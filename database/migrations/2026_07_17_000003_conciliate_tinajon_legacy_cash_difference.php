<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cajas')
            ->where('estado', 'CERRADA')
            ->where('monto_apertura', 100)
            ->where('monto_cierre', 868.25)
            ->where('total_sistema', 1323.25)
            ->where('diferencia', -455)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sucursales')
                    ->whereColumn('sucursales.id', 'cajas.sucursal_id')
                    ->where('sucursales.nombre', 'like', '%Tinajon%');
            })
            ->update([
                'total_sistema' => 868.25,
                'diferencia' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
