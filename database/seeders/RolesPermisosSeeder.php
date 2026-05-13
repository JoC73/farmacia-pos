<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'dashboard.ver',

            'sucursales.ver',
            'sucursales.crear',
            'sucursales.editar',
            'sucursales.eliminar',

            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',
            'permisos.asignar',

            'productos.ver',
            'productos.crear',
            'productos.editar',
            'productos.eliminar',
            'productos.cambiar_precio',

            'inventario.ver',
            'inventario.ajustar',
            'inventario.kardex',
            'inventario.stock_bajo',
            'inventario.vencimientos',

            'compras.ver',
            'compras.crear',
            'compras.editar',
            'compras.eliminar',

            'ventas.ver',
            'ventas.crear',
            'ventas.anular',
            'ventas.descuento',
            'ventas.reimprimir_ticket',

            'caja.abrir',
            'caja.cerrar',
            'caja.ver_cierres',
            'caja.ver_todas',

            'reportes.ventas',
            'reportes.ganancias',
            'reportes.inventario',
            'reportes.compras',
            'reportes.caja',

            'configuracion.ver',
            'configuracion.editar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'Administrador',
            'guard_name' => 'web',
        ]);

        $cajero = Role::firstOrCreate([
            'name' => 'Cajero',
            'guard_name' => 'web',
        ]);

        $inventario = Role::firstOrCreate([
            'name' => 'Encargado Inventario',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions(Permission::all());

        $cajero->syncPermissions([
            'dashboard.ver',
            'ventas.ver',
            'ventas.crear',
            'ventas.reimprimir_ticket',
            'productos.ver',
            'inventario.ver',
            'caja.abrir',
            'caja.cerrar',
        ]);

        $inventario->syncPermissions([
            'dashboard.ver',
            'productos.ver',
            'productos.crear',
            'productos.editar',
            'inventario.ver',
            'inventario.ajustar',
            'inventario.kardex',
            'inventario.stock_bajo',
            'inventario.vencimientos',
            'compras.ver',
            'compras.crear',
        ]);
    }
}