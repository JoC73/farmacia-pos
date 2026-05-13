<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')
            ->orderBy('nombre')
            ->paginate(10);

        return view('productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
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
        $producto->update([
            'estado' => false,
        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto desactivado correctamente.');
    }
}