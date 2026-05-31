<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'sucursal_id',
        'user_id',
        'cliente_id',
        'numero_factura',
        'cliente',
        'subtotal',
        'descuento',
        'total',
        'estado',
        'observacion',
        'anulada_por',
        'fecha_anulacion',
        'motivo_anulacion',
    ];

    protected $casts = [
        'fecha_anulacion' => 'datetime',
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

    public function anulador()
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
