<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $casts = [
        'fecha_compra' => 'date',
    ];

    protected $fillable = [
        'proveedor_id',
        'sucursal_id',
        'user_id',
        'numero_factura',
        'fecha_compra',
        'subtotal',
        'descuento',
        'total',
        'estado',
        'observacion',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class);
    }
}
