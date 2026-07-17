<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use App\Models\Sucursal;
use App\Models\User;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index()
    {
        return view('reportes.index');
    }

    public function ventas(Request $request)
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $query = Venta::with([
            'cliente',
            'usuario',
            'sucursal',
        ])
            ->where('estado', 'FINALIZADA')
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId));

        /*
        |--------------------------------------------------------------------------
        | FILTRO FECHA INICIO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('fecha_inicio')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->fecha_inicio
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO FECHA FIN
        |--------------------------------------------------------------------------
        */

        if ($request->filled('fecha_fin')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->fecha_fin
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO SUCURSAL
        |--------------------------------------------------------------------------
        */

        if (!$sucursalId && $request->filled('sucursal_id')) {

            $query->where(
                'sucursal_id',
                $request->sucursal_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO USUARIO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('user_id')) {

            $query->where(
                'user_id',
                $request->user_id
            );
        }

        $ventas = $query
            ->latest()
            ->paginate(30)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | TOTAL GENERAL
        |--------------------------------------------------------------------------
        */

        $totalVentas = (clone $query)->sum('total');

        /*
        |--------------------------------------------------------------------------
        | DATOS FILTROS
        |--------------------------------------------------------------------------
        */

        $sucursales = Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();

        $usuarios = User::orderBy('name')
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->get();

        return view('reportes.ventas', compact(
            'ventas',
            'totalVentas',
            'sucursales',
            'usuarios'
        ));
    }

    public function movimientosSucursal()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $sucursales = Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();

        $resumen = $sucursales->map(function (Sucursal $sucursal) {
            $ventasHoy = Venta::where('estado', 'FINALIZADA')
                ->where('sucursal_id', $sucursal->id)
                ->whereDate('created_at', today())
                ->sum('total');

            $ventasMes = Venta::where('estado', 'FINALIZADA')
                ->where('sucursal_id', $sucursal->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total');

            $comprasMes = Compra::where('sucursal_id', $sucursal->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total');

            $egresosMes = MovimientoCaja::where('tipo', 'EGRESO')
                ->whereHas('caja', fn ($query) => $query->where('sucursal_id', $sucursal->id))
                ->where(function ($query) {
                    $query
                        ->where(function ($fechaMovimiento) {
                            $fechaMovimiento
                                ->whereMonth('fecha_movimiento', now()->month)
                                ->whereYear('fecha_movimiento', now()->year);
                        })
                        ->orWhere(function ($fallback) {
                            $fallback
                                ->whereNull('fecha_movimiento')
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year);
                        });
                })
                ->sum('monto');

            $transferenciasMes = MovimientoCaja::where('tipo', 'TRANSFERENCIA_JEFE')
                ->whereHas('caja', fn ($query) => $query->where('sucursal_id', $sucursal->id))
                ->whereMonth('fecha_movimiento', now()->month)
                ->whereYear('fecha_movimiento', now()->year)
                ->sum('monto');

            $cierresHoy = Caja::where('sucursal_id', $sucursal->id)
                ->where('estado', 'CERRADA')
                ->whereDate('fecha_cierre', today())
                ->sum('monto_cierre');

            $cierresMes = Caja::where('sucursal_id', $sucursal->id)
                ->where('estado', 'CERRADA')
                ->whereMonth('fecha_cierre', now()->month)
                ->whereYear('fecha_cierre', now()->year)
                ->sum('monto_cierre');

            $diferenciaMes = Caja::where('sucursal_id', $sucursal->id)
                ->where('estado', 'CERRADA')
                ->whereMonth('fecha_cierre', now()->month)
                ->whereYear('fecha_cierre', now()->year)
                ->sum('diferencia');

            $cajasAbiertas = Caja::where('sucursal_id', $sucursal->id)
                ->where('estado', 'ABIERTA')
                ->count();

            return [
                'sucursal' => $sucursal,
                'ventas_hoy' => $ventasHoy,
                'ventas_mes' => $ventasMes,
                'compras_mes' => $comprasMes,
                'egresos_mes' => $egresosMes,
                'transferencias_mes' => $transferenciasMes,
                'cierres_hoy' => $cierresHoy,
                'cierres_mes' => $cierresMes,
                'diferencia_mes' => $diferenciaMes,
                'cajas_abiertas' => $cajasAbiertas,
                'flujo_neto_mes' => $ventasMes - $comprasMes - $egresosMes - $transferenciasMes,
            ];
        });

        return view('reportes.movimientos-sucursal', [
            'resumen' => $resumen,
            'scopeLabel' => $sucursalId
                ? (auth()->user()->sucursal?->nombre ?? 'Sucursal asignada')
                : 'Todas las sucursales',
        ]);
    }
}
