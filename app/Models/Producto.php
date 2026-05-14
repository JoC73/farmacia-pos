<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'categoria_id',
        'codigo_barra',
        'nombre',
        'laboratorio',
        'costo',
        'precio_venta',
        'stock_minimo',
        'fecha_vencimiento',
        'descripcion',
        'estado',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function scopeOrdenadoPorNombre($query)
    {
        return $query
            ->orderByRaw('LOWER(nombre)')
            ->orderBy('nombre');
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }
}
