<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\EntradaInventarioController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\InventarioFisicoController;
use App\Http\Controllers\CargaInicialProductoController;
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
use App\Http\Controllers\PremiumModuleController;


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

    Route::get('/inventarios/sucursales/{sucursal}/descargar', [InventarioController::class, 'descargarSucursal'])
        ->name('inventarios.sucursales.descargar')
        ->middleware(['permission:inventario.ver', 'role:Super Usuario']);

    Route::get('/inventarios/{inventario}/ajustar', [InventarioController::class, 'ajustar'])
        ->name('inventarios.ajustar')
        ->middleware(['permission:inventario.ajustar', 'role:Administrador|Administrador Global|Super Usuario']);

    Route::patch('/inventarios/{inventario}/ajustar', [InventarioController::class, 'actualizarExistencia'])
        ->name('inventarios.ajustar.update')
        ->middleware(['permission:inventario.ajustar', 'role:Administrador|Administrador Global|Super Usuario']);

    Route::patch('/inventarios/{inventario}/vencimiento', [InventarioController::class, 'actualizarFechaVencimiento'])
        ->name('inventarios.vencimiento.update')
        ->middleware(['permission:inventario.ajustar', 'role:Administrador|Administrador Global|Super Usuario']);

    Route::get('/inventarios/fisico', [InventarioFisicoController::class, 'index'])
        ->name('inventarios.fisico')
        ->middleware(['permission:inventario.ajustar', 'premium:physical_inventory']);

    Route::get('/inventarios/fisico/plantilla', [InventarioFisicoController::class, 'download'])
        ->name('inventarios.fisico.plantilla')
        ->middleware(['permission:inventario.ajustar', 'premium:physical_inventory']);

    Route::post('/inventarios/fisico/preview', [InventarioFisicoController::class, 'preview'])
        ->name('inventarios.fisico.preview')
        ->middleware(['permission:inventario.ajustar', 'premium:physical_inventory']);

    Route::post('/inventarios/fisico/confirmar', [InventarioFisicoController::class, 'confirm'])
        ->name('inventarios.fisico.confirmar')
        ->middleware(['permission:inventario.ajustar', 'premium:physical_inventory']);

    Route::get('/inventarios/carga-inicial', [CargaInicialProductoController::class, 'index'])
        ->name('inventarios.carga-inicial')
        ->middleware(['permission:inventario.ajustar', 'premium:initial_product_import']);

    Route::get('/inventarios/carga-inicial/plantilla', [CargaInicialProductoController::class, 'download'])
        ->name('inventarios.carga-inicial.plantilla')
        ->middleware(['permission:inventario.ajustar', 'premium:initial_product_import']);

    Route::post('/inventarios/carga-inicial/preview', [CargaInicialProductoController::class, 'preview'])
        ->name('inventarios.carga-inicial.preview')
        ->middleware(['permission:inventario.ajustar', 'premium:initial_product_import']);

    Route::post('/inventarios/carga-inicial/confirmar', [CargaInicialProductoController::class, 'confirm'])
        ->name('inventarios.carga-inicial.confirmar')
        ->middleware(['permission:inventario.ajustar', 'premium:initial_product_import']);

    /*
    |--------------------------------------------------------------------------
    | ENTRADAS DE INVENTARIO
    |--------------------------------------------------------------------------
    */

    Route::get('/inventarios/entrada', [EntradaInventarioController::class, 'create'])
        ->name('inventarios.entrada')
        ->middleware('permission:inventario.ajustar');

    Route::get('/inventarios/entrada/productos/buscar', [EntradaInventarioController::class, 'searchProducts'])
        ->name('inventarios.entrada.productos.buscar')
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

    Route::get('/ventas/productos/buscar', [VentaController::class, 'searchProducts'])
        ->name('ventas.productos.buscar')
        ->middleware('permission:ventas.crear');

    Route::post('/ventas', [VentaController::class, 'store'])
        ->name('ventas.store')
        ->middleware('permission:ventas.crear');

    Route::get('/ventas/sucursales/{sucursal}/descargar', [VentaController::class, 'descargarSucursal'])
        ->name('ventas.sucursales.descargar')
        ->middleware(['permission:ventas.ver', 'role:Super Usuario']);

    
    /*
|--------------------------------------------------------------------------
| CLIENTES
|--------------------------------------------------------------------------
*/

Route::resource('clientes', ClienteController::class)
    ->middleware('permission:ventas.ver');

});

Route::middleware('auth')->group(function () {

Route::get('/ventas/{venta}', [VentaController::class, 'show'])
    ->name('ventas.show')
    ->middleware('permission:ventas.ver');

Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
    ->name('ventas.anular')
    ->middleware(['permission:ventas.anular', 'role:Administrador|Administrador Global|Super Usuario']);


    /*
|--------------------------------------------------------------------------
| PROVEEDORES
|--------------------------------------------------------------------------
*/

Route::resource('proveedores', ProveedorController::class)
    ->parameters(['proveedores' => 'proveedor'])
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

Route::get('/compras/productos/buscar', [CompraController::class, 'searchProducts'])
    ->name('compras.productos.buscar')
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
    ->middleware('permission:caja.abrir|ventas.crear|caja.ver_cierres');

Route::get('/cajas/apertura', [CajaController::class, 'createApertura'])
    ->name('cajas.apertura')
    ->middleware('permission:caja.abrir');

Route::post('/cajas/apertura', [CajaController::class, 'storeApertura'])
    ->name('cajas.apertura.store')
    ->middleware('permission:caja.abrir');

Route::get('/cajas/corte-mensual', [CajaController::class, 'createCorteMensual'])
    ->name('cajas.corte-mensual')
    ->middleware('permission:caja.cerrar|caja.ver_cierres');

Route::post('/cajas/corte-mensual', [CajaController::class, 'storeCorteMensual'])
    ->name('cajas.corte-mensual.store')
    ->middleware('permission:caja.cerrar|caja.ver_cierres');

Route::get('/cajas/{caja}/cierre', [CajaController::class, 'createCierre'])
    ->name('cajas.cierre')
    ->middleware('permission:caja.cerrar');

Route::post('/cajas/{caja}/cierre', [CajaController::class, 'storeCierre'])
    ->name('cajas.cierre.store')
    ->middleware('permission:caja.cerrar');

Route::get('/cajas/{caja}/egreso', [CajaController::class, 'createEgreso'])
    ->name('cajas.egreso')
    ->middleware(['permission:caja.ver_cierres', 'premium:cash_expenses']);

Route::post('/cajas/{caja}/egreso', [CajaController::class, 'storeEgreso'])
    ->name('cajas.egreso.store')
    ->middleware(['permission:caja.ver_cierres', 'premium:cash_expenses']);

Route::get('/cajas/{caja}/transferencia', [CajaController::class, 'createTransferencia'])
    ->name('cajas.transferencia')
    ->middleware('permission:caja.abrir|ventas.crear|caja.ver_cierres');

Route::post('/cajas/{caja}/transferencia', [CajaController::class, 'storeTransferencia'])
    ->name('cajas.transferencia.store')
    ->middleware('permission:caja.abrir|ventas.crear|caja.ver_cierres');

Route::get('/cajas/{caja}', [CajaController::class, 'show'])
    ->name('cajas.show')
    ->middleware('permission:caja.abrir|ventas.crear|caja.ver_cierres');

/*
|--------------------------------------------------------------------------
| SUCURSALES
|--------------------------------------------------------------------------
*/

Route::get('/sucursales', [SucursalController::class, 'index'])
    ->name('sucursales.index')
    ->middleware('permission:sucursales.ver');

Route::get('/sucursales/create', [SucursalController::class, 'create'])
    ->name('sucursales.create')
    ->middleware(['permission:sucursales.ver', 'premium:branch_creation']);

Route::post('/sucursales', [SucursalController::class, 'store'])
    ->name('sucursales.store')
    ->middleware(['permission:sucursales.ver', 'premium:branch_creation']);

Route::get('/sucursales/{sucursal}', [SucursalController::class, 'show'])
    ->name('sucursales.show')
    ->middleware('permission:sucursales.ver');

Route::get('/sucursales/{sucursal}/edit', [SucursalController::class, 'edit'])
    ->name('sucursales.edit')
    ->middleware('permission:sucursales.ver');

Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])
    ->name('sucursales.update')
    ->middleware('permission:sucursales.ver');

Route::patch('/sucursales/{sucursal}', [SucursalController::class, 'update'])
    ->name('sucursales.update')
    ->middleware('permission:sucursales.ver');

Route::delete('/sucursales/{sucursal}', [SucursalController::class, 'destroy'])
    ->name('sucursales.destroy')
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

Route::get('/reportes/movimientos-sucursal', [ReporteController::class, 'movimientosSucursal'])
    ->name('reportes.movimientos-sucursal')
    ->middleware('permission:reportes.caja');

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

/*
|--------------------------------------------------------------------------
| MODULOS PREMIUM
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/premium/bloqueado/{moduleCode}', [PremiumModuleController::class, 'locked'])
        ->name('premium.locked');

    Route::get('/premium/modulos', [PremiumModuleController::class, 'index'])
        ->name('premium.index')
        ->middleware('role:Super Usuario');

    Route::patch('/premium/modulos/{module}/toggle', [PremiumModuleController::class, 'toggle'])
        ->name('premium.toggle')
        ->middleware('role:Super Usuario');

    Route::post('/premium/limpieza-sucursal', [PremiumModuleController::class, 'resetBranchProducts'])
        ->name('premium.branch-cleanup')
        ->middleware('role:Super Usuario');
});

});
    


require __DIR__.'/auth.php';
