<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Venta;
use App\Models\MovimientoCaja;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        $cajas = Caja::with([
            'usuario',
            'sucursal',
        ])
        ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
        ->latest()
        ->paginate(20);

        return view('cajas.index', compact('cajas'));
    }

    public function createApertura()
    {
        $cajaAbierta = Caja::where('user_id', auth()->id())
            ->where('estado', 'ABIERTA')
            ->exists();

        if ($cajaAbierta) {

            return redirect()
                ->route('cajas.index')
                ->with('error', 'Ya tienes una caja abierta.');
        }

        return view('cajas.apertura');
    }

    public function storeApertura(Request $request)
{
    $request->validate([

        'monto_apertura' => 'required|numeric|min:0',

    ]);

    /*
    |--------------------------------------------------------------------------
    | VALIDAR CAJA ABIERTA
    |--------------------------------------------------------------------------
    */

    $cajaAbierta = Caja::where('user_id', auth()->id())
        ->where('estado', 'ABIERTA')
        ->exists();

    if ($cajaAbierta) {

        return redirect()
            ->route('cajas.index')
            ->with(
                'error',
                'Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.'
            );
    }

    DB::transaction(function () use ($request) {

        $user = auth()->user();

        $caja = Caja::create([

            'sucursal_id' => $user->sucursal_id,

            'user_id' => $user->id,

            'monto_apertura' => $request->monto_apertura,

            'fecha_apertura' => now(),

            'estado' => 'ABIERTA',

        ]);

        MovimientoCaja::create([

            'caja_id' => $caja->id,

            'user_id' => $user->id,

            'tipo' => 'APERTURA',

            'monto' => $request->monto_apertura,

            'descripcion' => 'Apertura de caja',

        ]);
    });

    return redirect()
        ->route('cajas.index')
        ->with('success', 'Caja abierta correctamente.');
}

    public function createCierre(Caja $caja)
    {
        $this->authorizeCajaOperation($caja);

        if ($caja->estado === 'CERRADA') {

            return redirect()
                ->route('cajas.index')
                ->with('error', 'La caja ya está cerrada.');
        }

        $ventas = Venta::where('user_id', $caja->user_id)
            ->where('estado', 'FINALIZADA')
            ->whereBetween('created_at', [
                $caja->fecha_apertura,
                now()
            ])
            ->sum('total');

        $egresos = $this->egresosRegistrados($caja);
        $totalSistema = $caja->monto_apertura + $ventas - $egresos;

        return view('cajas.cierre', compact(
            'caja',
            'ventas',
            'egresos',
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

        $ventas = Venta::where('user_id', $caja->user_id)
            ->where('estado', 'FINALIZADA')
            ->whereBetween('created_at', [
                $caja->fecha_apertura,
                now()
            ])
            ->sum('total');

        $egresos = $this->egresosRegistrados($caja);

        $totalSistema =
            $caja->monto_apertura + $ventas - $egresos;

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

    public function show(Caja $caja)
    {
        $this->authorizeSucursalAccess($caja);

        $caja->load([
            'usuario',
            'sucursal',
            'movimientos.usuario',
        ]);

        return view('cajas.show', compact('caja'));
    }

    private function egresosRegistrados(Caja $caja): float
    {
        return (float) MovimientoCaja::where('caja_id', $caja->id)
            ->where('tipo', 'EGRESO')
            ->sum('monto');
    }

    private function validarCajaParaEgreso(Caja $caja): void
    {
        $this->authorizeCajaOperation($caja);

        if ($caja->estado === 'CERRADA') {
            abort(403, 'No se pueden registrar egresos en una caja cerrada.');
        }
    }

    private function authorizeSucursalAccess(Caja $caja): void
    {
        abort_unless(auth()->user()->canAccessSucursal($caja->sucursal_id), 403);
    }

    private function authorizeCajaOperation(Caja $caja): void
    {
        $this->authorizeSucursalAccess($caja);

        if ($caja->user_id !== auth()->id() && ! auth()->user()->can('caja.ver_cierres')) {
            abort(403, 'No tienes permiso para operar esta caja.');
        }
    }
}
