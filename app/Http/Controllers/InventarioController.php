<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $inventarios = Inventario::with([
            'producto',
            'sucursal'
        ])
        ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
        ->leftJoin('sucursales', 'inventarios.sucursal_id', '=', 'sucursales.id')
        ->select('inventarios.*')
        ->where('productos.estado', true)
        ->when($sucursalId, fn ($query) => $query->where('inventarios.sucursal_id', $sucursalId))
        ->orderByRaw('LOWER(productos.nombre)')
        ->orderByRaw("LOWER(COALESCE(sucursales.nombre, ''))")
        ->paginate(20);

        return view('inventarios.index', compact('inventarios'));
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
}
