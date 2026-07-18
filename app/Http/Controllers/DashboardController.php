<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $sucursalId = $user->visibleSucursalId();
        $scopeLabel = $sucursalId
            ? ($user->sucursal?->nombre ?? 'Sucursal asignada')
            : 'Todas las sucursales';

        $ventasHoy = Venta::where('estado', 'FINALIZADA')
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->whereDate('created_at', today())
            ->sum('total');

        $ventasMes = Venta::where('estado', 'FINALIZADA')
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $comprasMes = Compra::whereMonth('created_at', now()->month)
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $cajasAbiertas = Caja::where('estado', 'ABIERTA')
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->count();

        [$saldoCajaActual, $estadoSaldoCaja] = $this->saldoCajaActual($sucursalId);

        $productosPorVencer = Inventario::with(['producto', 'sucursal'])
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->where('activo', true)
            ->where('existencia', '>', 0)
            ->whereNotNull('inventarios.fecha_vencimiento')
            ->whereDate('inventarios.fecha_vencimiento', '<=', Carbon::now()->addDays(90))
            ->whereDate('inventarios.fecha_vencimiento', '>=', Carbon::now())
            ->whereHas('producto', fn ($query) => $query->where('estado', true))
            ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
            ->select('inventarios.*')
            ->orderBy('inventarios.fecha_vencimiento')
            ->limit(12)
            ->get();

        $productosVencidos = Inventario::with(['producto', 'sucursal'])
            ->where('existencia', '>', 0)
            ->where('activo', true)
            ->whereNotNull('inventarios.fecha_vencimiento')
            ->when($sucursalId, fn ($query) => $query->whereHas(
                'sucursal',
                fn ($sucursal) => $sucursal->whereKey($sucursalId)
            ))
            ->whereHas('producto', fn ($query) => $query->where('estado', true))
            ->whereDate('inventarios.fecha_vencimiento', '<', Carbon::now())
            ->orderBy('inventarios.fecha_vencimiento')
            ->get();

        $topProductos = DetalleVenta::select(
                'producto_id',
                DB::raw('SUM(cantidad) as total_vendido'),
                DB::raw('SUM(subtotal) as total_generado')
            )
            ->with('producto')
            ->whereHas('venta', fn ($query) => $query
                ->where('estado', 'FINALIZADA')
                ->when($sucursalId, fn ($ventaQuery) => $ventaQuery->where('sucursal_id', $sucursalId)))
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'ventasHoy',
            'ventasMes',
            'comprasMes',
            'cajasAbiertas',
            'saldoCajaActual',
            'estadoSaldoCaja',
            'productosPorVencer',
            'productosVencidos',
            'topProductos',
            'scopeLabel'
        ));
    }

    private function saldoCajaActual(?int $sucursalId): array
    {
        if ($sucursalId) {
            $caja = $this->ultimaCajaSucursal($sucursalId);

            return [
                $caja ? $this->saldoCaja($caja) : 0,
                $caja ? $caja->estado : 'SIN CAJA',
            ];
        }

        $total = Sucursal::where('estado', true)
            ->get()
            ->sum(function (Sucursal $sucursal) {
                $caja = $this->ultimaCajaSucursal($sucursal->id);

                return $caja ? $this->saldoCaja($caja) : 0;
            });

        return [(float) $total, 'TODAS'];
    }

    private function ultimaCajaSucursal(int $sucursalId): ?Caja
    {
        return Caja::where('sucursal_id', $sucursalId)
            ->latest('fecha_apertura')
            ->latest('id')
            ->first();
    }

    private function saldoCaja(Caja $caja): float
    {
        if ($caja->estado === 'CERRADA' && $caja->monto_cierre !== null) {
            return (float) $caja->monto_cierre;
        }

        $ventas = $this->sumMovimientosCaja($caja, ['VENTA']);
        $egresos = $this->sumMovimientosCaja($caja, ['EGRESO']);
        $transferencias = $this->sumMovimientosCaja($caja, ['TRANSFERENCIA_JEFE']);

        return (float) $caja->monto_apertura + $ventas - $egresos - $transferencias;
    }

    private function sumMovimientosCaja(Caja $caja, array $tipos): float
    {
        return (float) MovimientoCaja::where('caja_id', $caja->id)
            ->whereIn('tipo', $tipos)
            ->sum('monto');
    }
}
