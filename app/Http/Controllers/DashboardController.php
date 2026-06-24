<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Inventario;
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

        $productosPorVencer = Inventario::with(['producto', 'sucursal'])
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
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
            'productosPorVencer',
            'productosVencidos',
            'topProductos',
            'scopeLabel'
        ));
    }
}
