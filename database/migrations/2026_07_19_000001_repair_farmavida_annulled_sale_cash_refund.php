<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FACTURA = 'FAC-1784417085';

    public function up(): void
    {
        DB::transaction(function () {
            $venta = DB::table('ventas')
                ->where('numero_factura', self::FACTURA)
                ->where('estado', 'ANULADA')
                ->first();

            if (! $venta || ! $this->isFarmavida((int) $venta->sucursal_id)) {
                return;
            }

            $movimientoVenta = DB::table('movimiento_cajas')
                ->where('referencia', self::FACTURA)
                ->where('tipo', 'VENTA')
                ->first();

            if (! $movimientoVenta) {
                return;
            }

            $caja = DB::table('cajas')
                ->where('id', $movimientoVenta->caja_id)
                ->first();

            if (! $caja) {
                return;
            }

            $egresoExiste = DB::table('movimiento_cajas')
                ->where('caja_id', $caja->id)
                ->where('referencia', self::FACTURA)
                ->where('tipo', 'EGRESO')
                ->exists();

            if (! $egresoExiste) {
                DB::table('movimiento_cajas')->insert([
                    'caja_id' => $caja->id,
                    'user_id' => $venta->anulada_por ?: $venta->user_id,
                    'tipo' => 'EGRESO',
                    'monto' => round((float) $venta->total, 2),
                    'referencia' => self::FACTURA,
                    'descripcion' => 'Reparacion historica: devolucion por venta anulada',
                    'fecha_movimiento' => $venta->fecha_anulacion ?: $venta->updated_at ?: now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($caja->estado === 'CERRADA') {
                $montoCierreAnterior = (float) $caja->monto_cierre;
                $totalSistema = $this->totalSistemaCaja((int) $caja->id, (float) $caja->monto_apertura);

                $this->actualizarCajaCerrada((int) $caja->id, $totalSistema);
                $this->actualizarCajaSiguienteSiHeredoSaldo(
                    (int) $caja->sucursal_id,
                    (int) $caja->id,
                    $montoCierreAnterior,
                    $totalSistema
                );
            }
        });
    }

    public function down(): void
    {
        //
    }

    private function isFarmavida(int $sucursalId): bool
    {
        return DB::table('sucursales')
            ->where('id', $sucursalId)
            ->where('nombre', 'like', '%FarmaVida%')
            ->exists();
    }

    private function totalSistemaCaja(int $cajaId, float $montoApertura): float
    {
        $ventas = $this->sumMovimientos($cajaId, ['VENTA']);
        $egresos = $this->sumMovimientos($cajaId, ['EGRESO']);
        $transferencias = $this->sumMovimientos($cajaId, ['TRANSFERENCIA_JEFE']);

        return round($montoApertura + $ventas - $egresos - $transferencias, 2);
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

    private function actualizarCajaSiguienteSiHeredoSaldo(
        int $sucursalId,
        int $cajaId,
        float $montoAnterior,
        float $montoCorrecto
    ): void {
        $cajaSiguiente = DB::table('cajas')
            ->where('sucursal_id', $sucursalId)
            ->where('id', '>', $cajaId)
            ->where('monto_apertura', round($montoAnterior, 2))
            ->orderBy('id')
            ->first();

        if (! $cajaSiguiente) {
            return;
        }

        DB::table('cajas')
            ->where('id', $cajaSiguiente->id)
            ->update([
                'monto_apertura' => $montoCorrecto,
                'updated_at' => now(),
            ]);

        DB::table('movimiento_cajas')
            ->where('caja_id', $cajaSiguiente->id)
            ->where('tipo', 'APERTURA')
            ->update([
                'monto' => $montoCorrecto,
                'updated_at' => now(),
            ]);

        $totalSistema = $this->totalSistemaCaja((int) $cajaSiguiente->id, $montoCorrecto);

        if ($cajaSiguiente->estado === 'CERRADA') {
            $this->actualizarCajaCerrada((int) $cajaSiguiente->id, $totalSistema);

            return;
        }

        DB::table('cajas')
            ->where('id', $cajaSiguiente->id)
            ->update([
                'total_sistema' => $totalSistema,
                'diferencia' => 0,
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
