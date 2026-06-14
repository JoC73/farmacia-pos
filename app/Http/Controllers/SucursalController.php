<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $sucursales = Sucursal::latest()
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->paginate(20);

        return view('sucursales.index', compact('sucursales'));
    }

    public function create()
    {
        return view('sucursales.create');
    }

    public function store(Request $request)
    {
        $request->validate([

            'nombre' => 'required|max:150|unique:sucursales,nombre',

            'direccion' => 'nullable|max:255',

            'telefono' => 'nullable|max:30',

        ]);

        Sucursal::create([

            'nombre' => $request->nombre,

            'direccion' => $request->direccion,

            'telefono' => $request->telefono,

            'estado' => true,

        ]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal creada correctamente.');
    }

    public function show(Sucursal $sucursal)
    {
        return redirect()->route('sucursales.index');
    }

    public function edit(Sucursal $sucursal)
    {
        $this->authorizeSucursalAccess($sucursal);

        return view('sucursales.edit', compact('sucursal'));
    }

    public function update(Request $request, Sucursal $sucursal)
    {
        $this->authorizeSucursalAccess($sucursal);

        $request->validate([

            'nombre' => 'required|max:150|unique:sucursales,nombre,' . $sucursal->id,

            'direccion' => 'nullable|max:255',

            'telefono' => 'nullable|max:30',

        ]);

        $sucursal->update([

            'nombre' => $request->nombre,

            'direccion' => $request->direccion,

            'telefono' => $request->telefono,

            'estado' => $request->has('estado'),

        ]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Sucursal $sucursal)
    {
        $this->authorizeSucursalAccess($sucursal);

        $sucursal->update([
            'estado' => ! $sucursal->estado,
        ]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', $sucursal->estado
                ? 'Sucursal reactivada correctamente.'
                : 'Sucursal inhabilitada correctamente.');
    }

    private function authorizeSucursalAccess(Sucursal $sucursal): void
    {
        abort_unless(auth()->user()->canAccessSucursal($sucursal->id), 403);
    }
}
