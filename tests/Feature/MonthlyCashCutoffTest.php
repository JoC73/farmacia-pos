<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\CorteMensualCaja;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MonthlyCashCutoffTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ventas_se_bloquean_si_hay_corte_mensual_pendiente(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 1, 9, 0, 0, config('app.timezone')));

        [$user] = $this->createSucursalUserWithPermissions();

        $venta = Venta::create([
            'sucursal_id' => $user->sucursal_id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-JULIO-1',
            'subtotal' => 100,
            'descuento' => 0,
            'total' => 100,
            'estado' => 'FINALIZADA',
        ]);
        $venta->forceFill([
            'created_at' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
            'updated_at' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
        ])->save();

        $this
            ->actingAs($user)
            ->get(route('ventas.create'))
            ->assertRedirect(route('cajas.corte-mensual'));
    }

    public function test_corte_mensual_registra_transferencia_y_libera_ventas(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 1, 9, 0, 0, config('app.timezone')));

        [$user] = $this->createSucursalUserWithPermissions();

        $venta = Venta::create([
            'sucursal_id' => $user->sucursal_id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-JULIO-2',
            'subtotal' => 500,
            'descuento' => 0,
            'total' => 500,
            'estado' => 'FINALIZADA',
        ]);
        $venta->forceFill([
            'created_at' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
            'updated_at' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
        ])->save();

        $caja = Caja::create([
            'sucursal_id' => $user->sucursal_id,
            'user_id' => $user->id,
            'monto_apertura' => 1000,
            'fecha_apertura' => Carbon::create(2026, 7, 31, 8, 0, 0, config('app.timezone')),
            'estado' => 'ABIERTA',
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => 500,
            'fecha_movimiento' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
            'referencia' => 'FAC-JULIO-2',
        ]);

        $this
            ->actingAs($user)
            ->post(route('cajas.corte-mensual.store'), [
                'periodo_year' => 2026,
                'periodo_month' => 7,
                'monto_transferido' => 1200,
                'referencia' => 'BOLETA-JULIO',
                'observacion' => 'Corte mensual parcial',
            ])
            ->assertRedirect(route('cajas.index'));

        $this->assertDatabaseHas('corte_mensual_cajas', [
            'sucursal_id' => $user->sucursal_id,
            'caja_id' => $caja->id,
            'periodo_year' => 2026,
            'periodo_month' => 7,
            'disponible_antes' => 1500,
            'monto_transferido' => 1200,
            'saldo_restante' => 300,
        ]);

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => 1200,
            'referencia' => 'BOLETA-JULIO',
        ]);

        $this
            ->actingAs($user)
            ->get(route('ventas.create'))
            ->assertOk();
    }

    public function test_corte_mensual_no_permite_transferir_mas_del_disponible(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 1, 9, 0, 0, config('app.timezone')));

        [$user] = $this->createSucursalUserWithPermissions();
        $caja = $this->createOpenCashWithJulySale($user, 1000, 500);

        $this
            ->actingAs($user)
            ->from(route('cajas.corte-mensual'))
            ->post(route('cajas.corte-mensual.store'), [
                'periodo_year' => 2026,
                'periodo_month' => 7,
                'monto_transferido' => 1500.01,
                'referencia' => 'BOLETA-EXCESO',
            ])
            ->assertRedirect(route('cajas.corte-mensual'))
            ->assertSessionHasErrors('monto_transferido');

        $this->assertDatabaseMissing('corte_mensual_cajas', [
            'sucursal_id' => $user->sucursal_id,
            'periodo_year' => 2026,
            'periodo_month' => 7,
        ]);

        $this->assertDatabaseMissing('movimiento_cajas', [
            'caja_id' => $caja->id,
            'tipo' => 'TRANSFERENCIA_JEFE',
            'referencia' => 'BOLETA-EXCESO',
        ]);
    }

    public function test_corte_parcial_venta_posterior_cierre_y_nueva_apertura_cuadran(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 1, 9, 0, 0, config('app.timezone')));

        [$user] = $this->createSucursalUserWithPermissions();
        $caja = $this->createOpenCashWithJulySale($user, 1000, 500);

        $this
            ->actingAs($user)
            ->post(route('cajas.corte-mensual.store'), [
                'periodo_year' => 2026,
                'periodo_month' => 7,
                'monto_transferido' => 1200,
                'referencia' => 'BOLETA-PARCIAL',
            ])
            ->assertRedirect(route('cajas.index'));

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => 500,
            'fecha_movimiento' => now(),
            'referencia' => 'VENTA-POST-CORTE',
        ]);

        $this
            ->actingAs($user)
            ->post(route('cajas.cierre.store', $caja), [
                'monto_cierre' => 800,
                'observacion' => 'Cierre posterior al corte mensual',
            ])
            ->assertRedirect(route('cajas.index'));

        $this->assertDatabaseHas('cajas', [
            'id' => $caja->id,
            'estado' => 'CERRADA',
            'monto_cierre' => 800,
            'total_sistema' => 800,
            'diferencia' => 0,
        ]);

        $this
            ->actingAs($user)
            ->post(route('cajas.apertura.store'), [
                'monto_apertura' => 0,
            ])
            ->assertRedirect(route('cajas.index'));

        $this->assertDatabaseHas('cajas', [
            'sucursal_id' => $user->sucursal_id,
            'estado' => 'ABIERTA',
            'monto_apertura' => 800,
        ]);
    }

    public function test_no_registra_corte_fuera_de_periodo_pendiente_o_disponible(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 15, 9, 0, 0, config('app.timezone')));

        [$user] = $this->createSucursalUserWithPermissions();

        $this
            ->actingAs($user)
            ->get(route('cajas.corte-mensual'))
            ->assertRedirect(route('cajas.index'));

        $this
            ->actingAs($user)
            ->post(route('cajas.corte-mensual.store'), [
                'periodo_year' => 2026,
                'periodo_month' => 8,
                'monto_transferido' => 0,
            ])
            ->assertRedirect(route('cajas.index'));

        $this->assertSame(0, CorteMensualCaja::count());
    }

    private function createSucursalUserWithPermissions(): array
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

        foreach (['ventas.crear', 'ventas.ver', 'caja.abrir', 'caja.cerrar'] as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $user->givePermissionTo($permission);
        }

        return [$user, $sucursal];
    }

    private function createOpenCashWithJulySale(User $user, float $apertura, float $venta): Caja
    {
        Venta::create([
            'sucursal_id' => $user->sucursal_id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-JULIO-' . uniqid(),
            'subtotal' => $venta,
            'descuento' => 0,
            'total' => $venta,
            'estado' => 'FINALIZADA',
        ])->forceFill([
            'created_at' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
            'updated_at' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
        ])->save();

        $caja = Caja::create([
            'sucursal_id' => $user->sucursal_id,
            'user_id' => $user->id,
            'monto_apertura' => $apertura,
            'fecha_apertura' => Carbon::create(2026, 7, 31, 8, 0, 0, config('app.timezone')),
            'estado' => 'ABIERTA',
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'tipo' => 'VENTA',
            'monto' => $venta,
            'fecha_movimiento' => Carbon::create(2026, 7, 31, 12, 0, 0, config('app.timezone')),
            'referencia' => 'FAC-JULIO-CAJA',
        ]);

        return $caja;
    }
}
