<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::latest()
            ->paginate(20);

        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $request->validate([

            'nombre' => 'required|max:150',

            'nit' => 'nullable|max:30|unique:proveedores,nit',

            'telefono' => 'nullable|max:30',

            'direccion' => 'nullable|max:255',

            'estado' => 'nullable|boolean',

        ]);

        Proveedor::create([

            'nombre' => $request->nombre,

            'nit' => $request->nit,

            'telefono' => $request->telefono,

            'direccion' => $request->direccion,

            'estado' => $request->has('estado'),

        ]);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Proveedor $proveedor)
    {
        return redirect()->route('proveedores.index');
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $request->validate([

            'nombre' => 'required|max:150',

            'nit' => 'nullable|max:30|unique:proveedores,nit,' . $proveedor->id,

            'telefono' => 'nullable|max:30',

            'direccion' => 'nullable|max:255',

        ]);

        $proveedor->update([

            'nombre' => $request->nombre,

            'nit' => $request->nit,

            'telefono' => $request->telefono,

            'direccion' => $request->direccion,

            'estado' => true,

        ]);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor)
    {
        $proveedor->update([
            'estado' => false,
        ]);

        return redirect()
            ->route('proveedores.index')
            ->with('success', "Proveedor {$proveedor->nombre} desactivado correctamente.");
    }
}
