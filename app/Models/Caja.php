<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $fillable = [

        'sucursal_id',
        'user_id',
        'monto_apertura',
        'monto_cierre',
        'total_sistema',
        'diferencia',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'observacion',

    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class);
    }
}