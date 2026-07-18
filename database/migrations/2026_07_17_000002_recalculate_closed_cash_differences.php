<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cajas')
            ->where('estado', 'CERRADA')
            ->whereNotNull('monto_cierre')
            ->orderBy('id')
            ->chunkById(100, function ($cajas) {
                foreach ($cajas as $caja) {
                    $ventas = $this->sumMovimientos($caja->id, ['VENTA']);
                    $egresos = $this->sumMovimientos($caja->id, ['EGRESO']);
                    $transferencias = $this->sumMovimientos($caja->id, ['TRANSFERENCIA_JEFE']);
                    $totalSistema = round((float) $caja->monto_apertura + $ventas - $egresos - $transferencias, 2);
                    $diferencia = round((float) $caja->monto_cierre - $totalSistema, 2);

                    DB::table('cajas')
                        ->where('id', $caja->id)
                        ->update([
                            'total_sistema' => $totalSistema,
                            'diferencia' => $diferencia,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function sumMovimientos(int $cajaId, array $tipos): float
    {
        return (float) DB::table('movimiento_cajas')
            ->where('caja_id', $cajaId)
            ->whereIn('tipo', $tipos)
            ->sum('monto');
    }
};
