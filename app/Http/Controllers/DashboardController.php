<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $ventasHoy = Venta::where('estado', 'FINALIZADA')
            ->whereDate('created_at', today())
            ->sum('total');

        $ventasMes = Venta::where('estado', 'FINALIZADA')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $comprasMes = Compra::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $cajasAbiertas = Caja::where('estado', 'ABIERTA')
            ->count();

        $stockBajo = Inventario::with([
                'producto',
                'sucursal'
            ])
            ->whereHas('producto')
            ->get()
            ->filter(function ($inventario) {
                return $inventario->existencia <= $inventario->producto->stock_minimo;
            });

        $productosPorVencer = Producto::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<=', Carbon::now()->addDays(30))
            ->whereDate('fecha_vencimiento', '>=', Carbon::now())
            ->orderBy('fecha_vencimiento')
            ->get();

        $productosVencidos = Producto::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', Carbon::now())
            ->orderBy('fecha_vencimiento')
            ->get();

        $topProductos = DetalleVenta::select(
                'producto_id',
                DB::raw('SUM(cantidad) as total_vendido'),
                DB::raw('SUM(subtotal) as total_generado')
            )
            ->with('producto')
            ->whereHas('venta', fn ($query) => $query->where('estado', 'FINALIZADA'))
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'ventasHoy',
            'ventasMes',
            'comprasMes',
            'cajasAbiertas',
            'stockBajo',
            'productosPorVencer',
            'productosVencidos',
            'topProductos'
        ));
    }
}
