<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\DetalleVenta;
use App\Models\MovimientoInventario;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Caja;
use App\Models\MovimientoCaja;


class VentaController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $ventas = Venta::with([
            'usuario',
            'sucursal',
            'cliente',
            'anulador',
        ])
        ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
        ->latest()
        ->paginate(20);

        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $user = auth()->user();
        $cajaAbierta = null;

        if ($user->sucursal_id) {
            $cajaAbierta = Caja::with(['usuario', 'sucursal'])
                ->where('sucursal_id', $user->sucursal_id)
                ->where('estado', 'ABIERTA')
                ->latest()
                ->first();
        }

        $productos = $this->availableProductsForSale('', 18);

        $clientes = Cliente::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('ventas.create', compact(
            'productos',
            'clientes',
            'cajaAbierta'
        ));
    }

    public function searchProducts(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        if ($search !== '' && mb_strlen($search) < 2 && ! ctype_digit($search)) {
            return response()->json([]);
        }

        $sucursalId = (int) auth()->user()->sucursal_id;
        $cacheKey = 'pos_search:v2:'.$sucursalId.':'.md5(mb_strtolower($search));

        return response()->json(
            Cache::remember($cacheKey, now()->addSeconds(8), function () use ($search) {
                return $this->availableProductsForSale($search, 30)->values()->all();
            })
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        /*
|--------------------------------------------------------------------------
| VALIDAR CAJA ABIERTA
|--------------------------------------------------------------------------
*/

$caja = Caja::where('sucursal_id', auth()->user()->sucursal_id)
    ->where('estado', 'ABIERTA')
    ->latest()
    ->first();

if (!$caja) {

    return redirect()
        ->back()
        ->withInput()
        ->with('error', 'Debe existir una caja abierta para tu sucursal antes de vender.');
}

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

        DB::transaction(function () use ($request, $caja) {

            $user = auth()->user();

            $subtotalGeneral = 0;
            $productosProcesados = [];

            /*
            |--------------------------------------------------------------------------
            | VALIDAR STOCK Y CALCULAR
            |--------------------------------------------------------------------------
            */

            foreach ($request->productos as $item) {

                $inventario = Inventario::where('producto_id', $item['producto_id'])
                    ->where('sucursal_id', $user->sucursal_id)
                    ->first();

                if (!$inventario) {
                    abort(400, 'No existe inventario para un producto.');
                }
                
                $producto = Producto::findOrFail($item['producto_id']);

if ($inventario->existencia < $item['cantidad']) {

    $nombreProducto = trim((string) $inventario->nombre_local) !== ''
        ? $inventario->nombre_local
        : $producto->nombre;

    abort(400, 'Stock insuficiente para el producto: ' . $nombreProducto);
}

                $subtotal = $producto->precio_venta * $item['cantidad'];

                $subtotalGeneral += $subtotal;

                $productosProcesados[] = [
                    'producto' => $producto,
                    'inventario' => $inventario,
                    'cantidad' => $item['cantidad'],
                    'subtotal' => $subtotal,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | CREAR VENTA
            |--------------------------------------------------------------------------
            */

            $venta = Venta::create([
                'sucursal_id' => $user->sucursal_id,
                'user_id' => $user->id,
                'cliente_id' => $request->cliente_id,
                'numero_factura' => 'FAC-' . time(),
                'subtotal' => $subtotalGeneral,
                'descuento' => 0,
                'total' => $subtotalGeneral,
                'estado' => 'FINALIZADA',
            ]);

            /*
            |--------------------------------------------------------------------------
            | DETALLES + INVENTARIO + KARDEX
            |--------------------------------------------------------------------------
            */

            foreach ($productosProcesados as $item) {

                $producto = $item['producto'];
                $inventario = $item['inventario'];
                $cantidad = $item['cantidad'];
                $subtotal = $item['subtotal'];

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal' => $subtotal,
                ]);

                $existenciaAnterior = $inventario->existencia;
                $existenciaNueva = $existenciaAnterior - $cantidad;

                $inventario->update([
                    'existencia' => $existenciaNueva,
                ]);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'sucursal_id' => $user->sucursal_id,
                    'user_id' => $user->id,
                    'tipo_movimiento' => 'VENTA',
                    'cantidad' => $cantidad,
                    'existencia_anterior' => $existenciaAnterior,
                    'existencia_nueva' => $existenciaNueva,
                    'referencia' => $venta->numero_factura,
                    'observacion' => 'Venta POS',
                ]);

                /*
|--------------------------------------------------------------------------
| MOVIMIENTO CAJA
|--------------------------------------------------------------------------
*/

MovimientoCaja::create([

    'caja_id' => $caja->id,

    'user_id' => $user->id,

    'tipo' => 'VENTA',

    'monto' => $subtotal,

    'referencia' => $venta->numero_factura,

    'descripcion' => 'Venta POS',

]);
            }
        });

        return redirect()
            ->route('ventas.index')
            ->with('success', 'Venta realizada correctamente.');
    }

    public function show(Venta $venta)
    {
        $this->authorizeSucursalAccess($venta->sucursal_id);

        $venta->load([
            'usuario',
            'sucursal',
            'cliente',
            'detalles.producto.inventarios' => fn ($query) => $query->where('sucursal_id', $venta->sucursal_id),
            'anulador',
        ]);

        return view('ventas.show', compact('venta'));
    }

    public function anular(Request $request, Venta $venta)
    {
        $this->authorizeSucursalAccess($venta->sucursal_id);

        $data = $request->validate([
            'motivo_anulacion' => 'required|string|min:5|max:500',
        ]);

        if ($venta->estado === 'ANULADA') {
            return redirect()
                ->route('ventas.show', $venta)
                ->with('error', 'Esta venta ya fue anulada anteriormente.');
        }

        DB::transaction(function () use ($venta, $data) {
            $venta->load('detalles');

            $caja = Caja::whereHas('movimientos', function ($query) use ($venta) {
                $query->where('referencia', $venta->numero_factura)
                    ->where('tipo', 'VENTA');
            })->lockForUpdate()->first();

            if (! $caja) {
                throw ValidationException::withMessages([
                    'venta' => 'No se encontro la caja asociada a esta venta.',
                ]);
            }

            if ($caja->estado !== 'ABIERTA') {
                throw ValidationException::withMessages([
                    'venta' => 'No se puede anular una venta cuya caja ya fue cerrada.',
                ]);
            }

            foreach ($venta->detalles as $detalle) {
                $inventario = Inventario::where('producto_id', $detalle->producto_id)
                    ->where('sucursal_id', $venta->sucursal_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventario) {
                    throw ValidationException::withMessages([
                        'venta' => 'No existe inventario para revertir un producto de la venta.',
                    ]);
                }

                $existenciaAnterior = $inventario->existencia;
                $existenciaNueva = $existenciaAnterior + $detalle->cantidad;

                $inventario->update([
                    'existencia' => $existenciaNueva,
                ]);

                MovimientoInventario::create([
                    'producto_id' => $detalle->producto_id,
                    'sucursal_id' => $venta->sucursal_id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => 'DEVOLUCION_CLIENTE',
                    'cantidad' => $detalle->cantidad,
                    'existencia_anterior' => $existenciaAnterior,
                    'existencia_nueva' => $existenciaNueva,
                    'referencia' => $venta->numero_factura,
                    'observacion' => 'Anulacion de venta: ' . $data['motivo_anulacion'],
                ]);
            }

            MovimientoCaja::create([
                'caja_id' => $caja->id,
                'user_id' => auth()->id(),
                'tipo' => 'AJUSTE',
                'monto' => $venta->total,
                'referencia' => $venta->numero_factura,
                'descripcion' => 'Anulacion de venta: ' . $data['motivo_anulacion'],
            ]);

            $venta->update([
                'estado' => 'ANULADA',
                'anulada_por' => auth()->id(),
                'fecha_anulacion' => now(),
                'motivo_anulacion' => $data['motivo_anulacion'],
                'observacion' => trim(($venta->observacion ? $venta->observacion . "\n" : '') . 'Anulada: ' . $data['motivo_anulacion']),
            ]);
        });

        return redirect()
            ->route('ventas.show', $venta)
            ->with('success', 'Venta anulada correctamente. Stock y caja fueron revertidos.');
    }

    private function authorizeSucursalAccess(?int $sucursalId): void
    {
        abort_unless(auth()->user()->canAccessSucursal($sucursalId), 403);
    }

    private function availableProductsForSale(string $search = '', int $limit = 30)
    {
        $sucursalId = auth()->user()->sucursal_id;

        if (! $sucursalId) {
            return collect();
        }

        $normalizedSearch = mb_strtolower($search);

        return Producto::query()
            ->join('inventarios', 'inventarios.producto_id', '=', 'productos.id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->where('productos.estado', true)
            ->where('inventarios.sucursal_id', $sucursalId)
            ->where('inventarios.existencia', '>', 0)
            ->when($normalizedSearch !== '', function ($query) use ($normalizedSearch) {
                $like = "%{$normalizedSearch}%";

                $query->where(function ($subquery) use ($like) {
                    $subquery
                        ->whereRaw('LOWER(productos.nombre) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(inventarios.nombre_local, \'\')) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(productos.codigo_barra) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(productos.laboratorio, \'\')) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(categorias.nombre, \'\')) LIKE ?', [$like]);
                });
            })
            ->select([
                'productos.id',
                'productos.nombre',
                'productos.codigo_barra',
                'productos.precio_venta',
                'inventarios.nombre_local',
                'inventarios.existencia as inventario_actual',
            ])
            ->orderByRaw("LOWER(COALESCE(NULLIF(inventarios.nombre_local, ''), productos.nombre))")
            ->limit($limit)
            ->get()
            ->map(fn ($producto) => [
                'id' => (string) $producto->id,
                'nombre' => trim((string) $producto->nombre_local) !== '' ? $producto->nombre_local : $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'precio_venta' => (float) $producto->precio_venta,
                'inventario_actual' => (int) $producto->inventario_actual,
            ]);
    }
}
