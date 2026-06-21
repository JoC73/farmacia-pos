<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        ->when($sucursalId, fn ($query) => $query->where('inventarios.sucursal_id', $sucursalId))
        ->when($selectedSucursalId, fn ($query) => $query->where('inventarios.sucursal_id', $selectedSucursalId))
        ->when($search !== '', fn ($query) => $query->where(function ($subquery) use ($search) {
            $subquery
                ->where('productos.nombre', 'like', "%{$search}%")
                ->orWhere('productos.codigo_barra', 'like', "%{$search}%")
                ->orWhere('productos.laboratorio', 'like', "%{$search}%")
                ->orWhere('categorias.nombre', 'like', "%{$search}%")
                ->orWhere('sucursales.nombre', 'like', "%{$search}%");
        }))
        ->when($estadoStock === 'bajo', fn ($query) => $query->whereColumn('inventarios.existencia', '<=', 'productos.stock_minimo'))
        ->when($estadoStock === 'normal', fn ($query) => $query->whereColumn('inventarios.existencia', '>', 'productos.stock_minimo'))
        ->orderByRaw('LOWER(productos.nombre)')
        ->orderByRaw("LOWER(COALESCE(sucursales.nombre, ''))")
        ->paginate($perPage)
        ->withQueryString();

        $sucursales = auth()->user()->canViewAllSucursales()
            ? Sucursal::where('estado', true)->orderBy('nombre')->get()
            : collect();

        $canAdjustInventory = auth()->user()->hasAnyRole(['Administrador', 'Administrador Global', 'Super Usuario']);

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
            'canAdjustInventory'
        ));
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
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($inventario, $data) {
            $inventario = Inventario::whereKey($inventario->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existenciaAnterior = (int) $inventario->existencia;
            $existenciaNueva = (int) $data['existencia'];
            $diferencia = $existenciaNueva - $existenciaAnterior;

            if ($diferencia === 0) {
                return;
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
                'referencia' => 'Ajuste manual de existencia',
                'observacion' => $data['observacion'] ?: 'Ajuste manual realizado por administrador',
            ]);
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', 'Existencia actualizada correctamente.');
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
