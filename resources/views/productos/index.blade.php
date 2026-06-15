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

            <div class="mb-4 flex flex-wrap gap-2">

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

            <form method="GET" action="{{ route('productos.index') }}" class="mb-4 bg-white shadow rounded p-4" data-auto-filter-form>
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_150px_auto_auto] md:items-end">
                    <div>
                        <label for="q" class="block text-sm font-semibold text-gray-700 mb-1">
                            Buscar
                        </label>
                        <input type="search"
                               id="q"
                               name="q"
                               value="{{ $search }}"
                               placeholder="Nombre, codigo o laboratorio"
                               class="w-full rounded border-gray-300"
                               autocomplete="off"
                               data-auto-filter-input>
                    </div>

                    <div>
                        <label for="categoria_id" class="block text-sm font-semibold text-gray-700 mb-1">
                            Categoria
                        </label>
                        <select id="categoria_id"
                                name="categoria_id"
                                class="w-full rounded border-gray-300"
                                data-auto-filter-select>
                            <option value="">Todas</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected((string) $categoriaId === (string) $categoria->id)>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="per_page" class="block text-sm font-semibold text-gray-700 mb-1">
                            Mostrar
                        </label>
                        <select id="per_page"
                                name="per_page"
                                class="w-full rounded border-gray-300"
                                data-auto-filter-select>
                            @foreach([25, 50, 100, 200] as $option)
                                <option value="{{ $option }}" @selected($perPage === $option)>
                                    {{ $option }} registros
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded">
                        Filtrar
                    </button>

                    <a href="{{ route('productos.index') }}"
                       class="px-4 py-2 bg-gray-600 text-white rounded text-center">
                        Limpiar
                    </a>
                </div>
            </form>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <div class="mb-3 text-sm text-gray-600">
                    Mostrando {{ $productos->firstItem() ?? 0 }}-{{ $productos->lastItem() ?? 0 }}
                    de {{ $productos->total() }} productos
                </div>

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

    <script>
        document.querySelectorAll('[data-auto-filter-form]').forEach(form => {
            const input = form.querySelector('[data-auto-filter-input]');
            const selects = form.querySelectorAll('[data-auto-filter-select]');
            let timeout;

            function submitWithDelay() {
                clearTimeout(timeout);

                timeout = setTimeout(() => {
                    form.requestSubmit();
                }, 450);
            }

            input?.addEventListener('input', submitWithDelay);

            selects.forEach(select => {
                select.addEventListener('change', () => {
                    form.requestSubmit();
                });
            });
        });
    </script>
</x-app-layout>
