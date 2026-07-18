<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sucursalId = DB::table('sucursales')
            ->where('nombre', 'FarmaVida Mazate')
            ->value('id');

        if (! $sucursalId) {
            return;
        }

        DB::transaction(function () use ($sucursalId) {
            $cajaBase = DB::table('cajas')
                ->where('sucursal_id', $sucursalId)
                ->where('estado', 'CERRADA')
                ->where('monto_apertura', 3406)
                ->where('monto_cierre', 6792.50)
                ->where('total_sistema', 3536.50)
                ->first();

            if (! $cajaBase) {
                return;
            }

            $cierreCorrecto = 3536.50;

            $this->actualizarCajaCerrada($cajaBase->id, $cierreCorrecto);

            $cajaSiguiente = DB::table('cajas')
                ->where('sucursal_id', $sucursalId)
                ->where('id', '>', $cajaBase->id)
                ->where('monto_apertura', 6792.50)
                ->orderBy('id')
                ->first();

            if (! $cajaSiguiente) {
                return;
            }

            $this->actualizarApertura($cajaSiguiente->id, $cierreCorrecto);

            $ventas = $this->sumMovimientos($cajaSiguiente->id, ['VENTA']);
            $egresos = $this->sumMovimientos($cajaSiguiente->id, ['EGRESO']);
            $transferencias = $this->sumMovimientos($cajaSiguiente->id, ['TRANSFERENCIA_JEFE']);
            $totalSistema = round($cierreCorrecto + $ventas - $egresos - $transferencias, 2);

            if ($cajaSiguiente->estado === 'CERRADA') {
                $this->actualizarCajaCerrada($cajaSiguiente->id, $totalSistema);
            } else {
                DB::table('cajas')
                    ->where('id', $cajaSiguiente->id)
                    ->update([
                        'total_sistema' => $totalSistema,
                        'diferencia' => 0,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        //
    }

    private function actualizarCajaCerrada(int $cajaId, float $montoCorrecto): void
    {
        DB::table('cajas')
            ->where('id', $cajaId)
            ->update([
                'monto_cierre' => $montoCorrecto,
                'total_sistema' => $montoCorrecto,
                'diferencia' => 0,
                'updated_at' => now(),
            ]);

        DB::table('movimiento_cajas')
            ->where('caja_id', $cajaId)
            ->where('tipo', 'CIERRE')
            ->update([
                'monto' => $montoCorrecto,
                'updated_at' => now(),
            ]);
    }

    private function actualizarApertura(int $cajaId, float $montoCorrecto): void
    {
        DB::table('cajas')
            ->where('id', $cajaId)
            ->update([
                'monto_apertura' => $montoCorrecto,
                'updated_at' => now(),
            ]);

        DB::table('movimiento_cajas')
            ->where('caja_id', $cajaId)
            ->where('tipo', 'APERTURA')
            ->update([
                'monto' => $montoCorrecto,
                'updated_at' => now(),
            ]);
    }

    private function sumMovimientos(int $cajaId, array $tipos): float
    {
        return (float) DB::table('movimiento_cajas')
            ->where('caja_id', $cajaId)
            ->whereIn('tipo', $tipos)
            ->sum('monto');
    }
};
