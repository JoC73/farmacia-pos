<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_muestra_ventas_y_saldo_de_caja_como_metricas_separadas(): void
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

        $ventaAnterior = Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-MES-1',
            'subtotal' => 3386.50,
            'descuento' => 0,
            'total' => 3386.50,
            'estado' => 'FINALIZADA',
        ]);
        $ventaAnterior->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->save();

        Venta::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'numero_factura' => 'FAC-HOY-1',
            'subtotal' => 123.50,
            'descuento' => 0,
            'total' => 123.50,
            'estado' => 'FINALIZADA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Caja::create([
            'sucursal_id' => $sucursal->id,
            'user_id' => $user->id,
            'monto_apertura' => 3536.50,
            'monto_cierre' => 3660,
            'total_sistema' => 3660,
            'diferencia' => 0,
            'fecha_apertura' => now()->subHours(8),
            'fecha_cierre' => now(),
            'estado' => 'CERRADA',
        ]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ventas Hoy')
            ->assertSee('Q 123.50')
            ->assertSee('Ventas del Mes')
            ->assertSee('Q 3,510.00')
            ->assertSee('Saldo de Caja')
            ->assertSee('Q 3,660.00');
    }
}
