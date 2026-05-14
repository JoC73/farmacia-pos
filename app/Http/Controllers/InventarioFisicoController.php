<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarioFisicoController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('inventarios.fisico', [
            'sucursales' => $sucursales,
            'previewRows' => collect(),
            'importErrors' => collect(),
            'selectedSucursal' => null,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
        ]);

        $sucursal = Sucursal::findOrFail($data['sucursal_id']);
        $productos = Producto::with([
            'categoria',
            'inventarios' => fn ($query) => $query->where('sucursal_id', $sucursal->id),
        ])
            ->where('estado', true)
            ->ordenadoPorNombre()
            ->get();

        $filename = 'inventario-fisico-' . Str::slug($sucursal->nombre) . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($productos, $sucursal) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'producto_id',
                'codigo_barra',
                'producto',
                'categoria',
                'sucursal',
                'existencia_sistema',
                'existencia_fisica',
                'observacion',
            ]);

            foreach ($productos as $producto) {
                $inventario = $producto->inventarios->first();

                fputcsv($handle, [
                    $producto->id,
                    $producto->codigo_barra,
                    $producto->nombre,
                    $producto->categoria->nombre ?? 'Sin categoria',
                    $sucursal->nombre,
                    $inventario?->existencia ?? 0,
                    '',
                    '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $sucursales = Sucursal::where('estado', true)
            ->orderBy('nombre')
            ->get();

        [$previewRows, $importErrors] = $this->parseCsv(
            $request->file('archivo')->getRealPath(),
            (int) $data['sucursal_id']
        );

        return view('inventarios.fisico', [
            'sucursales' => $sucursales,
            'previewRows' => $previewRows,
            'importErrors' => $importErrors,
            'selectedSucursal' => (int) $data['sucursal_id'],
        ]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'rows' => ['required', 'string'],
        ]);

        $rows = collect(json_decode($data['rows'], true));

        abort_if($rows->isEmpty(), 422, 'No hay datos para aplicar.');

        $referencia = 'Inventario fisico ' . now()->format('Ymd-His');
        $aplicados = 0;

        DB::transaction(function () use ($rows, $data, $referencia, &$aplicados) {
            foreach ($rows as $row) {
                $inventario = Inventario::where('producto_id', $row['producto_id'])
                    ->where('sucursal_id', $data['sucursal_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$inventario) {
                    $inventario = Inventario::create([
                        'producto_id' => $row['producto_id'],
                        'sucursal_id' => $data['sucursal_id'],
                        'existencia' => 0,
                    ]);
                }

                $existenciaAnterior = (int) $inventario->existencia;
                $existenciaNueva = (int) $row['existencia_fisica'];
                $diferencia = $existenciaNueva - $existenciaAnterior;

                if ($diferencia === 0) {
                    continue;
                }

                $inventario->update([
                    'existencia' => $existenciaNueva,
                ]);

                MovimientoInventario::create([
                    'producto_id' => $inventario->producto_id,
                    'sucursal_id' => $inventario->sucursal_id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => $diferencia > 0 ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA',
                    'cantidad' => abs($diferencia),
                    'existencia_anterior' => $existenciaAnterior,
                    'existencia_nueva' => $existenciaNueva,
                    'referencia' => $referencia,
                    'observacion' => trim($row['observacion'] ?? '') ?: 'Ajuste por inventario fisico',
                ]);

                $aplicados++;
            }
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', "Inventario fisico aplicado correctamente. Ajustes registrados: {$aplicados}.");
    }

    private function parseCsv(string $path, int $sucursalId): array
    {
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $previewRows = collect();
        $errors = collect();
        $line = 1;

        if (!$headers) {
            fclose($handle);

            return [$previewRows, collect(['El archivo esta vacio o no tiene encabezados.'])];
        }

        $headers = collect($headers)
            ->map(fn ($header) => trim(str_replace("\xEF\xBB\xBF", '', (string) $header)))
            ->map(fn ($header) => Str::snake(Str::lower($header)))
            ->all();

        $requiredHeaders = ['producto_id', 'codigo_barra', 'existencia_fisica'];
        foreach ($requiredHeaders as $header) {
            if (!in_array($header, $headers, true)) {
                $errors->push("Falta la columna requerida: {$header}.");
            }
        }

        if ($errors->isNotEmpty()) {
            fclose($handle);

            return [$previewRows, $errors];
        }

        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            $row = array_combine($headers, array_slice(array_pad($data, count($headers), null), 0, count($headers)));

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $fisica = trim((string) ($row['existencia_fisica'] ?? ''));

            if ($fisica === '') {
                continue;
            }

            if (!ctype_digit($fisica)) {
                $errors->push("Linea {$line}: existencia_fisica debe ser un numero entero mayor o igual a 0.");
                continue;
            }

            $producto = Producto::where('id', $row['producto_id'] ?? null)
                ->where('codigo_barra', $row['codigo_barra'] ?? null)
                ->first();

            if (!$producto) {
                $errors->push("Linea {$line}: producto no encontrado o codigo de barra no coincide.");
                continue;
            }

            $inventario = Inventario::where('producto_id', $producto->id)
                ->where('sucursal_id', $sucursalId)
                ->first();

            $sistema = (int) ($inventario?->existencia ?? 0);
            $fisica = (int) $fisica;

            $previewRows->push([
                'producto_id' => $producto->id,
                'codigo_barra' => $producto->codigo_barra,
                'producto' => $producto->nombre,
                'existencia_sistema' => $sistema,
                'existencia_fisica' => $fisica,
                'diferencia' => $fisica - $sistema,
                'observacion' => trim((string) ($row['observacion'] ?? '')),
            ]);
        }

        fclose($handle);

        if ($previewRows->isEmpty() && $errors->isEmpty()) {
            $errors->push('No se encontraron filas con existencia_fisica para aplicar.');
        }

        return [$previewRows, $errors];
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isEmpty();
    }
}
