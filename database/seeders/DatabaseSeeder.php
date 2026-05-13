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

        $adminEmail = env('ADMIN_EMAIL', 'admin@farmacia.com');
        $adminPassword = env('ADMIN_PASSWORD', '123456');
        $adminName = env('ADMIN_NAME', 'Admin');

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
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'sucursal_id' => $sucursal->id,
                'estado' => true
            ]
        );

        $user->forceFill([
            'sucursal_id' => $user->sucursal_id ?: $sucursal->id,
            'estado' => true,
        ])->save();

        $user->assignRole($role);
    }
}
