<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::orderBy('nombre')->paginate(10);

        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('categorias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:120',
            'descripcion' => 'nullable|string',
        ]);

        $nombre = Categoria::displayName($request->nombre);
        $nombreNormalizado = Categoria::normalizeName($nombre);

        if ($this->categoryNameExists($nombreNormalizado)) {
            return back()
                ->withErrors(['nombre' => 'Ya existe una categoría con ese nombre o una variante equivalente.'])
                ->withInput();
        }

        Categoria::create([
            'nombre' => $nombre,
            'nombre_normalizado' => $nombreNormalizado,
            'descripcion' => $request->descripcion,
            'estado' => true,
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function show(Categoria $categoria)
    {
        return redirect()->route('categorias.index');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:120',
            'descripcion' => 'nullable|string',
            'estado' => 'nullable|boolean',
        ]);

        $nombre = Categoria::displayName($request->nombre);
        $nombreNormalizado = Categoria::normalizeName($nombre);

        if ($this->categoryNameExists($nombreNormalizado, $categoria->id)) {
            return back()
                ->withErrors(['nombre' => 'Ya existe una categoría con ese nombre o una variante equivalente.'])
                ->withInput();
        }

        $categoria->update([
            'nombre' => $nombre,
            'nombre_normalizado' => $nombreNormalizado,
            'descripcion' => $request->descripcion,
            'estado' => $request->has('estado'),
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->update([
            'estado' => false,
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría desactivada correctamente.');
    }

    private function categoryNameExists(string $normalizedName, ?int $exceptId = null): bool
    {
        return Categoria::where('nombre_normalizado', $normalizedName)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
