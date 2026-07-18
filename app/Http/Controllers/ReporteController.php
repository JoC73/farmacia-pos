<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\CorteMensualCaja;
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

        if ($request->filled('month')) {
            $query->whereMonth('created_at', (int) $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', (int) $request->year);
        }

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

        $years = $this->availableYears();
        $months = $this->months();

        return view('reportes.ventas', compact(
            'ventas',
            'totalVentas',
            'sucursales',
            'usuarios',
            'years',
            'months'
        ));
    }

    public function cortesMensuales(Request $request)
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $query = CorteMensualCaja::with(['sucursal', 'caja', 'usuario'])
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId));

        if ($request->filled('month')) {
            $query->where('periodo_month', (int) $request->month);
        }

        if ($request->filled('year')) {
            $query->where('periodo_year', (int) $request->year);
        }

        if (! $sucursalId && $request->filled('sucursal_id')) {
            $query->where('sucursal_id', (int) $request->sucursal_id);
        }

        $totalsQuery = clone $query;

        $cortes = $query
            ->orderByDesc('periodo_year')
            ->orderByDesc('periodo_month')
            ->latest('fecha_corte')
            ->paginate(30)
            ->withQueryString();

        $totales = [
            'disponible_antes' => (float) (clone $totalsQuery)->sum('disponible_antes'),
            'monto_transferido' => (float) (clone $totalsQuery)->sum('monto_transferido'),
            'saldo_restante' => (float) (clone $totalsQuery)->sum('saldo_restante'),
        ];

        $sucursales = Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();

        return view('reportes.cortes-mensuales', [
            'cortes' => $cortes,
            'totales' => $totales,
            'sucursales' => $sucursales,
            'years' => $this->availableYears(),
            'months' => $this->months(),
        ]);
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

    private function availableYears(): array
    {
        $currentYear = (int) now()->year;
        $startYear = 2026;

        return range($currentYear, $startYear);
    }

    private function months(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }
}
