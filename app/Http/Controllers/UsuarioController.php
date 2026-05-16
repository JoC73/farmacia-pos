<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sucursal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with([
            'sucursal',
            'roles'
        ])
        ->latest()
        ->paginate(20);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $sucursales = Sucursal::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $roles = Role::orderBy('name')
            ->when(! auth()->user()->hasRole('Super Usuario'), fn ($query) => $query->where('name', '!=', 'Super Usuario'))
            ->get();

        return view('usuarios.create', compact(
            'sucursales',
            'roles'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([

            'name' => 'required|max:150',

            'email' => 'required|email|unique:users,email',

            'password' => 'required|min:6|confirmed',

            'sucursal_id' => 'required|exists:sucursales,id',

            'rol' => 'required|exists:roles,name',

        ]);

        if ($request->rol === 'Super Usuario' && ! auth()->user()->hasRole('Super Usuario')) {
            return back()
                ->withInput()
                ->withErrors(['rol' => 'No tienes autorizacion para asignar el rol Super Usuario.']);
        }

        $usuario = User::create([

            'name' => $request->name,

            'email' => $request->email,

            'password' => Hash::make($request->password),

            'sucursal_id' => $request->sucursal_id,

        ]);

        $usuario->assignRole($request->rol);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $usuario)
    {
        return redirect()->route('usuarios.index');
    }

    public function edit(User $usuario)
    {
        $sucursales = Sucursal::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $roles = Role::orderBy('name')
            ->when(! auth()->user()->hasRole('Super Usuario'), fn ($query) => $query->where('name', '!=', 'Super Usuario'))
            ->get();

        return view('usuarios.edit', compact(
            'usuario',
            'sucursales',
            'roles'
        ));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([

            'name' => 'required|max:150',

            'email' => 'required|email|unique:users,email,' . $usuario->id,

            'sucursal_id' => 'required|exists:sucursales,id',

            'rol' => 'required|exists:roles,name',

        ]);

        if (
            ($request->rol === 'Super Usuario' || $usuario->hasRole('Super Usuario'))
            && ! auth()->user()->hasRole('Super Usuario')
        ) {
            return back()
                ->withInput()
                ->withErrors(['rol' => 'No tienes autorizacion para modificar usuarios Super Usuario.']);
        }

        $usuario->update([

            'name' => $request->name,

            'email' => $request->email,

            'sucursal_id' => $request->sucursal_id,

        ]);

        if ($request->filled('password')) {

            $usuario->update([

                'password' => Hash::make($request->password),

            ]);
        }

        $usuario->syncRoles([$request->rol]);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {

            return redirect()
                ->route('usuarios.index')
                ->with('error', 'No puedes desactivarte.');
        }

        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado.');
    }
}
