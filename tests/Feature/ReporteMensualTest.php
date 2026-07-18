<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\CorteMensualCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReporteMensualTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporte_de_ventas_filtra_por_mes_y_anio(): void
    {
        [$user, $sucursal] = $this->createUserWithReports();

        $this->createSale($user, $sucursal, 'FAC-JULIO', 100, '2026-07-15 10:00:00');
        $this->createSale($user, $sucursal, 'FAC-AGOSTO', 200, '2026-08-15 10:00:00');

        $this
            ->actingAs($user)
            ->get(route('reportes.ventas', [
                'month' => 7,
                'year' => 2026,
            ]))
            ->assertOk()
            ->assertSee('FAC-JULIO')
            ->assertSee('Q 100.00')
            ->assertDontSee('FAC-AGOSTO')
            ->assertDontSee('Q 200.00');
    }

    public function test_reporte_de_cortes_mensuales_filtra_por_mes_y_sucursal(): void
    {
        [$user, $sucursal] = $this->createUserWithReports();
        $otraSucursal = Sucursal::create([
            'nombre' => 'Otra Sucursal',
            'estado' => true,
        ]);

        $caja = Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 100,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
        ]);

        CorteMensualCaja::create([
            'sucursal_id' => $sucursal->id,
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'periodo_year' => 2026,
            'periodo_month' => 7,
            'saldo_inicial' => 100,
            'ventas' => 500,
            'egresos' => 0,
            'transferencias_previas' => 0,
            'disponible_antes' => 600,
            'monto_transferido' => 400,
            'saldo_restante' => 200,
            'fecha_corte' => now(),
            'referencia' => 'BOLETA-JULIO',
        ]);

        CorteMensualCaja::create([
            'sucursal_id' => $otraSucursal->id,
            'user_id' => $user->id,
            'periodo_year' => 2026,
            'periodo_month' => 8,
            'saldo_inicial' => 0,
            'ventas' => 900,
            'egresos' => 0,
            'transferencias_previas' => 0,
            'disponible_antes' => 900,
            'monto_transferido' => 900,
            'saldo_restante' => 0,
            'fecha_corte' => now(),
            'referencia' => 'BOLETA-AGOSTO',
        ]);

        $this
            ->actingAs($user)
            ->get(route('reportes.cortes-mensuales', [
                'month' => 7,
                'year' => 2026,
            ]))
            ->assertOk()
            ->assertSee('BOLETA-JULIO')
            ->assertSee('Q 400.00')
            ->assertDontSee('BOLETA-AGOSTO')
            ->assertDontSee('Q 900.00');
    }

    private function createUserWithReports(): array
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

        foreach (['reportes.ventas', 'reportes.caja'] as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $user->givePermissionTo($permission);
        }

        return [$user, $sucursal];
    }

    private function createSale(User $user, Sucursal $sucursal, string $factura, float $total, string $createdAt): void
    {
        $venta = Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => $factura,
            'subtotal' => $total,
            'descuento' => 0,
            'total' => $total,
            'estado' => 'FINALIZADA',
        ]);

        $venta->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();
    }
}
