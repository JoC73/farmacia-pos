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
        ->orderByDesc('id')
        ->paginate(20);

        return view('inventarios.index', compact('inventarios'));
    }
}