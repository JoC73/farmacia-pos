<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $sucursalId = auth()->user()->visibleSucursalId();
        $perPage = $this->validPerPage($request->integer('per_page', 50));
        $search = trim((string) $request->input('q', ''));
        $categoriaId = $request->input('categoria_id');

        $productos = Producto::with('categoria')
            ->when($sucursalId, fn ($query) => $query->with([
                'inventarios' => fn ($inventario) => $inventario->where('sucursal_id', $sucursalId),
            ]))
            ->where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereHas(
                'inventarios',
                fn ($inventario) => $inventario->where('sucursal_id', $sucursalId)
            ))
            ->when($search !== '', fn ($query) => $query->where(function ($subquery) use ($search) {
                $subquery
                    ->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo_barra', 'like', "%{$search}%")
                    ->orWhere('laboratorio', 'like', "%{$search}%");
            }))
            ->when($categoriaId, fn ($query) => $query->where('categoria_id', $categoriaId))
            ->ordenadoPorNombre()
            ->paginate($perPage)
            ->withQueryString();

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $canManageGlobalProducts = auth()->user()->hasAnyRole(['Administrador Global', 'Super Usuario']);
        $canAdjustLocalInventory = auth()->user()->hasRole('Administrador')
            && ! $canManageGlobalProducts
            && auth()->user()->can('inventario.ajustar')
            && $sucursalId;

        if ($request->ajax()) {
            return view('productos.partials.results', compact(
                'productos',
                'canManageGlobalProducts',
                'canAdjustLocalInventory'
            ));
        }

        return view('productos.index', compact(
            'productos',
            'categorias',
            'perPage',
            'search',
            'categoriaId',
            'canManageGlobalProducts',
            'canAdjustLocalInventory'
        ));
    }

    public function create()
    {
        $this->authorizeGlobalProductManagement();

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $this->authorizeGlobalProductManagement();

        $request->validate([

            'categoria_id' => 'nullable|exists:categorias,id',

            'codigo_barra' => 'required|string|max:120|unique:productos,codigo_barra',

            'nombre' => 'required|string|max:200',

            'laboratorio' => 'nullable|string|max:150',

            'costo' => 'required|numeric|min:0',

            'precio_venta' => 'required|numeric|min:0',

            'stock_minimo' => 'required|integer|min:0',

            'fecha_vencimiento' => 'nullable|date',

            'descripcion' => 'nullable|string',

        ]);

        Producto::create([

            'categoria_id' => $request->categoria_id,

            'codigo_barra' => $request->codigo_barra,

            'nombre' => $request->nombre,

            'laboratorio' => $request->laboratorio,

            'costo' => $request->costo,

            'precio_venta' => $request->precio_venta,

            'stock_minimo' => $request->stock_minimo,

            'fecha_vencimiento' => $request->fecha_vencimiento,

            'descripcion' => $request->descripcion,

            'estado' => true,

        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function show(Producto $producto)
    {
        return redirect()->route('productos.index');
    }

    public function edit(Producto $producto)
    {
        $this->authorizeGlobalProductManagement();

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.edit', compact(
            'producto',
            'categorias'
        ));
    }

    public function update(Request $request, Producto $producto)
    {
        $this->authorizeGlobalProductManagement();

        $request->validate([

            'categoria_id' => 'nullable|exists:categorias,id',

            'codigo_barra' => 'required|string|max:120|unique:productos,codigo_barra,' . $producto->id,

            'nombre' => 'required|string|max:200',

            'laboratorio' => 'nullable|string|max:150',

            'costo' => 'required|numeric|min:0',

            'precio_venta' => 'required|numeric|min:0',

            'stock_minimo' => 'required|integer|min:0',

            'fecha_vencimiento' => 'nullable|date',

            'descripcion' => 'nullable|string',

            'estado' => 'nullable|boolean',

        ]);

        $producto->update([

            'categoria_id' => $request->categoria_id,

            'codigo_barra' => $request->codigo_barra,

            'nombre' => $request->nombre,

            'laboratorio' => $request->laboratorio,

            'costo' => $request->costo,

            'precio_venta' => $request->precio_venta,

            'stock_minimo' => $request->stock_minimo,

            'fecha_vencimiento' => $request->fecha_vencimiento,

            'descripcion' => $request->descripcion,

            'estado' => $request->has('estado'),

        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $this->authorizeGlobalProductManagement();

        $producto->update([
            'estado' => false,
        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto desactivado correctamente.');
    }

    private function authorizeGlobalProductManagement(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['Administrador Global', 'Super Usuario']), 403);
    }

    private function validPerPage(int $perPage): int
    {
        return in_array($perPage, [25, 50, 100, 200], true) ? $perPage : 50;
    }
}
