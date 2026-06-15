<?php

use App\Models\Inventario;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
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

Artisan::command('products:merge-duplicates {--apply : Ejecuta la fusion; sin esta opcion solo muestra diagnostico} {--strategy=max : Estrategia de existencia por sucursal: max o sum}', function () {
    $apply = (bool) $this->option('apply');
    $strategy = (string) $this->option('strategy');

    if (! in_array($strategy, ['max', 'sum'], true)) {
        $this->error('La estrategia debe ser max o sum.');
        return 1;
    }

    $groups = Producto::query()
        ->get()
        ->groupBy(fn (Producto $producto) => product_duplicate_key($producto->nombre, $producto->laboratorio))
        ->filter(fn ($items) => $items->count() > 1);

    if ($groups->isEmpty()) {
        $this->info('No se encontraron productos duplicados por nombre/laboratorio.');
        return 0;
    }

    $this->warn('Productos duplicados encontrados: ' . $groups->count() . ' grupo(s).');

    foreach ($groups as $key => $items) {
        $master = $items
            ->sortBy([
                ['estado', 'desc'],
                ['id', 'asc'],
            ])
            ->first();

        $duplicates = $items->where('id', '!=', $master->id)->values();

        $this->line('');
        $this->line("Grupo: {$key}");
        $this->line("Maestro: #{$master->id} {$master->nombre} ({$master->codigo_barra})");

        foreach ($duplicates as $duplicate) {
            $this->line("  Duplicado: #{$duplicate->id} {$duplicate->nombre} ({$duplicate->codigo_barra})");
        }

        if (! $apply) {
            continue;
        }

        DB::transaction(function () use ($master, $duplicates, $strategy) {
            $duplicateIds = $duplicates->pluck('id')->all();
            $allProductIds = array_merge([$master->id], $duplicateIds);

            foreach (Sucursal::pluck('id') as $sucursalId) {
                $inventarios = Inventario::whereIn('producto_id', $allProductIds)
                    ->where('sucursal_id', $sucursalId)
                    ->lockForUpdate()
                    ->get();

                if ($inventarios->isEmpty()) {
                    continue;
                }

                $masterInventory = $inventarios->firstWhere('producto_id', $master->id);

                if (! $masterInventory) {
                    $masterInventory = Inventario::create([
                        'producto_id' => $master->id,
                        'sucursal_id' => $sucursalId,
                        'existencia' => 0,
                    ]);
                }

                $existenciaAnterior = (int) $masterInventory->existencia;
                $existencias = $inventarios->pluck('existencia')->map(fn ($value) => (int) $value);
                $existenciaNueva = $strategy === 'sum'
                    ? $existencias->sum()
                    : $existencias->max();

                if ($existenciaNueva !== $existenciaAnterior) {
                    $masterInventory->update([
                        'existencia' => $existenciaNueva,
                    ]);

                    MovimientoInventario::create([
                        'producto_id' => $master->id,
                        'sucursal_id' => $sucursalId,
                        'user_id' => null,
                        'tipo_movimiento' => $existenciaNueva >= $existenciaAnterior ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA',
                        'cantidad' => abs($existenciaNueva - $existenciaAnterior),
                        'existencia_anterior' => $existenciaAnterior,
                        'existencia_nueva' => $existenciaNueva,
                        'referencia' => 'Fusion de productos duplicados',
                        'observacion' => "Existencia consolidada con estrategia {$strategy}.",
                    ]);
                }

                Inventario::whereIn('producto_id', $duplicateIds)
                    ->where('sucursal_id', $sucursalId)
                    ->delete();
            }

            DetalleVenta::whereIn('producto_id', $duplicateIds)->update([
                'producto_id' => $master->id,
            ]);

            DetalleCompra::whereIn('producto_id', $duplicateIds)->update([
                'producto_id' => $master->id,
            ]);

            MovimientoInventario::whereIn('producto_id', $duplicateIds)->update([
                'producto_id' => $master->id,
            ]);

            Producto::whereIn('id', $duplicateIds)->update([
                'estado' => false,
            ]);
        });
    }

    $this->line('');

    if ($apply) {
        $this->info('Fusion de duplicados completada.');
    } else {
        $this->warn('Diagnostico completado. Ejecuta con --apply para fusionar.');
    }

    return 0;
})->purpose('Detecta y fusiona productos duplicados por nombre/laboratorio, conservando historial e inventario por sucursal.');

if (! function_exists('product_duplicate_key')) {
    function product_duplicate_key(?string $nombre, ?string $laboratorio): string
    {
        return str($nombre ?? '')
            ->ascii()
            ->lower()
            ->squish()
            ->append('|')
            ->append(str($laboratorio ?? '')->ascii()->lower()->squish())
            ->toString();
    }
}
