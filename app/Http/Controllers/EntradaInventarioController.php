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

        $productos = $this->availableProductsForEntry('', 18);

        $sucursales = Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();

        return view('inventarios.entrada', compact(
            'productos',
            'sucursales'
        ));
    }

    public function searchProducts(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        if ($search !== '' && mb_strlen($search) < 2) {
            return response()->json([]);
        }

        return response()->json($this->availableProductsForEntry($search, 30));
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

    private function availableProductsForEntry(string $search = '', int $limit = 30)
    {
        $normalizedSearch = mb_strtolower($search);

        return Producto::query()
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->where('productos.estado', true)
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
            ])
            ->orderByRaw('LOWER(productos.nombre)')
            ->limit($limit)
            ->get()
            ->map(fn ($producto) => [
                'id' => (string) $producto->id,
                'nombre' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
            ]);
    }
}
