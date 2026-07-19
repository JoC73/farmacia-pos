<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VentaAnulacionCajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_anular_venta_registra_egreso_y_resta_disponible_de_caja(): void
    {
        [$user, $caja, $venta, $inventario] = $this->createSaleScenario();

        $this
            ->actingAs($user)
            ->post(route('ventas.anular', $venta), [
                'motivo_anulacion' => 'Cliente devolvio producto',
            ])
            ->assertRedirect(route('ventas.show', $venta));

        $this->assertDatabaseHas('ventas', [
            'id' => $venta->id,
            'estado' => 'ANULADA',
            'anulada_por' => $user->id,
        ]);

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'EGRESO',
            'monto' => 100,
            'referencia' => $venta->numero_factura,
        ]);

        $this->assertDatabaseMissing('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'AJUSTE',
            'referencia' => $venta->numero_factura,
        ]);

        $this->assertSame(10, $inventario->fresh()->existencia);

        $this
            ->actingAs($user)
            ->post(route('cajas.cierre.store', $caja), [
                'monto_cierre' => 0,
                'observacion' => 'Cierre tras anulacion',
            ])
            ->assertRedirect(route('cajas.index'));

        $this->assertDatabaseHas('cajas', [
            'id' => $caja->id,
            'monto_cierre' => 0,
            'total_sistema' => 0,
            'diferencia' => 0,
        ]);
    }

    public function test_anular_venta_no_permite_dejar_caja_en_negativo(): void
    {
        [$user, $caja, $venta, $inventario] = $this->createSaleScenario();

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => 100,
            'fecha_movimiento' => now(),
            'referencia' => 'TRANSFERENCIA-TOTAL',
        ]);

        $this
            ->actingAs($user)
            ->from(route('ventas.show', $venta))
            ->post(route('ventas.anular', $venta), [
                'motivo_anulacion' => 'Cliente devolvio producto',
            ])
            ->assertRedirect(route('ventas.show', $venta))
            ->assertSessionHasErrors('venta');

        $this->assertDatabaseHas('ventas', [
            'id' => $venta->id,
            'estado' => 'FINALIZADA',
        ]);

        $this->assertDatabaseMissing('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'EGRESO',
            'referencia' => $venta->numero_factura,
        ]);

        $this->assertSame(8, $inventario->fresh()->existencia);
    }

    private function createSaleScenario(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $sucursal = Sucursal::create([
            'nombre' => 'FarmaVida Mazate',
            'estado' => true,
        ]);

        $user = User::factory()->create()->forceFill([
            'sucursal_id' => $sucursal->id,
            'estado' => true,
        ]);
        $user->save();

        $role = Role::firstOrCreate([
            'name' => 'Administrador',
            'guard_name' => 'web',
        ]);

        foreach (['ventas.anular', 'ventas.ver', 'caja.cerrar'] as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);

        $producto = Producto::create([
            'codigo_barra' => 'ANULA-1',
            'nombre' => 'Producto de prueba',
            'precio_venta' => 50,
            'estado' => true,
        ]);

        $inventario = Inventario::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $sucursal->id,
            'existencia' => 8,
            'activo' => true,
        ]);

        $caja = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 0,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        $venta = Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-ANULA-1',
            'subtotal' => 100,
            'descuento' => 0,
            'total' => 100,
            'estado' => 'FINALIZADA',
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 50,
            'subtotal' => 100,
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => 100,
            'fecha_movimiento' => now(),
            'referencia' => $venta->numero_factura,
        ]);

        return [$user, $caja, $venta, $inventario];
    }
}
