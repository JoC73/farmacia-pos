<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolPermisoController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')
            ->when(! auth()->user()->hasRole('Super Usuario'), fn ($query) => $query->where('name', '!=', 'Super Usuario'))
            ->latest()
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')
            ->get();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        if ($request->name === 'Super Usuario' && ! auth()->user()->hasRole('Super Usuario')) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'No tienes autorizacion para crear el rol Super Usuario.']);
        }

        $role = Role::create([
            'name' => $request->name,
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol creado correctamente.');
    }

    public function edit(Role $role)
    {
        if ($role->name === 'Super Usuario' && ! auth()->user()->hasRole('Super Usuario')) {
            abort(403);
        }

        $permissions = Permission::orderBy('name')
            ->get();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
        ]);

        if (
            ($role->name === 'Super Usuario' || $request->name === 'Super Usuario')
            && ! auth()->user()->hasRole('Super Usuario')
        ) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'No tienes autorizacion para modificar el rol Super Usuario.']);
        }

        $role->update([
            'name' => $request->name,
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol actualizado.');
    }
}
