<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CajaTransferenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_transferencia_usa_ventas_del_mes_mas_apertura(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'FarmaVida Mazate',
            'estado' => true,
        ]);

        $user = User::factory()->create()->forceFill([
            'sucursal_id' => $sucursal->id,
            'estado' => true,
        ]);
        $user->save();
        $this->giveSalesPermission($user);

        $caja = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 100,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-TEST-1',
            'subtotal' => 3627.25,
            'descuento' => 0,
            'total' => 3627.25,
            'estado' => 'FINALIZADA',
        ]);

        $this->assertTrue($user->fresh()->canAccessSucursal($caja->sucursal_id));
        $this->assertTrue($user->fresh()->can('ventas.crear'));
        $this->assertSame('ABIERTA', $caja->fresh()->estado);

        $response = $this
            ->actingAs($user)
            ->post(route('cajas.transferencia.store', $caja), [
                'referencia' => 'BOLETA-1',
                'fecha_movimiento' => now()->format('Y-m-d H:i:s'),
                'monto' => 3727.25,
                'descripcion' => 'Transferencia mensual',
            ]);

        $response->assertRedirect(route('cajas.index'));

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => 3727.25,
        ]);
    }

    public function test_transferencia_no_supera_disponible_mensual(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'FarmaVida Mazate',
            'estado' => true,
        ]);

        $user = User::factory()->create()->forceFill([
            'sucursal_id' => $sucursal->id,
            'estado' => true,
        ]);
        $user->save();
        $this->giveSalesPermission($user);

        $caja = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 100,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-TEST-2',
            'subtotal' => 100,
            'descuento' => 0,
            'total' => 100,
            'estado' => 'FINALIZADA',
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => 50,
            'fecha_movimiento' => now(),
            'referencia' => 'BOLETA-PREVIA',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('cajas.transferencia', $caja))
            ->post(route('cajas.transferencia.store', $caja), [
                'referencia' => 'BOLETA-2',
                'fecha_movimiento' => now()->format('Y-m-d H:i:s'),
                'monto' => 151,
                'descripcion' => 'Transferencia mayor',
            ]);

        $response
            ->assertRedirect(route('cajas.transferencia', $caja))
            ->assertSessionHasErrors('monto');
    }

    public function test_transferencia_no_toma_montos_de_otra_sucursal(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'FarmaVida Mazate',
            'estado' => true,
        ]);

        $otraSucursal = Sucursal::create([
            'nombre' => 'Otra Sucursal',
            'estado' => true,
        ]);

        $user = User::factory()->create()->forceFill([
            'sucursal_id' => $sucursal->id,
            'estado' => true,
        ]);
        $user->save();
        $this->giveSalesPermission($user);

        $otroUser = User::factory()->create()->forceFill([
            'sucursal_id' => $otraSucursal->id,
            'estado' => true,
        ]);
        $otroUser->save();

        $caja = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 100,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        $otraCaja = Caja::create([
            'sucursal_id' => $otraSucursal->id,
            'user_id' => $otroUser->id,
            'monto_apertura' => 500,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-SUCURSAL-1',
            'subtotal' => 3627.25,
            'descuento' => 0,
            'total' => 3627.25,
            'estado' => 'FINALIZADA',
        ]);

        Venta::create([
            'sucursal_id' => $otraSucursal->id,
            'user_id' => $otroUser->id,
            'numero_factura' => 'FAC-OTRA-1',
            'subtotal' => 9999.99,
            'descuento' => 0,
            'total' => 9999.99,
            'estado' => 'FINALIZADA',
        ]);

        MovimientoCaja::create([
            'caja_id' => $otraCaja->id,
            'user_id' => $otroUser->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => 3000,
            'fecha_movimiento' => now(),
            'referencia' => 'OTRA-SUCURSAL',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('cajas.transferencia.store', $caja), [
                'referencia' => 'BOLETA-AISLADA',
                'fecha_movimiento' => now()->format('Y-m-d H:i:s'),
                'monto' => 3727.25,
                'descripcion' => 'Transferencia mensual aislada',
            ]);

        $response->assertRedirect(route('cajas.index'));

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => 3727.25,
        ]);
    }

    public function test_cierre_usa_disponible_despues_de_transferencia_mensual(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'FarmaVida Mazate',
            'estado' => true,
        ]);

        $user = User::factory()->create()->forceFill([
            'sucursal_id' => $sucursal->id,
            'estado' => true,
        ]);
        $user->save();
        $this->giveSalesPermission($user);

        $caja = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 100,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-CIERRE-1',
            'subtotal' => 3627.25,
            'descuento' => 0,
            'total' => 3627.25,
            'estado' => 'FINALIZADA',
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => 1100,
            'fecha_movimiento' => now(),
            'referencia' => 'BOLETA-1100',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('cajas.cierre.store', $caja), [
                'monto_cierre' => 2627.25,
                'observacion' => 'Cierre despues de transferencia',
            ]);

        $response->assertRedirect(route('cajas.index'));

        $this->assertDatabaseHas('cajas', [
            'id' => $caja->id,
            'estado' => 'CERRADA',
            'monto_cierre' => 2627.25,
            'total_sistema' => 2627.25,
            'diferencia' => 0,
        ]);
    }

    private function giveSalesPermission(User $user): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['ventas.crear', 'caja.cerrar'] as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $user->givePermissionTo($permission);
        }
    }
}
