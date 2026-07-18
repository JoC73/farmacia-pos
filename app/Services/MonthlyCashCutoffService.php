<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\CorteMensualCaja;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use Carbon\Carbon;

class MonthlyCashCutoffService
{
    private const FEATURE_START_YEAR = 2026;
    private const FEATURE_START_MONTH = 7;

    public function pendingForSucursal(?int $sucursalId): ?array
    {
        if (! $sucursalId) {
            return null;
        }

        $period = now()->copy()->startOfMonth()->subMonth();

        if (! $this->isManagedPeriod($period) || ! $this->hasActivity($sucursalId, $period)) {
            return null;
        }

        if ($this->exists($sucursalId, $period)) {
            return null;
        }

        return $this->periodPayload($period, true);
    }

    public function warningForSucursal(?int $sucursalId): ?array
    {
        if (! $sucursalId || now()->day !== now()->daysInMonth) {
            return null;
        }

        $period = now()->copy()->startOfMonth();

        if (! $this->isManagedPeriod($period) || ! $this->hasActivity($sucursalId, $period)) {
            return null;
        }

        if ($this->exists($sucursalId, $period)) {
            return null;
        }

        return $this->periodPayload($period, false);
    }

    public function resumenCaja(Caja $caja): array
    {
        $ventas = $this->sumMovimientos($caja->id, ['VENTA']);
        $egresos = $this->sumMovimientos($caja->id, ['EGRESO']);
        $transferencias = $this->sumMovimientos($caja->id, ['TRANSFERENCIA_JEFE']);
        $disponible = (float) $caja->monto_apertura + $ventas - $egresos - $transferencias;

        return [
            'saldo_inicial' => (float) $caja->monto_apertura,
            'ventas' => $ventas,
            'egresos' => $egresos,
            'transferencias_previas' => $transferencias,
            'disponible_antes' => $disponible,
        ];
    }

    public function cutoffExists(int $sucursalId, int $year, int $month): bool
    {
        return CorteMensualCaja::where('sucursal_id', $sucursalId)
            ->where('periodo_year', $year)
            ->where('periodo_month', $month)
            ->exists();
    }

    private function isManagedPeriod(Carbon $period): bool
    {
        $start = Carbon::create(self::FEATURE_START_YEAR, self::FEATURE_START_MONTH, 1, 0, 0, 0, config('app.timezone'));

        return $period->greaterThanOrEqualTo($start);
    }

    private function hasActivity(int $sucursalId, Carbon $period): bool
    {
        return Caja::where('sucursal_id', $sucursalId)
            ->whereBetween('fecha_apertura', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
            ->exists()
            || Venta::where('sucursal_id', $sucursalId)
                ->where('estado', 'FINALIZADA')
                ->whereBetween('created_at', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
                ->exists();
    }

    private function exists(int $sucursalId, Carbon $period): bool
    {
        return $this->cutoffExists($sucursalId, (int) $period->year, (int) $period->month);
    }

    private function periodPayload(Carbon $period, bool $bloqueante): array
    {
        return [
            'year' => (int) $period->year,
            'month' => (int) $period->month,
            'label' => $period->locale('es')->translatedFormat('F Y'),
            'bloqueante' => $bloqueante,
        ];
    }

    private function sumMovimientos(int $cajaId, array $tipos): float
    {
        return (float) MovimientoCaja::where('caja_id', $cajaId)
            ->whereIn('tipo', $tipos)
            ->sum('monto');
    }
}
