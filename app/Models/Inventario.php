<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'nombre_local',
        'categoria_local',
        'laboratorio_local',
        'costo_local',
        'precio_venta_local',
        'stock_minimo_local',
        'descripcion_local',
        'activo',
        'existencia',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'costo_local' => 'decimal:2',
        'precio_venta_local' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function getNombreMostradoAttribute(): string
    {
        return trim((string) $this->nombre_local) !== ''
            ? $this->nombre_local
            : ($this->producto->nombre ?? 'Producto eliminado');
    }

    public function getCategoriaMostradaAttribute(): string
    {
        return trim((string) $this->categoria_local) !== ''
            ? $this->categoria_local
            : ($this->producto->categoria->nombre ?? 'Sin categoria');
    }

    public function getLaboratorioMostradoAttribute(): string
    {
        return trim((string) $this->laboratorio_local) !== ''
            ? $this->laboratorio_local
            : (trim((string) ($this->producto->laboratorio ?? '')) ?: '-');
    }

    public function getCostoMostradoAttribute(): float
    {
        return $this->costo_local !== null
            ? (float) $this->costo_local
            : (float) ($this->producto->costo ?? 0);
    }

    public function getPrecioVentaMostradoAttribute(): float
    {
        return $this->precio_venta_local !== null
            ? (float) $this->precio_venta_local
            : (float) ($this->producto->precio_venta ?? 0);
    }

    public function getStockMinimoMostradoAttribute(): int
    {
        return $this->stock_minimo_local !== null
            ? (int) $this->stock_minimo_local
            : (int) ($this->producto->stock_minimo ?? 0);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
