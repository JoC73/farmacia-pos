<?php

namespace App\Http\Controllers;

use App\Models\Inventario;

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
        ->when($sucursalId, fn ($query) => $query->where('inventarios.sucursal_id', $sucursalId))
        ->orderByRaw('LOWER(productos.nombre)')
        ->orderByRaw("LOWER(COALESCE(sucursales.nombre, ''))")
        ->paginate(20);

        return view('inventarios.index', compact('inventarios'));
    }
}
