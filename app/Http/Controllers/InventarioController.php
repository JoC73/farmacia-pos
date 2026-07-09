<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $sucursalId = auth()->user()->visibleSucursalId();
        $perPage = $this->validPerPage($request->integer('per_page', 50));
        $search = trim((string) $request->input('q', ''));
        $estadoStock = $request->input('estado_stock');
        $selectedSucursalId = auth()->user()->canViewAllSucursales()
            ? $request->input('sucursal_id')
            : null;

        $inventarios = Inventario::with([
            'producto',
            'sucursal'
        ])
        ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
        ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
        ->leftJoin('sucursales', 'inventarios.sucursal_id', '=', 'sucursales.id')
        ->select('inventarios.*')
        ->where('productos.estado', true)
        ->where('inventarios.activo', true)
        ->when($sucursalId, fn ($query) => $query->where('inventarios.sucursal_id', $sucursalId))
        ->when($selectedSucursalId, fn ($query) => $query->where('inventarios.sucursal_id', $selectedSucursalId))
        ->when($search !== '', fn ($query) => $query->where(function ($subquery) use ($search) {
            $subquery
                ->where('productos.nombre', 'like', "%{$search}%")
                ->orWhere('inventarios.nombre_local', 'like', "%{$search}%")
                ->orWhere('inventarios.categoria_local', 'like', "%{$search}%")
                ->orWhere('inventarios.laboratorio_local', 'like', "%{$search}%")
                ->orWhere('productos.codigo_barra', 'like', "%{$search}%")
                ->orWhere('productos.laboratorio', 'like', "%{$search}%")
                ->orWhere('categorias.nombre', 'like', "%{$search}%")
                ->orWhere('sucursales.nombre', 'like', "%{$search}%");
        }))
        ->when($estadoStock === 'bajo', fn ($query) => $query->whereColumn('inventarios.existencia', '<=', 'productos.stock_minimo'))
        ->when($estadoStock === 'normal', fn ($query) => $query->whereColumn('inventarios.existencia', '>', 'productos.stock_minimo'))
        ->orderByRaw("LOWER(COALESCE(NULLIF(inventarios.nombre_local, ''), productos.nombre))")
        ->orderByRaw("LOWER(COALESCE(sucursales.nombre, ''))")
        ->paginate($perPage)
        ->withQueryString();

        $sucursales = auth()->user()->canViewAllSucursales()
            ? Sucursal::where('estado', true)->orderBy('nombre')->get()
            : collect();

        $canAdjustInventory = auth()->user()->hasAnyRole(['Administrador', 'Administrador Global', 'Super Usuario']);
        $canDownloadBranchInventories = auth()->user()->hasRole('Super Usuario');

        if ($request->ajax()) {
            return view('inventarios.partials.results', compact(
                'inventarios',
                'canAdjustInventory'
            ));
        }

        return view('inventarios.index', compact(
            'inventarios',
            'perPage',
            'search',
            'estadoStock',
            'selectedSucursalId',
            'sucursales',
            'canAdjustInventory',
            'canDownloadBranchInventories'
        ));
    }

    public function descargarSucursal(Sucursal $sucursal)
    {
        abort_unless(auth()->user()->hasRole('Super Usuario'), 403);
        abort_unless($sucursal->estado, 404);

        $inventarios = Inventario::with(['producto.categoria', 'sucursal'])
            ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->select('inventarios.*')
            ->where('inventarios.sucursal_id', $sucursal->id)
            ->where('inventarios.activo', true)
            ->where('productos.estado', true)
            ->orderByRaw("LOWER(COALESCE(NULLIF(inventarios.nombre_local, ''), productos.nombre))")
            ->get();

        $filename = 'inventario-' . Str::slug($sucursal->nombre) . '-' . now()->format('Ymd-His') . '.xlsx';
        $path = storage_path('app/' . $filename);
        $writer = new XlsxWriter();

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([
            'sucursal',
            'codigo_barra',
            'nombre_sucursal',
            'nombre_catalogo',
            'categoria',
            'laboratorio',
            'costo',
            'precio_venta',
            'stock_minimo',
            'existencia',
            'fecha_vencimiento',
        ]));

        foreach ($inventarios as $inventario) {
            $producto = $inventario->producto;

            $writer->addRow(Row::fromValues([
                $sucursal->nombre,
                $producto?->codigo_barra,
                $inventario->nombre_mostrado,
                $producto?->nombre,
                $inventario->categoria_mostrada,
                $inventario->laboratorio_mostrado,
                $inventario->costo_mostrado,
                $inventario->precio_venta_mostrado,
                $inventario->stock_minimo_mostrado,
                (int) $inventario->existencia,
                optional($inventario->fecha_vencimiento)->format('Y-m-d'),
            ]));
        }

        $writer->close();

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function ajustar(Inventario $inventario)
    {
        $this->authorizeInventoryAdjustment($inventario);

        $inventario->load(['producto', 'sucursal']);

        return view('inventarios.ajustar', compact('inventario'));
    }

    public function actualizarExistencia(Request $request, Inventario $inventario)
    {
        $this->authorizeInventoryAdjustment($inventario);

        $data = $request->validate([
            'existencia' => ['required', 'integer', 'min:0'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($inventario, $data) {
            $inventario = Inventario::whereKey($inventario->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existenciaAnterior = (int) $inventario->existencia;
            $existenciaNueva = (int) $data['existencia'];
            $diferencia = $existenciaNueva - $existenciaAnterior;
            $fechaAnterior = optional($inventario->fecha_vencimiento)->format('Y-m-d');
            $fechaNueva = $data['fecha_vencimiento'] ?? null;
            $fechaCambio = $fechaAnterior !== $fechaNueva;

            if ($diferencia === 0 && ! $fechaCambio) {
                return;
            }

            $inventario->update([
                'existencia' => $existenciaNueva,
                'fecha_vencimiento' => $fechaNueva,
            ]);

            if ($diferencia !== 0) {
                MovimientoInventario::create([
                    'producto_id' => $inventario->producto_id,
                    'sucursal_id' => $inventario->sucursal_id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => $diferencia > 0 ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA',
                    'cantidad' => abs($diferencia),
                    'existencia_anterior' => $existenciaAnterior,
                    'existencia_nueva' => $existenciaNueva,
                    'referencia' => 'Ajuste manual de existencia',
                    'observacion' => $data['observacion'] ?: 'Ajuste manual realizado por administrador',
                ]);
            }

            if ($fechaCambio) {
                MovimientoInventario::create([
                    'producto_id' => $inventario->producto_id,
                    'sucursal_id' => $inventario->sucursal_id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => 'AJUSTE_ENTRADA',
                    'cantidad' => 0,
                    'existencia_anterior' => $existenciaNueva,
                    'existencia_nueva' => $existenciaNueva,
                    'referencia' => 'Ajuste de vencimiento',
                    'observacion' => $data['observacion']
                        ?: "Fecha de vencimiento actualizada de ".($fechaAnterior ?: 'sin fecha')." a ".($fechaNueva ?: 'sin fecha'),
                ]);
            }
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', 'Inventario actualizado correctamente.');
    }

    public function actualizarFechaVencimiento(Request $request, Inventario $inventario)
    {
        $this->authorizeInventoryAdjustment($inventario);

        $data = $request->validate([
            'fecha_vencimiento' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($inventario, $data) {
            $inventario = Inventario::whereKey($inventario->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fechaAnterior = optional($inventario->fecha_vencimiento)->format('Y-m-d');
            $fechaNueva = $data['fecha_vencimiento'] ?? null;

            if ($fechaAnterior === $fechaNueva) {
                return;
            }

            $inventario->update([
                'fecha_vencimiento' => $fechaNueva,
            ]);

            MovimientoInventario::create([
                'producto_id' => $inventario->producto_id,
                'sucursal_id' => $inventario->sucursal_id,
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'AJUSTE_ENTRADA',
                'cantidad' => 0,
                'existencia_anterior' => (int) $inventario->existencia,
                'existencia_nueva' => (int) $inventario->existencia,
                'referencia' => 'Ajuste de vencimiento',
                'observacion' => "Fecha de vencimiento actualizada de ".($fechaAnterior ?: 'sin fecha')." a ".($fechaNueva ?: 'sin fecha'),
            ]);
        });

        return back()->with('success', 'Fecha de vencimiento actualizada correctamente.');
    }

    private function authorizeInventoryAdjustment(Inventario $inventario): void
    {
        abort_unless(auth()->user()->hasAnyRole(['Administrador', 'Administrador Global', 'Super Usuario']), 403);
        abort_unless(auth()->user()->canAccessSucursal($inventario->sucursal_id), 403);
    }

    private function validPerPage(int $perPage): int
    {
        return in_array($perPage, [25, 50, 100, 200], true) ? $perPage : 50;
    }
}
