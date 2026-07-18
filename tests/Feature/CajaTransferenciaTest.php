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

    public function test_transferencia_usa_ventas_de_la_caja_mas_apertura(): void
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

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => 3627.25,
            'fecha_movimiento' => now(),
            'referencia' => 'FAC-TEST-1',
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

    public function test_transferencia_no_supera_disponible_de_la_caja(): void
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
            'tipo' => 'VENTA',
            'monto' => 100,
            'fecha_movimiento' => now(),
            'referencia' => 'FAC-TEST-2',
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

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => 3627.25,
            'fecha_movimiento' => now(),
            'referencia' => 'FAC-SUCURSAL-1',
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
            'tipo' => 'VENTA',
            'monto' => 9999.99,
            'fecha_movimiento' => now(),
            'referencia' => 'FAC-OTRA-1',
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

    public function test_cierre_usa_disponible_despues_de_transferencia_de_caja(): void
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
            'tipo' => 'VENTA',
            'monto' => 3627.25,
            'fecha_movimiento' => now(),
            'referencia' => 'FAC-CIERRE-1',
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

    public function test_tres_sucursales_calculan_su_caja_de_forma_independiente(): void
    {
        $farmavida = $this->createBranchCashScenario('FarmaVida', 100, 123.50, 20);
        $tinajon = $this->createBranchCashScenario('El Tinajon', 829.50, 919.50, 100);
        $garibaldi = $this->createBranchCashScenario('Garibaldi', 0, 0, 0);

        $this
            ->actingAs($farmavida['user'])
            ->get(route('cajas.show', $farmavida['caja']))
            ->assertOk()
            ->assertSee('Q 203.50');

        $this
            ->actingAs($tinajon['user'])
            ->get(route('cajas.show', $tinajon['caja']))
            ->assertOk()
            ->assertSee('Q 1,649.00');

        $this
            ->actingAs($garibaldi['user'])
            ->get(route('cajas.show', $garibaldi['caja']))
            ->assertOk()
            ->assertSee('Q 0.00');
    }

    public function test_cajas_cerradas_muestran_total_y_diferencia_conciliados(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Farmacia Familiar M&C El Tinajon',
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
            'monto_cierre' => 868.25,
            'total_sistema' => 868.25,
            'diferencia' => 0,
            'fecha_apertura' => now()->subDay(),
            'fecha_cierre' => now(),
            'estado' => 'CERRADA',
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => 1323.25,
            'fecha_movimiento' => now(),
            'referencia' => 'VENTAS-TINAJON',
        ]);

        $this
            ->actingAs($user)
            ->get(route('cajas.index'))
            ->assertOk()
            ->assertSee('Q 868.25')
            ->assertSee('Q 0.00')
            ->assertDontSee('Q 1,323.25')
            ->assertDontSee('Q -455.00');
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

    private function createBranchCashScenario(string $name, float $apertura, float $ventas, float $transferencias): array
    {
        $sucursal = Sucursal::create([
            'nombre' => $name,
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
            'monto_apertura' => $apertura,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        if ($ventas > 0) {
            MovimientoCaja::create([
                'caja_id' => $caja->id,
                'user_id' => $user->id,
                'tipo' => 'VENTA',
                'monto' => $ventas,
                'fecha_movimiento' => now(),
                'referencia' => 'VENTA-' . $name,
            ]);
        }

        if ($transferencias > 0) {
            MovimientoCaja::create([
                'caja_id' => $caja->id,
                'user_id' => $user->id,
                'tipo' => 'TRANSFERENCIA_JEFE',
                'monto' => $transferencias,
                'fecha_movimiento' => now(),
                'referencia' => 'TRANSFERENCIA-' . $name,
            ]);
        }

        return compact('sucursal', 'user', 'caja');
    }
}
