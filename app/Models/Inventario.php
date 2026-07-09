<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'nombre_local',
        'activo',
        'existencia',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'activo' => 'boolean',
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

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
