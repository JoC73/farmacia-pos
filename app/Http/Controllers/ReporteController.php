<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Sucursal;
use App\Models\User;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index()
    {
        return view('reportes.index');
    }

    public function ventas(Request $request)
    {
        $query = Venta::with([
            'cliente',
            'usuario',
            'sucursal',
        ])->where('estado', 'FINALIZADA');

        /*
        |--------------------------------------------------------------------------
        | FILTRO FECHA INICIO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('fecha_inicio')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->fecha_inicio
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO FECHA FIN
        |--------------------------------------------------------------------------
        */

        if ($request->filled('fecha_fin')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->fecha_fin
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO SUCURSAL
        |--------------------------------------------------------------------------
        */

        if ($request->filled('sucursal_id')) {

            $query->where(
                'sucursal_id',
                $request->sucursal_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO USUARIO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('user_id')) {

            $query->where(
                'user_id',
                $request->user_id
            );
        }

        $ventas = $query
            ->latest()
            ->paginate(30)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | TOTAL GENERAL
        |--------------------------------------------------------------------------
        */

        $totalVentas = (clone $query)->sum('total');

        /*
        |--------------------------------------------------------------------------
        | DATOS FILTROS
        |--------------------------------------------------------------------------
        */

        $sucursales = Sucursal::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $usuarios = User::orderBy('name')
            ->get();

        return view('reportes.ventas', compact(
            'ventas',
            'totalVentas',
            'sucursales',
            'usuarios'
        ));
    }
}
