<x-app-layout>
    @php
        $canManageGlobalProducts = Auth::user()->hasAnyRole(['Administrador Global', 'Super Usuario']);
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Productos
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4 flex gap-2">

                @if($canManageGlobalProducts)
                    <a href="{{ route('productos.create') }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded">
                        Nuevo Producto
                    </a>
                @endif

                @can('categorias.ver')
                    <a href="{{ route('categorias.index') }}"
                       class="px-4 py-2 bg-gray-600 text-white rounded">
                        Categorías
                    </a>
                @endcan

            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Código</th>
                            <th class="p-2 border">Producto</th>
                            <th class="p-2 border">Categoría</th>
                            <th class="p-2 border">Costo</th>
                            <th class="p-2 border">Precio</th>
                            <th class="p-2 border">Stock mín.</th>
                            <th class="p-2 border">Vence</th>
                            <th class="p-2 border">Estado</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($productos as $producto)
                            <tr>
                                <td class="p-2 border">
                                    {{ $producto->codigo_barra }}
                                </td>

                                <td class="p-2 border">
                                    {{ $producto->nombre }}
                                </td>

                                <td class="p-2 border">
                                    {{ $producto->categoria->nombre ?? 'Sin categoría' }}
                                </td>

                                <td class="p-2 border">
                                    Q {{ number_format($producto->costo, 2) }}
                                </td>

                                <td class="p-2 border">
                                    Q {{ number_format($producto->precio_venta, 2) }}
                                </td>

                                <td class="p-2 border">
                                    {{ $producto->stock_minimo }}
                                </td>

                                <td class="p-2 border">
                                    {{ $producto->fecha_vencimiento ?? 'N/A' }}
                                </td>

                                <td class="p-2 border">
                                    {{ $producto->estado ? 'Activo' : 'Inactivo' }}
                                </td>

                                <td class="p-2 border whitespace-nowrap">

                                    @if($canManageGlobalProducts)
                                        <a href="{{ route('productos.edit', $producto) }}"
                                           class="text-blue-600">
                                            Editar
                                        </a>

                                        <form action="{{ route('productos.destroy', $producto) }}"
                                              method="POST"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    onclick="return confirm('¿Deseas desactivar este producto?')"
                                                    class="text-red-600 ml-3">
                                                Desactivar
                                            </button>
                                        </form>
                                    @endif

                                    @unless($canManageGlobalProducts)
                                        <span class="text-gray-400">
                                            Gestion global protegida
                                        </span>
                                    @endunless

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-4 text-center text-gray-500">
                                    No hay productos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $productos->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
