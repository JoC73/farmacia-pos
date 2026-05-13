<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nit',
        'nombre',
        'telefono',
        'direccion',
        'estado',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}