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
        $sucursalId = auth()->user()->visibleSucursalId();

        $usuarios = User::with([
            'sucursal',
            'roles'
        ])
        ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
        ->latest()
        ->paginate(20);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $sucursales = $this->sucursalesPermitidas();

        $roles = Role::orderBy('name')
            ->when(! auth()->user()->hasRole('Super Usuario'), fn ($query) => $query->whereNotIn('name', $this->superOnlyRoles()))
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

        if (in_array($request->rol, $this->superOnlyRoles(), true) && ! auth()->user()->hasRole('Super Usuario')) {
            return back()
                ->withInput()
                ->withErrors(['rol' => 'No tienes autorizacion para asignar este rol.']);
        }

        $this->authorizeSucursalAccess((int) $request->sucursal_id);

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
        $this->authorizeSucursalAccess($usuario->sucursal_id);

        $sucursales = $this->sucursalesPermitidas();

        $roles = Role::orderBy('name')
            ->when(! auth()->user()->hasRole('Super Usuario'), fn ($query) => $query->whereNotIn('name', $this->superOnlyRoles()))
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
            (in_array($request->rol, $this->superOnlyRoles(), true) || $usuario->hasAnyRole($this->superOnlyRoles()))
            && ! auth()->user()->hasRole('Super Usuario')
        ) {
            return back()
                ->withInput()
                ->withErrors(['rol' => 'No tienes autorizacion para modificar usuarios con este rol.']);
        }

        $this->authorizeSucursalAccess($usuario->sucursal_id);
        $this->authorizeSucursalAccess((int) $request->sucursal_id);

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
        $this->authorizeSucursalAccess($usuario->sucursal_id);

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

    private function sucursalesPermitidas()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        return Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();
    }

    private function authorizeSucursalAccess(?int $sucursalId): void
    {
        abort_unless(auth()->user()->canAccessSucursal($sucursalId), 403);
    }

    private function superOnlyRoles(): array
    {
        return ['Super Usuario', 'Administrador Global'];
    }
}
