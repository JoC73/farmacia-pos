<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $sucursalId = auth()->user()->visibleSucursalId();
        $perPage = $this->validPerPage($request->integer('per_page', 50));
        $search = trim((string) $request->input('q', ''));
        $categoriaId = $request->input('categoria_id');

        $productos = Producto::with('categoria')
            ->when($sucursalId, fn ($query) => $query->with([
                'inventarios' => fn ($inventario) => $inventario->where('sucursal_id', $sucursalId),
            ]))
            ->where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereHas(
                'inventarios',
                fn ($inventario) => $inventario->where('sucursal_id', $sucursalId)
            ))
            ->when($search !== '', fn ($query) => $query->where(function ($subquery) use ($search) {
                $subquery
                    ->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo_barra', 'like', "%{$search}%")
                    ->orWhere('laboratorio', 'like', "%{$search}%")
                    ->orWhereHas('categoria', fn ($categoria) => $categoria->where('nombre', 'like', "%{$search}%"));
            }))
            ->when($categoriaId, fn ($query) => $query->where('categoria_id', $categoriaId))
            ->ordenadoPorNombre()
            ->paginate($perPage)
            ->withQueryString();

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $canCreateProducts = $this->canCreateProducts();
        $canManageGlobalProducts = $this->canManageGlobalProducts();
        $canAdjustLocalInventory = auth()->user()->hasRole('Administrador')
            && ! $canManageGlobalProducts
            && auth()->user()->can('inventario.ajustar')
            && $sucursalId;

        if ($request->ajax()) {
            return view('productos.partials.results', compact(
                'productos',
                'canCreateProducts',
                'canManageGlobalProducts',
                'canAdjustLocalInventory'
            ));
        }

        return view('productos.index', compact(
            'productos',
            'categorias',
            'perPage',
            'search',
            'categoriaId',
            'canCreateProducts',
            'canManageGlobalProducts',
            'canAdjustLocalInventory'
        ));
    }

    public function create()
    {
        $this->authorizeProductCreation();

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $canSelectSucursal = auth()->user()->canViewAllSucursales();
        $sucursales = $canSelectSucursal
            ? Sucursal::where('estado', true)->orderBy('nombre')->get()
            : collect();
        $selectedSucursal = $canSelectSucursal ? null : auth()->user()->sucursal;

        return view('productos.create', compact(
            'categorias',
            'sucursales',
            'canSelectSucursal',
            'selectedSucursal'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeProductCreation();

        $rules = [

            'categoria_id' => 'nullable|exists:categorias,id',

            'codigo_barra' => 'required|string|max:120|unique:productos,codigo_barra',

            'nombre' => 'required|string|max:200',

            'laboratorio' => 'nullable|string|max:150',

            'costo' => 'required|numeric|min:0',

            'precio_venta' => 'required|numeric|min:0',

            'stock_minimo' => 'required|integer|min:0',

            'fecha_vencimiento' => 'nullable|date',

            'descripcion' => 'nullable|string',

            'existencia_inicial' => 'nullable|integer|min:0',

        ];

        if (auth()->user()->canViewAllSucursales()) {
            $rules['sucursal_id'] = 'required|exists:sucursales,id';
        }

        $data = $request->validate($rules);
        $sucursal = $this->targetSucursalForCreation($request);

        DB::transaction(function () use ($data, $sucursal) {
            $producto = Producto::create([

                'categoria_id' => $data['categoria_id'] ?? null,

                'codigo_barra' => $data['codigo_barra'],

                'nombre' => $data['nombre'],

                'laboratorio' => $data['laboratorio'] ?? null,

                'costo' => $data['costo'],

                'precio_venta' => $data['precio_venta'],

                'stock_minimo' => $data['stock_minimo'],

                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,

                'descripcion' => $data['descripcion'] ?? null,

                'estado' => true,

            ]);

            $existenciaInicial = (int) ($data['existencia_inicial'] ?? 0);

            Inventario::create([
                'producto_id' => $producto->id,
                'sucursal_id' => $sucursal->id,
                'existencia' => $existenciaInicial,
                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
            ]);

            if ($existenciaInicial > 0) {
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'sucursal_id' => $sucursal->id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => 'AJUSTE_ENTRADA',
                    'cantidad' => $existenciaInicial,
                    'existencia_anterior' => 0,
                    'existencia_nueva' => $existenciaInicial,
                    'referencia' => 'Alta de producto',
                    'observacion' => 'Producto creado y asignado a sucursal',
                ]);
            }
        });

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto creado y asignado a '.$sucursal->nombre.'.');
    }

    public function show(Producto $producto)
    {
        return redirect()->route('productos.index');
    }

    public function edit(Producto $producto)
    {
        $this->authorizeGlobalProductManagement();

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.edit', compact(
            'producto',
            'categorias'
        ));
    }

    public function update(Request $request, Producto $producto)
    {
        $this->authorizeGlobalProductManagement();

        $request->validate([

            'categoria_id' => 'nullable|exists:categorias,id',

            'codigo_barra' => 'required|string|max:120|unique:productos,codigo_barra,' . $producto->id,

            'nombre' => 'required|string|max:200',

            'laboratorio' => 'nullable|string|max:150',

            'costo' => 'required|numeric|min:0',

            'precio_venta' => 'required|numeric|min:0',

            'stock_minimo' => 'required|integer|min:0',

            'fecha_vencimiento' => 'nullable|date',

            'descripcion' => 'nullable|string',

            'estado' => 'nullable|boolean',

        ]);

        $producto->update([

            'categoria_id' => $request->categoria_id,

            'codigo_barra' => $request->codigo_barra,

            'nombre' => $request->nombre,

            'laboratorio' => $request->laboratorio,

            'costo' => $request->costo,

            'precio_venta' => $request->precio_venta,

            'stock_minimo' => $request->stock_minimo,

            'fecha_vencimiento' => $request->fecha_vencimiento,

            'descripcion' => $request->descripcion,

            'estado' => $request->has('estado'),

        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $this->authorizeGlobalProductManagement();

        $producto->update([
            'estado' => false,
        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto desactivado correctamente.');
    }

    private function authorizeGlobalProductManagement(): void
    {
        abort_unless($this->canManageGlobalProducts(), 403);
    }

    private function authorizeProductCreation(): void
    {
        abort_unless($this->canCreateProducts(), 403);
        abort_if(! auth()->user()->canViewAllSucursales() && ! auth()->user()->sucursal_id, 403);
    }

    private function canCreateProducts(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador', 'Administrador Global', 'Super Usuario']);
    }

    private function canManageGlobalProducts(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador Global', 'Super Usuario']);
    }

    private function targetSucursalForCreation(Request $request): Sucursal
    {
        if (auth()->user()->canViewAllSucursales()) {
            return Sucursal::where('estado', true)
                ->findOrFail($request->integer('sucursal_id'));
        }

        return Sucursal::where('estado', true)
            ->findOrFail(auth()->user()->sucursal_id);
    }

    private function validPerPage(int $perPage): int
    {
        return in_array($perPage, [25, 50, 100, 200], true) ? $perPage : 50;
    }
}
