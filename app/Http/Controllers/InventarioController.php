<?php

namespace App\Http\Controllers;

use App\Models\Inventario;

class InventarioController extends Controller
{
    public function index()
    {
        $inventarios = Inventario::with([
            'producto',
            'sucursal'
        ])
        ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
        ->leftJoin('sucursales', 'inventarios.sucursal_id', '=', 'sucursales.id')
        ->select('inventarios.*')
        ->orderByRaw('LOWER(productos.nombre)')
        ->orderByRaw("LOWER(COALESCE(sucursales.nombre, ''))")
        ->paginate(20);

        return view('inventarios.index', compact('inventarios'));
    }
}
