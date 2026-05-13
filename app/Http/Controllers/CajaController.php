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
        $cajas = Caja::with([
            'usuario',
            'sucursal',
        ])
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
        if ($caja->estado === 'CERRADA') {

            return redirect()
                ->route('cajas.index')
                ->with('error', 'La caja ya está cerrada.');
        }

        $ventas = Venta::where('user_id', $caja->user_id)
            ->whereBetween('created_at', [
                $caja->fecha_apertura,
                now()
            ])
            ->sum('total');

        return view('cajas.cierre', compact(
            'caja',
            'ventas'
        ));
    }

    public function storeCierre(Request $request, Caja $caja)
{
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
            ->whereBetween('created_at', [
                $caja->fecha_apertura,
                now()
            ])
            ->sum('total');

        $totalSistema =
            $caja->monto_apertura + $ventas;

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
    public function show(Caja $caja)
    {
        $caja->load([
            'usuario',
            'sucursal',
            'movimientos.usuario',
        ]);

        return view('cajas.show', compact('caja'));
    }
}