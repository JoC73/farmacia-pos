<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntradaInventarioController extends Controller
{
    public function create()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $productos = Producto::where('estado', true)
            ->ordenadoPorNombre()
            ->get();

        $sucursales = Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();

        return view('inventarios.entrada', compact(
            'productos',
            'sucursales'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([

            'sucursal_id' => 'required|exists:sucursales,id',

            'productos' => 'required|array|min:1',

            'productos.*.producto_id' => 'required|exists:productos,id',

            'productos.*.cantidad' => 'required|integer|min:1',

            'observacion' => 'nullable|string|max:500',

        ]);

        abort_unless(auth()->user()->canAccessSucursal((int) $request->sucursal_id), 403);

        DB::transaction(function () use ($request) {

            foreach ($request->productos as $item) {

                $inventario = Inventario::firstOrCreate(
                    [
                        'producto_id' => $item['producto_id'],
                        'sucursal_id' => $request->sucursal_id,
                    ],
                    [
                        'existencia' => 0,
                    ]
                );

                $existenciaAnterior = $inventario->existencia;

                $existenciaNueva =
                    $existenciaAnterior + $item['cantidad'];

                $inventario->update([
                    'existencia' => $existenciaNueva,
                ]);

                MovimientoInventario::create([
                    'producto_id' => $item['producto_id'],
                    'sucursal_id' => $request->sucursal_id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => 'COMPRA',
                    'cantidad' => $item['cantidad'],
                    'existencia_anterior' => $existenciaAnterior,
                    'existencia_nueva' => $existenciaNueva,
                    'referencia' => 'Entrada manual de inventario',
                    'observacion' => $request->observacion,
                ]);
            }
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', 'Inventario ingresado correctamente.');
    }
}
