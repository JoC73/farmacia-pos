<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use App\Models\PremiumModule;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PremiumModuleController extends Controller
{
    public function index()
    {
        PremiumModule::seedCatalog();

        $modules = PremiumModule::orderBy('name')->get();
        $sucursales = Sucursal::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $branchCleanupStats = $sucursales->mapWithKeys(function (Sucursal $sucursal) {
            return [
                $sucursal->id => [
                    'inventarios' => Inventario::where('sucursal_id', $sucursal->id)->count(),
                    'ventas' => Venta::where('sucursal_id', $sucursal->id)->count(),
                    'compras' => Compra::where('sucursal_id', $sucursal->id)->count(),
                    'cajas' => Caja::where('sucursal_id', $sucursal->id)->count(),
                ],
            ];
        });

        return view('premium.index', compact(
            'modules',
            'sucursales',
            'branchCleanupStats'
        ));
    }

    public function toggle(Request $request, PremiumModule $module)
    {
        $module->update([
            'enabled' => ! $module->enabled,
            'enabled_by' => ! $module->enabled ? auth()->id() : null,
            'enabled_at' => ! $module->enabled ? now() : null,
        ]);

        return redirect()
            ->route('premium.index')
            ->with('success', $module->enabled
                ? "Modulo {$module->name} activado."
                : "Modulo {$module->name} desactivado.");
    }

    public function locked(string $moduleCode)
    {
        PremiumModule::seedCatalog();

        $module = PremiumModule::where('code', $moduleCode)->first();

        return view('premium.locked', compact('module'));
    }

    public function resetBranchProducts(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'confirmation' => ['required', 'string', 'in:BORRAR'],
        ]);

        $sucursal = Sucursal::findOrFail($data['sucursal_id']);

        $summary = [
            'inventarios' => 0,
            'movimientos_inventario' => 0,
            'ventas' => 0,
            'compras' => 0,
            'cajas' => 0,
            'productos_desactivados' => 0,
        ];

        DB::transaction(function () use ($sucursal, &$summary) {
            $sucursalId = $sucursal->id;

            $ventaIds = Venta::where('sucursal_id', $sucursalId)->pluck('id');
            $compraIds = Compra::where('sucursal_id', $sucursalId)->pluck('id');
            $cajaIds = Caja::where('sucursal_id', $sucursalId)->pluck('id');
            $productIds = Inventario::where('sucursal_id', $sucursalId)
                ->pluck('producto_id')
                ->unique()
                ->values();

            $summary['cajas'] = $cajaIds->count();
            $summary['ventas'] = $ventaIds->count();
            $summary['compras'] = $compraIds->count();
            $summary['inventarios'] = Inventario::where('sucursal_id', $sucursalId)->count();
            $summary['movimientos_inventario'] = MovimientoInventario::where('sucursal_id', $sucursalId)->count();

            if ($cajaIds->isNotEmpty()) {
                MovimientoCaja::whereIn('caja_id', $cajaIds)->delete();
                Caja::whereIn('id', $cajaIds)->delete();
            }

            if ($ventaIds->isNotEmpty()) {
                DetalleVenta::whereIn('venta_id', $ventaIds)->delete();
                Venta::whereIn('id', $ventaIds)->delete();
            }

            if ($compraIds->isNotEmpty()) {
                DetalleCompra::whereIn('compra_id', $compraIds)->delete();
                Compra::whereIn('id', $compraIds)->delete();
            }

            MovimientoInventario::where('sucursal_id', $sucursalId)->delete();
            Inventario::where('sucursal_id', $sucursalId)->delete();

            if ($productIds->isNotEmpty()) {
                $orphanProductIds = Producto::whereIn('id', $productIds)
                    ->whereDoesntHave('inventarios')
                    ->pluck('id');

                if ($orphanProductIds->isNotEmpty()) {
                    $summary['productos_desactivados'] = Producto::whereIn('id', $orphanProductIds)
                        ->update(['estado' => false]);
                }
            }
        });

        return redirect()
            ->route('premium.index')
            ->with(
                'success',
                "Sucursal {$sucursal->nombre} limpiada. Inventarios: {$summary['inventarios']}. Movimientos: {$summary['movimientos_inventario']}. Ventas: {$summary['ventas']}. Compras: {$summary['compras']}. Cajas: {$summary['cajas']}. Productos desactivados sin otra sucursal: {$summary['productos_desactivados']}."
            );
    }
}
