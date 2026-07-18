<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorteMensualCaja extends Model
{
    protected $table = 'corte_mensual_cajas';

    protected $fillable = [
        'sucursal_id',
        'caja_id',
        'user_id',
        'periodo_year',
        'periodo_month',
        'saldo_inicial',
        'ventas',
        'egresos',
        'transferencias_previas',
        'disponible_antes',
        'monto_transferido',
        'saldo_restante',
        'fecha_corte',
        'referencia',
        'observacion',
    ];

    protected $casts = [
        'fecha_corte' => 'datetime',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
