<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    protected $table = 'movimiento_cajas';

    protected $fillable = [

        'caja_id',
        'user_id',
        'tipo',
        'monto',
        'fecha_movimiento',
        'referencia',
        'descripcion',

    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
