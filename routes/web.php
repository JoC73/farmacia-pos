<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\EntradaInventarioController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RolPermisoController;


Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/health', fn () => response('ok', 200));

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PERFIL
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | CATEGORÍAS
    |--------------------------------------------------------------------------
    */

    Route::resource('categorias', CategoriaController::class)
        ->middleware('permission:productos.ver');

    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS
    |--------------------------------------------------------------------------
    */

    Route::resource('productos', ProductoController::class)
        ->middleware('permission:productos.ver');

    /*
    |--------------------------------------------------------------------------
    | INVENTARIOS
    |--------------------------------------------------------------------------
    */

    Route::get('/inventarios', [InventarioController::class, 'index'])
        ->name('inventarios.index')
        ->middleware('permission:inventario.ver');

    /*
    |--------------------------------------------------------------------------
    | ENTRADAS DE INVENTARIO
    |--------------------------------------------------------------------------
    */

    Route::get('/inventarios/entrada', [EntradaInventarioController::class, 'create'])
        ->name('inventarios.entrada')
        ->middleware('permission:inventario.ajustar');

    Route::post('/inventarios/entrada', [EntradaInventarioController::class, 'store'])
        ->name('inventarios.entrada.store')
        ->middleware('permission:inventario.ajustar');

    /*
    |--------------------------------------------------------------------------
    | VENTAS
    |--------------------------------------------------------------------------
    */

    Route::get('/ventas', [VentaController::class, 'index'])
        ->name('ventas.index')
        ->middleware('permission:ventas.ver');

    Route::get('/ventas/create', [VentaController::class, 'create'])
        ->name('ventas.create')
        ->middleware('permission:ventas.crear');

    Route::post('/ventas', [VentaController::class, 'store'])
        ->name('ventas.store')
        ->middleware('permission:ventas.crear');

    
    /*
|--------------------------------------------------------------------------
| CLIENTES
|--------------------------------------------------------------------------
*/

Route::resource('clientes', ClienteController::class)
    ->middleware('permission:ventas.ver');

});

Route::get('/ventas/{venta}', [VentaController::class, 'show'])
    ->name('ventas.show')
    ->middleware('permission:ventas.ver');


    /*
|--------------------------------------------------------------------------
| PROVEEDORES
|--------------------------------------------------------------------------
*/

Route::resource('proveedores', ProveedorController::class)
    ->middleware('permission:compras.ver');


    /*
|--------------------------------------------------------------------------
| COMPRAS
|--------------------------------------------------------------------------
*/

Route::get('/compras', [CompraController::class, 'index'])
    ->name('compras.index')
    ->middleware('permission:compras.ver');

Route::get('/compras/create', [CompraController::class, 'create'])
    ->name('compras.create')
    ->middleware('permission:compras.crear');

Route::post('/compras', [CompraController::class, 'store'])
    ->name('compras.store')
    ->middleware('permission:compras.crear');

Route::get('/compras/{compra}', [CompraController::class, 'show'])
    ->name('compras.show')
    ->middleware('permission:compras.ver');


    /*
|--------------------------------------------------------------------------
| CAJAS
|--------------------------------------------------------------------------
*/
Route::get('/cajas', [CajaController::class, 'index'])
    ->name('cajas.index')
    ->middleware('permission:caja.ver_cierres');

Route::get('/cajas/apertura', [CajaController::class, 'createApertura'])
    ->name('cajas.apertura')
    ->middleware('permission:caja.abrir');

Route::post('/cajas/apertura', [CajaController::class, 'storeApertura'])
    ->name('cajas.apertura.store')
    ->middleware('permission:caja.abrir');

Route::get('/cajas/{caja}/cierre', [CajaController::class, 'createCierre'])
    ->name('cajas.cierre')
    ->middleware('permission:caja.cerrar');

Route::post('/cajas/{caja}/cierre', [CajaController::class, 'storeCierre'])
    ->name('cajas.cierre.store')
    ->middleware('permission:caja.cerrar');

Route::get('/cajas/{caja}', [CajaController::class, 'show'])
    ->name('cajas.show')
    ->middleware('permission:caja.ver_cierres');

/*
|--------------------------------------------------------------------------
| SUCURSALES
|--------------------------------------------------------------------------
*/

Route::resource('sucursales', SucursalController::class)
    ->parameters([
        'sucursales' => 'sucursal'
    ])
    ->middleware('permission:sucursales.ver');

/*
|--------------------------------------------------------------------------
| USUARIOS
|--------------------------------------------------------------------------
*/

Route::resource('usuarios', UsuarioController::class)
    ->parameters([
        'usuarios' => 'usuario'
    ])
    ->middleware('permission:usuarios.ver');

    /*
|--------------------------------------------------------------------------
| REPORTES
|--------------------------------------------------------------------------
*/

Route::get('/reportes', [ReporteController::class, 'index'])
    ->name('reportes.index')
    ->middleware('permission:reportes.ventas');

Route::get('/reportes/ventas', [ReporteController::class, 'ventas'])
    ->name('reportes.ventas')
    ->middleware('permission:reportes.ventas');

/*
|--------------------------------------------------------------------------
| ROLES Y PERMISOS
|--------------------------------------------------------------------------
*/

Route::get('/roles', [RolPermisoController::class, 'index'])
    ->name('roles.index')
    ->middleware('permission:roles.ver');

Route::get('/roles/create', [RolPermisoController::class, 'create'])
    ->name('roles.create')
    ->middleware('permission:roles.crear');

Route::post('/roles', [RolPermisoController::class, 'store'])
    ->name('roles.store')
    ->middleware('permission:roles.crear');

Route::get('/roles/{role}/edit', [RolPermisoController::class, 'edit'])
    ->name('roles.edit')
    ->middleware('permission:roles.editar');

Route::put('/roles/{role}', [RolPermisoController::class, 'update'])
    ->name('roles.update')
    ->middleware('permission:roles.editar');
    


require __DIR__.'/auth.php';
