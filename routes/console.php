<?php

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:set-all-branches-stock {quantity=2 : Existencia exacta para cada producto por sucursal} {--force : Ejecuta sin pedir confirmacion}', function () {
    $quantity = (int) $this->argument('quantity');

    if ($quantity < 0) {
        $this->error('La existencia no puede ser negativa.');
        return 1;
    }

    if (! $this->option('force') && ! $this->confirm("Esto ajustara todos los productos activos en todas las sucursales activas a existencia {$quantity}. ¿Continuar?")) {
        $this->warn('Operacion cancelada.');
        return 0;
    }

    $productos = Producto::where('estado', true)->get();
    $sucursales = Sucursal::where('estado', true)->get();

    $ajustes = 0;
    $sinCambios = 0;

    DB::transaction(function () use ($productos, $sucursales, $quantity, &$ajustes, &$sinCambios) {
        foreach ($sucursales as $sucursal) {
            foreach ($productos as $producto) {
                $inventario = Inventario::firstOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'sucursal_id' => $sucursal->id,
                    ],
                    [
                        'existencia' => 0,
                    ]
                );

                $existenciaAnterior = (int) $inventario->existencia;

                if ($existenciaAnterior === $quantity) {
                    $sinCambios++;
                    continue;
                }

                $inventario->update([
                    'existencia' => $quantity,
                ]);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'sucursal_id' => $sucursal->id,
                    'user_id' => null,
                    'tipo_movimiento' => $quantity >= $existenciaAnterior ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA',
                    'cantidad' => abs($quantity - $existenciaAnterior),
                    'existencia_anterior' => $existenciaAnterior,
                    'existencia_nueva' => $quantity,
                    'referencia' => 'Ajuste masivo de stock por sucursal',
                    'observacion' => 'Ajuste administrativo masivo ejecutado por comando Artisan.',
                ]);

                $ajustes++;
            }
        }
    });

    $this->info("Stock actualizado a {$quantity}.");
    $this->line("Ajustes registrados: {$ajustes}.");
    $this->line("Registros sin cambios: {$sinCambios}.");

    return 0;
})->purpose('Ajusta de forma auditada la existencia de todos los productos activos en todas las sucursales activas.');
