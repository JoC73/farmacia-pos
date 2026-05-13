<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::latest()
            ->paginate(20);

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $request->validate([

            'nit' => 'nullable|max:30|unique:clientes,nit',

            'nombre' => 'required|max:150',

            'telefono' => 'nullable|max:30',

            'direccion' => 'nullable|max:255',

        ]);

        Cliente::create([

            'nit' => $request->nit,

            'nombre' => $request->nombre,

            'telefono' => $request->telefono,

            'direccion' => $request->direccion,

            'estado' => true,

        ]);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Cliente $cliente)
    {
        return redirect()->route('clientes.index');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([

            'nit' => 'nullable|max:30|unique:clientes,nit,' . $cliente->id,

            'nombre' => 'required|max:150',

            'telefono' => 'nullable|max:30',

            'direccion' => 'nullable|max:255',

        ]);

        $cliente->update([

            'nit' => $request->nit,

            'nombre' => $request->nombre,

            'telefono' => $request->telefono,

            'direccion' => $request->direccion,

            'estado' => true,

        ]);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->update([
            'estado' => false,
        ]);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente desactivado correctamente.');
    }
}