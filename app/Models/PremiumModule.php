<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;

class PremiumModule extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'enabled',
        'enabled_by',
        'enabled_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'enabled_at' => 'datetime',
    ];

    public static function catalog(): array
    {
        return [
            'branch_creation' => [
                'name' => 'Creacion de sucursales adicionales',
                'description' => 'Permite crear nuevas sucursales despues de activar el paquete premium.',
            ],
            'physical_inventory' => [
                'name' => 'Inventario fisico masivo',
                'description' => 'Descarga Excel por sucursal, carga conteos fisicos y aplica ajustes auditados.',
            ],
            'advanced_reports' => [
                'name' => 'Reportes avanzados y PDF profesional',
                'description' => 'Reportes ampliados, exportaciones y documentos PDF profesionales.',
            ],
            'advanced_dashboard' => [
                'name' => 'Dashboard con graficas avanzadas',
                'description' => 'Graficas, comparativos y analitica gerencial avanzada.',
            ],
            'branch_transfers' => [
                'name' => 'Transferencias entre sucursales',
                'description' => 'Traslados con salida, entrada y trazabilidad entre sucursales.',
            ],
            'customer_returns' => [
                'name' => 'Devoluciones',
                'description' => 'Devoluciones de clientes y proveedores con impacto en inventario y caja.',
            ],
            'customer_credit' => [
                'name' => 'Credito a clientes',
                'description' => 'Ventas al credito, abonos, saldos y cuentas por cobrar.',
            ],
            'advanced_printing' => [
                'name' => 'Impresion termica avanzada',
                'description' => 'Tickets optimizados para impresoras termicas y reimpresion avanzada.',
            ],
            'barcode_tools' => [
                'name' => 'Codigo de barras y lector',
                'description' => 'Flujos optimizados para busqueda, venta y recepcion con lector.',
            ],
            'lots_fefo' => [
                'name' => 'Lotes, vencimientos y FEFO',
                'description' => 'Inventario por lote, vencimiento real por lote y salida FEFO.',
            ],
            'inventory_ai' => [
                'name' => 'IA para inventario',
                'description' => 'Alertas predictivas, sugerencias de compra y analisis inteligente.',
            ],
            'mobile_api' => [
                'name' => 'API movil',
                'description' => 'Endpoints seguros para apps moviles y servicios externos.',
            ],
            'seller_app' => [
                'name' => 'App para vendedores',
                'description' => 'Modulo comercial movil para vendedores o rutas.',
            ],
            'smart_purchases' => [
                'name' => 'Compras inteligentes automaticas',
                'description' => 'Sugerencias automaticas de compra por rotacion, stock y proveedores.',
            ],
        ];
    }

    public static function seedCatalog(): void
    {
        foreach (self::catalog() as $code => $module) {
            self::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $module['name'],
                    'description' => $module['description'],
                ]
            );
        }
    }

    public static function enabled(string $code): bool
    {
        try {
            return self::where('code', $code)
                ->where('enabled', true)
                ->exists();
        } catch (QueryException) {
            return false;
        }
    }
}
