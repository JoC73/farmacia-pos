<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CorteMensualCaja;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Services\MonthlyCashCutoffService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $sucursalId = $user->visibleSucursalId();

        $cajas = Caja::with([
            'usuario',
            'sucursal',
        ])
        ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
        ->latest()
        ->paginate(20);

        $cajas->getCollection()->each(function (Caja $caja) {
            $resumen = $this->resumenCaja($caja);
            $totalSistema = $this->totalSistemaMostrado($caja, (float) $resumen['disponible']);
            $diferencia = $this->diferenciaCaja($caja, $totalSistema);

            $caja->setAttribute('total_sistema_mostrado', $totalSistema);
            $caja->setAttribute('diferencia_mostrada', $diferencia);
        });

        $cajaAbiertaActual = null;

        if ($sucursalId) {
            $cajaAbiertaActual = Caja::with(['usuario', 'sucursal'])
                ->where('sucursal_id', $sucursalId)
                ->where('estado', 'ABIERTA')
                ->latest()
                ->first();
        }

        $transferencias = collect();
        $totalTransferenciasMes = 0;
        $corteMensualPendiente = app(MonthlyCashCutoffService::class)->pendingForSucursal($sucursalId);
        $corteMensualAviso = app(MonthlyCashCutoffService::class)->warningForSucursal($sucursalId);

        if ($user->can('caja.ver_cierres')) {
            $transferenciasQuery = MovimientoCaja::with([
                'caja.sucursal',
                'usuario',
            ])
                ->where('tipo', 'TRANSFERENCIA_JEFE')
                ->when($sucursalId, function ($query) use ($sucursalId) {
                    $query->whereHas('caja', fn ($cajaQuery) => $cajaQuery->where('sucursal_id', $sucursalId));
                });

            $totalTransferenciasMes = (clone $transferenciasQuery)
                ->whereMonth('fecha_movimiento', now()->month)
                ->whereYear('fecha_movimiento', now()->year)
                ->sum('monto');

            $transferencias = $transferenciasQuery
                ->latest('fecha_movimiento')
                ->latest()
                ->limit(15)
                ->get();
        }

        return view('cajas.index', compact(
            'cajas',
            'transferencias',
            'totalTransferenciasMes',
            'cajaAbiertaActual',
            'corteMensualPendiente',
            'corteMensualAviso'
        ));
    }

    public function createApertura()
    {
        $user = auth()->user();
        $sucursalId = $user->visibleSucursalId();

        if (! $user->canViewAllSucursales() && ! $sucursalId) {
            return redirect()
                ->route('cajas.index')
                ->with('error', 'Tu usuario no tiene sucursal asignada. Un administrador debe editar tu usuario antes de abrir caja.');
        }

        if ($sucursalId) {
            $cajaAbierta = Caja::where('sucursal_id', $sucursalId)
                ->where('estado', 'ABIERTA')
                ->latest()
                ->first();

            if ($cajaAbierta) {
                return redirect()
                    ->route('cajas.index')
                    ->with('success', 'Esta sucursal ya tiene una caja abierta. Puedes continuar usando la caja #' . $cajaAbierta->id . '.');
            }
        }

        $sucursales = Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();

        $saldosSugeridos = $sucursales
            ->mapWithKeys(function (Sucursal $sucursal) {
                $ultimaCajaCerrada = $this->ultimaCajaCerrada($sucursal->id);

                return [
                    $sucursal->id => [
                        'monto' => $ultimaCajaCerrada ? (float) $ultimaCajaCerrada->monto_cierre : 0,
                        'tiene_historial' => (bool) $ultimaCajaCerrada,
                        'caja_id' => $ultimaCajaCerrada?->id,
                        'fecha_cierre' => $ultimaCajaCerrada?->fecha_cierre
                            ?->timezone(config('app.timezone'))
                            ?->format('d/m/Y H:i'),
                    ],
                ];
            })
            ->all();

        $puedeCorregirApertura = $this->canOverrideOpeningAmount($user);

        return view('cajas.apertura', compact('sucursales', 'saldosSugeridos', 'puedeCorregirApertura'));
    }

    public function storeApertura(Request $request)
{
    $user = auth()->user();
    $sucursalId = $user->canViewAllSucursales()
        ? (int) $request->input('sucursal_id')
        : (int) $user->sucursal_id;

    $request->validate([

        'monto_apertura' => 'nullable|numeric|min:0',
        'sucursal_id' => $user->canViewAllSucursales()
            ? 'required|exists:sucursales,id'
            : 'nullable',

    ]);

    abort_unless($user->canAccessSucursal($sucursalId), 403);

    if (! $sucursalId) {
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'No se pudo determinar la sucursal para abrir caja. Verifica que el usuario tenga una sucursal asignada.');
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR CAJA ABIERTA POR SUCURSAL
    |--------------------------------------------------------------------------
    */

    $cajaAbierta = Caja::where('sucursal_id', $sucursalId)
            ->where('estado', 'ABIERTA')
            ->exists();

    if ($cajaAbierta) {

        return redirect()
            ->route('cajas.index')
            ->with(
                'success',
                'Esta sucursal ya tiene una caja abierta. Puedes continuar usando la caja existente.'
            );
    }

    $ultimaCajaCerrada = $this->ultimaCajaCerrada($sucursalId);
    $montoSugerido = $ultimaCajaCerrada ? (float) $ultimaCajaCerrada->monto_cierre : 0.0;
    $puedeCorregirApertura = $this->canOverrideOpeningAmount($user);

    if ($ultimaCajaCerrada) {
        $montoApertura = $puedeCorregirApertura
            ? (float) $request->input('monto_apertura', $montoSugerido)
            : $montoSugerido;
    } else {
        $montoApertura = (float) $request->input('monto_apertura', 0);
    }

    $aperturaCorregida = $ultimaCajaCerrada
        && $puedeCorregirApertura
        && abs($montoApertura - $montoSugerido) > 0.009;

    DB::transaction(function () use ($sucursalId, $user, $montoApertura, $ultimaCajaCerrada, $montoSugerido, $aperturaCorregida) {

        $caja = Caja::create([

            'sucursal_id' => $sucursalId,

            'user_id' => $user->id,

            'monto_apertura' => $montoApertura,

            'fecha_apertura' => now(),

            'estado' => 'ABIERTA',

        ]);

        MovimientoCaja::create([

            'caja_id' => $caja->id,

            'user_id' => $user->id,

            'tipo' => 'APERTURA',

            'monto' => $montoApertura,

            'referencia' => $ultimaCajaCerrada
                ? 'Saldo trasladado de caja #' . $ultimaCajaCerrada->id
                : null,

            'descripcion' => $aperturaCorregida
                ? 'Apertura corregida manualmente. Saldo sugerido: Q ' . number_format($montoSugerido, 2)
                : 'Apertura de caja',

        ]);
    });

    return redirect()
        ->route('cajas.index')
        ->with('success', 'Caja abierta correctamente con saldo inicial de Q ' . number_format($montoApertura, 2) . '.');
}

    public function createCierre(Caja $caja)
    {
        $this->authorizeCajaOperation($caja);

        if ($caja->estado === 'CERRADA') {

            return redirect()
                ->route('cajas.index')
                ->with('error', 'La caja ya está cerrada.');
        }

        $resumen = $this->resumenCaja($caja);
        $ventas = $resumen['ventas'];
        $egresos = $resumen['egresos'];
        $transferencias = $resumen['transferencias'];
        $salidas = $resumen['salidas'];
        $totalSistema = $resumen['disponible'];

        return view('cajas.cierre', compact(
            'caja',
            'ventas',
            'egresos',
            'transferencias',
            'salidas',
            'totalSistema'
        ));
    }

    public function storeCierre(Request $request, Caja $caja)
{
    $this->authorizeCajaOperation($caja);

    $request->validate([

        'monto_cierre' => 'required|numeric|min:0',

        'observacion' => 'nullable|max:500',

    ]);

    if ($caja->estado === 'CERRADA') {

        return redirect()
            ->route('cajas.index')
            ->with('error', 'La caja ya fue cerrada.');
    }

    DB::transaction(function () use ($request, $caja) {

        $resumen = $this->resumenCaja($caja);
        $totalSistema = $resumen['disponible'];

        $diferencia =
            $request->monto_cierre - $totalSistema;

        $caja->update([

            'monto_cierre' => $request->monto_cierre,

            'total_sistema' => $totalSistema,

            'diferencia' => $diferencia,

            'fecha_cierre' => now(),

            'estado' => 'CERRADA',

            'observacion' => $request->observacion,

        ]);

        MovimientoCaja::create([

            'caja_id' => $caja->id,

            'user_id' => auth()->id(),

            'tipo' => 'CIERRE',

            'monto' => $request->monto_cierre,

            'descripcion' => 'Cierre de caja',

        ]);
    });

    return redirect()
        ->route('cajas.index')
        ->with('success', 'Caja cerrada correctamente.');
}

    public function createEgreso(Caja $caja)
    {
        $this->validarCajaParaEgreso($caja);

        return view('cajas.egreso', compact('caja'));
    }

    public function storeEgreso(Request $request, Caja $caja)
    {
        $this->validarCajaParaEgreso($caja);

        $data = $request->validate([
            'referencia' => 'required|string|max:120',
            'fecha_movimiento' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
        ]);

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => auth()->id(),
            'tipo' => 'EGRESO',
            'monto' => $data['monto'],
            'fecha_movimiento' => $data['fecha_movimiento'],
            'referencia' => $data['referencia'],
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        return redirect()
            ->route('cajas.show', $caja)
            ->with('success', 'Egreso de caja registrado correctamente.');
    }

    public function createTransferencia(Caja $caja)
    {
        $this->validarCajaParaTransferencia($caja);

        $resumen = $this->resumenCaja($caja);
        $disponible = $resumen['disponible'];

        return view('cajas.transferencia', compact('caja', 'disponible', 'resumen'));
    }

    public function storeTransferencia(Request $request, Caja $caja)
    {
        $this->validarCajaParaTransferencia($caja);

        $data = $request->validate([
            'referencia' => 'required|string|max:120',
            'fecha_movimiento' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $disponible = $this->efectivoDisponibleParaTransferencia($caja);

        if (round((float) $data['monto'], 2) > round($disponible, 2)) {
            return back()
                ->withInput()
                ->withErrors([
                    'monto' => 'La transferencia no puede superar el efectivo disponible para enviar al jefe.',
                ]);
        }

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'user_id' => auth()->id(),
            'tipo' => 'TRANSFERENCIA_JEFE',
            'monto' => $data['monto'],
            'fecha_movimiento' => $data['fecha_movimiento'],
            'referencia' => $data['referencia'],
            'descripcion' => $data['descripcion'] ?: 'Transferencia a jefe',
        ]);

        return redirect()
            ->route('cajas.index')
            ->with('success', 'Transferencia a jefe registrada correctamente. El disponible de la sucursal fue actualizado.');
    }

    public function createCorteMensual()
    {
        $user = auth()->user();
        $sucursalId = $user->visibleSucursalId();

        if (! $sucursalId) {
            return redirect()
                ->route('cajas.index')
                ->with('error', 'Selecciona una sucursal para registrar el corte mensual.');
        }

        $service = app(MonthlyCashCutoffService::class);
        $periodo = $service->pendingForSucursal($sucursalId)
            ?? $service->warningForSucursal($sucursalId);

        if (! $periodo) {
            return redirect()
                ->route('cajas.index')
                ->with('success', 'No hay corte mensual pendiente para esta sucursal.');
        }

        $caja = Caja::with(['usuario', 'sucursal'])
            ->where('sucursal_id', $sucursalId)
            ->where('estado', 'ABIERTA')
            ->latest()
            ->first();

        if (! $caja) {
            return redirect()
                ->route('cajas.index')
                ->with('error', 'Debes abrir caja antes de registrar el corte mensual. La apertura tomara el saldo del ultimo cierre.');
        }

        if ($service->cutoffExists($sucursalId, $periodo['year'], $periodo['month'])) {
            return redirect()
                ->route('cajas.index')
                ->with('success', 'El corte mensual de ' . $periodo['label'] . ' ya fue registrado.');
        }

        $resumen = $service->resumenCaja($caja);

        return view('cajas.corte-mensual', compact('caja', 'periodo', 'resumen'));
    }

    public function storeCorteMensual(Request $request)
    {
        $user = auth()->user();
        $sucursalId = $user->visibleSucursalId();

        abort_unless($sucursalId && $user->canAccessSucursal($sucursalId), 403);

        $data = $request->validate([
            'periodo_year' => 'required|integer|min:2026|max:2100',
            'periodo_month' => 'required|integer|min:1|max:12',
            'monto_transferido' => 'required|numeric|min:0',
            'referencia' => 'nullable|string|max:120',
            'observacion' => 'nullable|string|max:500',
        ]);

        $service = app(MonthlyCashCutoffService::class);
        $periodo = $service->pendingForSucursal($sucursalId)
            ?? $service->warningForSucursal($sucursalId);

        if (
            ! $periodo
            || (int) $data['periodo_year'] !== (int) $periodo['year']
            || (int) $data['periodo_month'] !== (int) $periodo['month']
        ) {
            return redirect()
                ->route('cajas.index')
                ->with('error', 'No hay un corte mensual pendiente para el periodo solicitado.');
        }

        if ($service->cutoffExists($sucursalId, (int) $data['periodo_year'], (int) $data['periodo_month'])) {
            return redirect()
                ->route('cajas.index')
                ->with('success', 'Este corte mensual ya fue registrado anteriormente.');
        }

        $caja = Caja::where('sucursal_id', $sucursalId)
            ->where('estado', 'ABIERTA')
            ->latest()
            ->first();

        if (! $caja) {
            return redirect()
                ->route('cajas.index')
                ->with('error', 'Debes tener caja abierta para registrar el corte mensual.');
        }

        DB::transaction(function () use ($service, $user, $sucursalId, $data) {
            if ($service->cutoffExists($sucursalId, (int) $data['periodo_year'], (int) $data['periodo_month'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'periodo_month' => 'Este corte mensual ya fue registrado anteriormente.',
                ]);
            }

            $caja = Caja::where('sucursal_id', $sucursalId)
                ->where('estado', 'ABIERTA')
                ->lockForUpdate()
                ->latest()
                ->first();

            if (! $caja) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'caja' => 'Debes tener caja abierta para registrar el corte mensual.',
                ]);
            }

            $resumen = $service->resumenCaja($caja);
            $montoTransferido = round((float) $data['monto_transferido'], 2);
            $disponible = round((float) $resumen['disponible_antes'], 2);

            if ($montoTransferido > $disponible) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'monto_transferido' => 'El monto transferido no puede superar el disponible de caja.',
                ]);
            }

            if ($montoTransferido > 0) {
                MovimientoCaja::create([
                    'caja_id' => $caja->id,
                    'user_id' => $user->id,
                    'tipo' => 'TRANSFERENCIA_JEFE',
                    'monto' => $montoTransferido,
                    'fecha_movimiento' => now(),
                    'referencia' => $data['referencia'] ?? 'CORTE-MENSUAL-' . $data['periodo_year'] . '-' . str_pad((string) $data['periodo_month'], 2, '0', STR_PAD_LEFT),
                    'descripcion' => ($data['observacion'] ?? null) ?: 'Corte mensual de caja',
                ]);
            }

            CorteMensualCaja::create([
                'sucursal_id' => $sucursalId,
                'caja_id' => $caja->id,
                'user_id' => $user->id,
                'periodo_year' => (int) $data['periodo_year'],
                'periodo_month' => (int) $data['periodo_month'],
                'saldo_inicial' => $resumen['saldo_inicial'],
                'ventas' => $resumen['ventas'],
                'egresos' => $resumen['egresos'],
                'transferencias_previas' => $resumen['transferencias_previas'],
                'disponible_antes' => $disponible,
                'monto_transferido' => $montoTransferido,
                'saldo_restante' => round($disponible - $montoTransferido, 2),
                'fecha_corte' => now(),
                'referencia' => $data['referencia'] ?? null,
                'observacion' => $data['observacion'] ?? null,
            ]);
        });

        return redirect()
            ->route('cajas.index')
            ->with('success', 'Corte mensual registrado correctamente.');
    }

    public function show(Caja $caja)
    {
        $this->authorizeCajaVisibility($caja);

        $caja->load([
            'usuario',
            'sucursal',
            'movimientos' => fn ($query) => $query->latest('fecha_movimiento')->latest(),
            'movimientos.usuario',
        ]);

        $resumen = $this->resumenCaja($caja);
        $ventas = $resumen['ventas'];
        $egresos = $resumen['egresos'];
        $transferencias = $resumen['transferencias'];
        $totalSistema = $this->totalSistemaMostrado($caja, (float) $resumen['disponible']);
        $diferencia = $this->diferenciaCaja($caja, $totalSistema);

        return view('cajas.show', compact(
            'caja',
            'ventas',
            'egresos',
            'transferencias',
            'totalSistema',
            'diferencia'
        ));
    }

    private function egresosRegistrados(Caja $caja): float
    {
        return (float) MovimientoCaja::where('caja_id', $caja->id)
            ->where('tipo', 'EGRESO')
            ->sum('monto');
    }

    private function transferenciasRegistradas(Caja $caja): float
    {
        return (float) MovimientoCaja::where('caja_id', $caja->id)
            ->where('tipo', 'TRANSFERENCIA_JEFE')
            ->sum('monto');
    }

    private function salidasRegistradas(Caja $caja): float
    {
        return (float) MovimientoCaja::where('caja_id', $caja->id)
            ->whereIn('tipo', ['EGRESO', 'TRANSFERENCIA_JEFE'])
            ->sum('monto');
    }

    private function ventasRegistradas(Caja $caja): float
    {
        return (float) MovimientoCaja::where('caja_id', $caja->id)
            ->where('tipo', 'VENTA')
            ->sum('monto');
    }

    private function validarCajaParaEgreso(Caja $caja): void
    {
        $this->authorizeCajaOperation($caja);

        if ($caja->estado === 'CERRADA') {
            abort(403, 'No se pueden registrar egresos en una caja cerrada.');
        }
    }

    private function validarCajaParaTransferencia(Caja $caja): void
    {
        $this->authorizeSucursalAccess($caja);

        if ($caja->estado === 'CERRADA') {
            abort(403, 'No se pueden registrar transferencias en una caja cerrada.');
        }

        $user = auth()->user();

        abort_unless(
            $user->can('caja.abrir')
                || $user->can('ventas.crear')
                || $user->can('caja.ver_cierres')
                || $user->hasAnyRole(['Administrador', 'Administrador Global', 'Super Usuario']),
            403,
            'Tu usuario no tiene permiso para registrar transferencias.'
        );
    }

    private function efectivoDisponibleParaTransferencia(Caja $caja): float
    {
        return $this->resumenCaja($caja)['disponible'];
    }

    private function resumenCaja(Caja $caja): array
    {
        $apertura = (float) $caja->monto_apertura;
        $ventas = $this->ventasRegistradas($caja);
        $egresos = $this->egresosRegistrados($caja);
        $transferencias = $this->transferenciasRegistradas($caja);
        $salidas = $egresos + $transferencias;

        return [
            'apertura' => $apertura,
            'ventas' => $ventas,
            'egresos' => $egresos,
            'transferencias' => $transferencias,
            'salidas' => $salidas,
            'disponible' => $apertura + $ventas - $salidas,
        ];
    }

    private function diferenciaCaja(Caja $caja, float $totalSistema): float
    {
        if ($caja->estado !== 'CERRADA' || $caja->monto_cierre === null) {
            return 0;
        }

        return (float) $caja->monto_cierre - $totalSistema;
    }

    private function totalSistemaMostrado(Caja $caja, float $disponibleActual): float
    {
        if ($caja->estado === 'CERRADA') {
            return (float) $caja->total_sistema;
        }

        return $disponibleActual;
    }

    private function ultimaCajaCerrada(int $sucursalId): ?Caja
    {
        return Caja::where('sucursal_id', $sucursalId)
            ->where('estado', 'CERRADA')
            ->whereNotNull('monto_cierre')
            ->latest('fecha_cierre')
            ->latest('id')
            ->first();
    }

    private function canOverrideOpeningAmount($user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Administrador Global', 'Super Usuario']);
    }

    private function authorizeSucursalAccess(Caja $caja): void
    {
        abort_unless(auth()->user()->canAccessSucursal($caja->sucursal_id), 403, 'No tienes acceso a la sucursal de esta caja.');
    }

    private function authorizeCajaOperation(Caja $caja): void
    {
        $this->authorizeSucursalAccess($caja);

        abort_unless($caja->estado === 'ABIERTA' || auth()->user()->can('caja.ver_cierres'), 403);
    }

    private function authorizeCajaVisibility(Caja $caja): void
    {
        $this->authorizeSucursalAccess($caja);
    }
}
