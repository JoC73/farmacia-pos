<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\DetalleCompra;
use App\Models\Inventario;
use App\Models\MovimientoInventario;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $compras = Compra::with([
            'proveedor',
            'usuario',
            'sucursal',
        ])
        ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
        ->latest()
        ->paginate(20);

        return view('compras.index', compact('compras'));
    }

    public function create()
    {
        $productos = Producto::where('estado', true)
            ->ordenadoPorNombre()
            ->get();

        $proveedores = Proveedor::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('compras.create', compact(
            'productos',
            'proveedores'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedors,id',

            'productos' => 'required|array|min:1',

            'productos.*.producto_id' => 'required|exists:productos,id',

            'productos.*.cantidad' => 'required|integer|min:1',

            'productos.*.costo' => 'required|numeric|min:0',

        ]);

        $user = auth()->user();

if (!$user->sucursal_id) {

    return redirect()
        ->back()
        ->withInput()
        ->with(
            'error',
            'El usuario no tiene sucursal asignada.'
        );
}

        DB::transaction(function () use ($request) {

            $user = auth()->user();

            $subtotalGeneral = 0;

            $productosProcesados = [];

            /*
            |--------------------------------------------------------------------------
            | CALCULAR
            |--------------------------------------------------------------------------
            */

foreach ($request->productos as $item) {

    $producto = Producto::findOrFail($item['producto_id']);

    if ($item['costo'] <= 0) {

        abort(400, 'El costo del producto debe ser mayor a 0.');
    }


                $subtotal =
                    $item['costo'] * $item['cantidad'];

                $subtotalGeneral += $subtotal;

                $productosProcesados[] = [

                    'producto' => $producto,

                    'cantidad' => $item['cantidad'],

                    'costo' => $item['costo'],

                    'subtotal' => $subtotal,

                ];
            }

            /*
            |--------------------------------------------------------------------------
            | CREAR COMPRA
            |--------------------------------------------------------------------------
            */

            $compra = Compra::create([

                'proveedor_id' => $request->proveedor_id,

                'sucursal_id' => $user->sucursal_id,

                'user_id' => $user->id,

                'numero_factura' => $request->numero_factura,

                'fecha_compra' => now(),

                'subtotal' => $subtotalGeneral,

                'descuento' => 0,

                'total' => $subtotalGeneral,

                'estado' => 'REGISTRADA',

                'observacion' => $request->observacion,

            ]);

            /*
            |--------------------------------------------------------------------------
            | DETALLES + INVENTARIO + KARDEX
            |--------------------------------------------------------------------------
            */

            foreach ($productosProcesados as $item) {

                $producto = $item['producto'];

                $cantidad = $item['cantidad'];

                $costo = $item['costo'];

                $subtotal = $item['subtotal'];

                // DETALLE
                DetalleCompra::create([

                    'compra_id' => $compra->id,

                    'producto_id' => $producto->id,

                    'cantidad' => $cantidad,

                    'costo_unitario' => $costo,

                    'subtotal' => $subtotal,

                ]);

                // INVENTARIO
                $inventario = Inventario::firstOrCreate(

                    [
                        'producto_id' => $producto->id,
                        'sucursal_id' => $user->sucursal_id,
                    ],

                    [
                        'existencia' => 0,
                    ]

                );

                $existenciaAnterior = $inventario->existencia;

                $existenciaNueva =
                    $existenciaAnterior + $cantidad;

                $inventario->update([

                    'existencia' => $existenciaNueva,

                ]);

                // KARDEX
                MovimientoInventario::create([

                    'producto_id' => $producto->id,

                    'sucursal_id' => $user->sucursal_id,

                    'user_id' => $user->id,

                    'tipo_movimiento' => 'COMPRA',

                    'cantidad' => $cantidad,

                    'existencia_anterior' => $existenciaAnterior,

                    'existencia_nueva' => $existenciaNueva,

                    'referencia' => $compra->numero_factura,

                    'observacion' => 'Ingreso por compra',

                ]);
            }
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra registrada correctamente.');
    }

    public function show(Compra $compra)
    {
        abort_unless(auth()->user()->canAccessSucursal($compra->sucursal_id), 403);

        $compra->load([
            'proveedor',
            'usuario',
            'sucursal',
            'detalles.producto',
        ]);

        return view('compras.show', compact('compra'));
    }
}
