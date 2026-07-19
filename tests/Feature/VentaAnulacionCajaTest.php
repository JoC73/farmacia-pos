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

    public function test_migracion_repara_venta_anulada_historica_en_caja_cerrada_farmavida(): void
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

        $caja = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 100,
            'monto_cierre' => 181,
            'total_sistema' => 181,
            'diferencia' => 0,
            'fecha_apertura' => now()->subDay(),
            'fecha_cierre' => now(),
            'estado' => 'CERRADA',
        ]);

        $venta = Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-1784417085',
            'subtotal' => 81,
            'descuento' => 0,
            'total' => 81,
            'estado' => 'ANULADA',
            'anulada_por' => $user->id,
            'fecha_anulacion' => now(),
            'motivo_anulacion' => 'Anulacion historica',
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => 81,
            'fecha_movimiento' => now()->subDay(),
            'referencia' => $venta->numero_factura,
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'CIERRE',
            'monto' => 181,
            'fecha_movimiento' => now(),
            'referencia' => 'CIERRE-HISTORICO',
        ]);

        $cajaSiguiente = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 181,
            'fecha_apertura' => now()->addDay(),
            'estado' => 'ABIERTA',
        ]);

        MovimientoCaja::create([
            'caja_id' => $cajaSiguiente->id,
            'user_id' => $user->id,
            'tipo' => 'APERTURA',
            'monto' => 181,
            'fecha_movimiento' => now()->addDay(),
            'referencia' => 'APERTURA-HEREDADA',
        ]);

        $migration = require database_path('migrations/2026_07_19_000001_repair_farmavida_annulled_sale_cash_refund.php');
        $migration->up();

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'EGRESO',
            'monto' => 81,
            'referencia' => 'FAC-1784417085',
        ]);

        $this->assertDatabaseHas('cajas', [
            'id' => $caja->id,
            'monto_cierre' => 100,
            'total_sistema' => 100,
            'diferencia' => 0,
        ]);

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'CIERRE',
            'monto' => 100,
        ]);

        $this->assertDatabaseHas('cajas', [
            'id' => $cajaSiguiente->id,
            'monto_apertura' => 100,
        ]);

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $cajaSiguiente->id,
            'tipo' => 'APERTURA',
            'monto' => 100,
        ]);
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
