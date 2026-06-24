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
        $productos = $this->availableProductsForPurchase('', 20);

        $proveedores = Proveedor::where('estado', true)
            ->orderBy('nombre')
            ->get();

        if ($proveedores->isEmpty()) {
            return redirect()
                ->route('compras.index')
                ->with('error', 'No hay proveedores activos. Crea al menos un proveedor antes de registrar compras.');
        }

        if ($productos->isEmpty()) {
            return redirect()
                ->route('compras.index')
                ->with('error', 'No hay productos disponibles para esta sucursal. Realiza una carga inicial o entrada de inventario antes de registrar compras.');
        }

        $productosCompra = $productos
            ->map(fn ($producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'costo' => (float) $producto->costo,
                'codigo_barra' => $producto->codigo_barra,
            ])
            ->values();

        return view('compras.create', compact(
            'productos',
            'proveedores',
            'productosCompra'
        ));
    }

    public function searchProducts(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        if ($search !== '' && mb_strlen($search) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $this->availableProductsForPurchase($search, 30)
                ->map(fn ($producto) => [
                    'id' => (string) $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo_barra' => $producto->codigo_barra,
                    'costo' => (float) $producto->costo,
                ])
        );
    }

    public function store(Request $request)
    {
        $productos = collect($request->input('productos', []))
            ->filter(fn ($item) => ! empty($item['producto_id']))
            ->values()
            ->all();

        $request->merge([
            'productos' => $productos,
        ]);

        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',

            'numero_factura' => 'nullable|string|max:120',

            'productos' => 'required|array|min:1',

            'productos.*.producto_id' => 'required|exists:productos,id',

            'productos.*.cantidad' => 'required|integer|min:1',

            'productos.*.costo' => 'required|numeric|min:0.01',

            'productos.*.fecha_vencimiento' => 'nullable|date',

            'observacion' => 'nullable|string|max:500',

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

                $subtotal =
                    $item['costo'] * $item['cantidad'];

                $subtotalGeneral += $subtotal;

                $productosProcesados[] = [

                    'producto' => $producto,

                    'cantidad' => $item['cantidad'],

                    'costo' => $item['costo'],

                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,

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

                $fechaVencimiento = $item['fecha_vencimiento'];

                $subtotal = $item['subtotal'];

                // DETALLE
                DetalleCompra::create([

                    'compra_id' => $compra->id,

                    'producto_id' => $producto->id,

                    'cantidad' => $cantidad,

                    'costo_unitario' => $costo,

                    'subtotal' => $subtotal,

                    'fecha_vencimiento' => $fechaVencimiento,

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

                $inventarioData = [

                    'existencia' => $existenciaNueva,

                ];

                if ($fechaVencimiento) {
                    $inventarioData['fecha_vencimiento'] = $fechaVencimiento;
                }

                $inventario->update($inventarioData);

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

                    'observacion' => $fechaVencimiento
                        ? "Ingreso por compra. Vence: {$fechaVencimiento}"
                        : 'Ingreso por compra',

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

    private function availableProductsForPurchase(string $search = '', int $limit = 30)
    {
        $sucursalId = auth()->user()->visibleSucursalId();
        $normalizedSearch = mb_strtolower($search);

        return Producto::query()
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->where('productos.estado', true)
            ->when($sucursalId, fn ($query) => $query->whereHas(
                'inventarios',
                fn ($inventario) => $inventario->where('sucursal_id', $sucursalId)
            ))
            ->when($normalizedSearch !== '', function ($query) use ($normalizedSearch) {
                $like = "%{$normalizedSearch}%";

                $query->where(function ($subquery) use ($like) {
                    $subquery
                        ->whereRaw('LOWER(productos.nombre) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(productos.codigo_barra) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(productos.laboratorio, \'\')) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(categorias.nombre, \'\')) LIKE ?', [$like]);
                });
            })
            ->select([
                'productos.id',
                'productos.nombre',
                'productos.codigo_barra',
                'productos.costo',
            ])
            ->orderByRaw('LOWER(productos.nombre)')
            ->limit($limit)
            ->get();
    }
}
