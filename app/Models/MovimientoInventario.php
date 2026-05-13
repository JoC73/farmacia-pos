<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimiento_inventarios';

    protected $fillable = [

        'producto_id',
        'sucursal_id',
        'user_id',
        'tipo_movimiento',
        'cantidad',
        'existencia_anterior',
        'existencia_nueva',
        'referencia',
        'observacion',

    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}