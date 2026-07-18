<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;

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

            $caja->setAttribute('total_sistema_mostrado', $resumen['disponible']);
            $caja->setAttribute('diferencia_mostrada', $caja->estado === 'CERRADA' ? (float) $caja->diferencia : 0);
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
            'cajaAbiertaActual'
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
        $totalSistema = $resumen['disponible'];
        $diferencia = $caja->estado === 'CERRADA'
            ? (float) $caja->diferencia
            : 0;

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
