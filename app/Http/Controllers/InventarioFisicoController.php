<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarioFisicoController extends Controller
{
    public function index()
    {
        $sucursales = $this->sucursalesPermitidas();

        return view('inventarios.fisico', [
            'sucursales' => $sucursales,
            'previewRows' => collect(),
            'importErrors' => collect(),
            'selectedSucursal' => null,
        ]);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
        ]);

        $this->authorizeSucursalAccess((int) $data['sucursal_id']);

        $sucursal = Sucursal::findOrFail($data['sucursal_id']);
        $productos = Producto::with([
            'categoria',
            'inventarios' => fn ($query) => $query
                ->where('sucursal_id', $sucursal->id)
                ->where('activo', true),
        ])
            ->where('estado', true)
            ->ordenadoPorNombre()
            ->get();

        $filename = 'inventario-fisico-' . Str::slug($sucursal->nombre) . '-' . now()->format('Ymd') . '.xlsx';
        $path = storage_path('app/' . $filename);
        $writer = new XlsxWriter();

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([
                'producto_id',
                'codigo_barra',
                'producto',
                'categoria',
                'sucursal',
                'existencia_sistema',
                'existencia_fisica',
                'observacion',
            ]));

            foreach ($productos as $producto) {
                $inventario = $producto->inventarios->first();

            $writer->addRow(Row::fromValues([
                    $producto->id,
                    $producto->codigo_barra,
                    $inventario?->nombre_mostrado ?? $producto->nombre,
                    $inventario?->categoria_mostrada ?? ($producto->categoria->nombre ?? 'Sin categoria'),
                    $sucursal->nombre,
                    $inventario?->existencia ?? 0,
                    '',
                    '',
            ]));
            }

        $writer->close();

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'archivo' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:4096'],
        ]);

        $this->authorizeSucursalAccess((int) $data['sucursal_id']);

        $sucursales = $this->sucursalesPermitidas();

        [$previewRows, $importErrors] = $this->parseFile(
            $request->file('archivo'),
            (int) $data['sucursal_id']
        );

        $previewToken = null;

        if ($previewRows->isNotEmpty() && $importErrors->isEmpty()) {
            $previewToken = (string) Str::uuid();
            session([
                "inventario_fisico_preview.{$previewToken}" => [
                    'sucursal_id' => (int) $data['sucursal_id'],
                    'rows' => $previewRows->values()->all(),
                ],
            ]);
        }

        return view('inventarios.fisico', [
            'sucursales' => $sucursales,
            'previewRows' => $previewRows,
            'importErrors' => $importErrors,
            'selectedSucursal' => (int) $data['sucursal_id'],
            'previewToken' => $previewToken,
        ]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'preview_token' => ['required', 'string'],
        ]);

        $this->authorizeSucursalAccess((int) $data['sucursal_id']);

        $preview = session()->pull("inventario_fisico_preview.{$data['preview_token']}");

        if (!$preview || (int) $preview['sucursal_id'] !== (int) $data['sucursal_id']) {
            return redirect()
                ->route('inventarios.fisico')
                ->with('error', 'La vista previa expiro. Vuelve a validar el archivo antes de confirmar.');
        }

        $rows = collect($preview['rows']);

        if ($rows->isEmpty()) {
            return redirect()
                ->route('inventarios.fisico')
                ->with('error', 'No hay datos para aplicar.');
        }

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

    private function parseFile($file, int $sucursalId): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        if ($extension === 'xlsx') {
            return $this->parseXlsx($file->getRealPath(), $sucursalId);
        }

        return $this->parseCsv($file->getRealPath(), $sucursalId);
    }

    private function parseXlsx(string $path, int $sucursalId): array
    {
        $reader = new XlsxReader();
        $reader->open($path);

        $headers = null;
        $previewRows = collect();
        $errors = collect();
        $line = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $values = $row->toArray();

                if ($headers === null) {
                    $headers = $this->normalizeHeaders($values);
                    $errors = $this->validateHeaders($headers);

                    if ($errors->isNotEmpty()) {
                        $reader->close();

                        return [$previewRows, $errors];
                    }

                    continue;
                }

                $parsedRow = array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));
                $this->addPreviewRow($parsedRow, $line, $sucursalId, $previewRows, $errors);
            }

            break;
        }

        $reader->close();

        if ($headers === null) {
            $errors->push('El archivo esta vacio o no tiene encabezados.');
        }

        if ($previewRows->isEmpty() && $errors->isEmpty()) {
            $errors->push('No se encontraron filas con existencia_fisica para aplicar.');
        }

        return [$previewRows, $errors];
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

        $headers = $this->normalizeHeaders($headers);
        $errors = $this->validateHeaders($headers);

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

            $this->addPreviewRow($row, $line, $sucursalId, $previewRows, $errors);
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

    private function normalizeHeaders(array $headers): array
    {
        return collect($headers)
            ->map(fn ($header) => trim(str_replace("\xEF\xBB\xBF", '', (string) $header)))
            ->map(fn ($header) => Str::snake(Str::lower($header)))
            ->all();
    }

    private function validateHeaders(array $headers)
    {
        $errors = collect();
        $requiredHeaders = ['producto_id', 'codigo_barra', 'existencia_fisica'];

        foreach ($requiredHeaders as $header) {
            if (!in_array($header, $headers, true)) {
                $errors->push("Falta la columna requerida: {$header}.");
            }
        }

        return $errors;
    }

    private function addPreviewRow(array $row, int $line, int $sucursalId, $previewRows, $errors): void
    {
        if ($this->isEmptyRow($row)) {
            return;
        }

        $fisica = trim((string) ($row['existencia_fisica'] ?? ''));

        if ($fisica === '') {
            return;
        }

        if (!ctype_digit($fisica)) {
            $errors->push("Linea {$line}: existencia_fisica debe ser un numero entero mayor o igual a 0.");
            return;
        }

        $producto = Producto::where('id', $row['producto_id'] ?? null)
            ->where('codigo_barra', $row['codigo_barra'] ?? null)
            ->first();

        if (!$producto) {
            $errors->push("Linea {$line}: producto no encontrado o codigo de barra no coincide.");
            return;
        }

        $inventario = Inventario::where('producto_id', $producto->id)
            ->where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->first();

        $sistema = (int) ($inventario?->existencia ?? 0);
        $fisica = (int) $fisica;

        $previewRows->push([
            'producto_id' => $producto->id,
            'codigo_barra' => $producto->codigo_barra,
            'producto' => $inventario?->nombre_mostrado ?? $producto->nombre,
            'existencia_sistema' => $sistema,
            'existencia_fisica' => $fisica,
            'diferencia' => $fisica - $sistema,
            'observacion' => trim((string) ($row['observacion'] ?? '')),
        ]);
    }

    private function sucursalesPermitidas()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        return Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();
    }

    private function authorizeSucursalAccess(int $sucursalId): void
    {
        abort_unless(auth()->user()->canAccessSucursal($sucursalId), 403);
    }
}
