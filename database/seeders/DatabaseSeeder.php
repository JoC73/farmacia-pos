<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ejecutar roles y permisos
        $this->call(RolesPermisosSeeder::class);

        // Crear sucursal
        $sucursal = Sucursal::firstOrCreate(
            ['nombre' => 'Farmacia Central'],
            [
                'direccion' => 'Zona 1',
                'telefono' => '12345678',
                'estado' => true
            ]
        );

        // Crear rol administrador
        $role = Role::firstOrCreate([
            'name' => 'Administrador'
        ]);

        // Crear usuario admin
        $user = User::firstOrCreate(
            ['email' => 'admin@farmacia.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123456'),
                'sucursal_id' => $sucursal->id,
                'estado' => true
            ]
        );

        $user->assignRole($role);
    }
}