<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'sucursal_id',
        'user_id',
        'numero_factura',
        'cliente',
        'subtotal',
        'descuento',
        'total',
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

    public function cliente()
{
    return $this->belongsTo(Cliente::class);
}

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}