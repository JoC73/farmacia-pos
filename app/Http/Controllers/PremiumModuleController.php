<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\PremiumModule;
use App\Models\Sucursal;
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
                    'inventarios' => Inventario::where('sucursal_id', $sucursal->id)
                        ->where('activo', true)
                        ->count(),
                    'existencia' => Inventario::where('sucursal_id', $sucursal->id)
                        ->where('activo', true)
                        ->sum('existencia'),
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
            'confirmation' => ['required', 'string', 'in:BORRAR INVENTARIO'],
        ]);

        $sucursal = Sucursal::findOrFail($data['sucursal_id']);

        $summary = [
            'inventarios_ocultados' => 0,
            'movimientos_inventario' => 0,
            'existencia_retirada' => 0,
            'ventas_conservadas' => 0,
            'compras_conservadas' => 0,
            'cajas_conservadas' => 0,
        ];

        DB::transaction(function () use ($sucursal, &$summary) {
            $sucursalId = $sucursal->id;

            $summary['ventas_conservadas'] = Venta::where('sucursal_id', $sucursalId)->count();
            $summary['compras_conservadas'] = Compra::where('sucursal_id', $sucursalId)->count();
            $summary['cajas_conservadas'] = Caja::where('sucursal_id', $sucursalId)->count();

            $inventarios = Inventario::where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->lockForUpdate()
                ->get();

            foreach ($inventarios as $inventario) {
                $existenciaAnterior = (int) $inventario->existencia;

                $inventario->update([
                    'activo' => false,
                    'existencia' => 0,
                ]);

                $summary['inventarios_ocultados']++;
                $summary['existencia_retirada'] += $existenciaAnterior;

                if ($existenciaAnterior > 0) {
                    MovimientoInventario::create([
                        'producto_id' => $inventario->producto_id,
                        'sucursal_id' => $sucursalId,
                        'user_id' => auth()->id(),
                        'tipo_movimiento' => 'AJUSTE_SALIDA',
                        'cantidad' => $existenciaAnterior,
                        'existencia_anterior' => $existenciaAnterior,
                        'existencia_nueva' => 0,
                        'referencia' => 'Borrado seguro de inventario',
                        'observacion' => 'Producto ocultado por Super Usuario sin borrar ventas, cajas ni historial.',
                    ]);

                    $summary['movimientos_inventario']++;
                }
            }
        });

        return redirect()
            ->route('premium.index')
            ->with(
                'success',
                "Borrado seguro aplicado en {$sucursal->nombre}. Inventarios ocultados: {$summary['inventarios_ocultados']}. Existencia retirada: {$summary['existencia_retirada']}. Movimientos creados: {$summary['movimientos_inventario']}. Ventas conservadas: {$summary['ventas_conservadas']}. Compras conservadas: {$summary['compras_conservadas']}. Cajas conservadas: {$summary['cajas_conservadas']}."
            );
    }
}
