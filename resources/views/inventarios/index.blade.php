<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Inventario por Sucursal
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
                @can('inventario.ajustar')
                    <a href="{{ route('inventarios.entrada') }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded">
                        Nueva Entrada
                    </a>

                    <a href="{{ route('inventarios.carga-inicial') }}"
                       class="px-4 py-2 bg-indigo-700 text-white rounded">
                        Carga Inicial
                    </a>
                @endcan

                @can('inventario.ajustar')
                    <a href="{{ route('inventarios.fisico') }}"
                       class="px-4 py-2 bg-slate-800 text-white rounded">
                        Inventario Fisico
                    </a>
                @endcan
            </div>

            @if($canDownloadBranchInventories && $sucursales->isNotEmpty())
                <div class="mb-4 rounded border border-emerald-200 bg-white p-4 shadow">
                    <div class="mb-3">
                        <h3 class="font-bold text-gray-800">
                            Descarga de inventarios por sucursal
                        </h3>
                        <p class="text-sm text-gray-500">
                            Disponible solo para Super Usuario. Descarga un Excel independiente por sucursal.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach($sucursales as $sucursal)
                            <a href="{{ route('inventarios.sucursales.descargar', $sucursal) }}"
                               class="rounded bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                                Descargar {{ $sucursal->nombre }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="GET"
                  action="{{ route('inventarios.index') }}"
                  class="mb-4 bg-white shadow rounded p-4"
                  data-async-filter-form
                  data-results-target="#inventarios-results">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_180px_150px_auto_auto] md:items-end">
                    <div>
                        <label for="q" class="block text-sm font-semibold text-gray-700 mb-1">
                            Buscar
                        </label>
                        <input type="search"
                               id="q"
                               name="q"
                               value="{{ $search }}"
                               placeholder="Producto, codigo, laboratorio o sucursal"
                               class="w-full rounded border-gray-300"
                               autocomplete="off"
                               data-auto-filter-input>
                    </div>

                    @if($sucursales->isNotEmpty())
                        <div>
                            <label for="sucursal_id" class="block text-sm font-semibold text-gray-700 mb-1">
                                Sucursal
                            </label>
                            <select id="sucursal_id"
                                    name="sucursal_id"
                                    class="w-full rounded border-gray-300"
                                    data-auto-filter-select>
                                <option value="">Todas</option>
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}" @selected((string) $selectedSucursalId === (string) $sucursal->id)>
                                        {{ $sucursal->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="estado_stock" class="block text-sm font-semibold text-gray-700 mb-1">
                            Estado
                        </label>
                        <select id="estado_stock"
                                name="estado_stock"
                                class="w-full rounded border-gray-300"
                                data-auto-filter-select>
                            <option value="">Todos</option>
                            <option value="bajo" @selected($estadoStock === 'bajo')>Stock bajo</option>
                            <option value="normal" @selected($estadoStock === 'normal')>Normal</option>
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

                    <button type="button"
                            class="px-4 py-2 bg-gray-600 text-white rounded text-center"
                            data-clear-filter>
                        Limpiar
                    </button>
                </div>
            </form>

            <div id="inventarios-results">
                @include('inventarios.partials.results', [
                    'inventarios' => $inventarios,
                    'canAdjustInventory' => $canAdjustInventory,
                ])
            </div>

        </div>
    </div>

    <script>
        document.querySelectorAll('[data-async-filter-form]').forEach(form => {
            const input = form.querySelector('[data-auto-filter-input]');
            const selects = form.querySelectorAll('[data-auto-filter-select]');
            const clearButton = form.querySelector('[data-clear-filter]');
            const target = document.querySelector(form.dataset.resultsTarget);
            let timeout;
            let controller;

            function buildUrl(baseUrl = form.action) {
                const params = new URLSearchParams(new FormData(form));

                for (const [key, value] of [...params.entries()]) {
                    if (value === '') {
                        params.delete(key);
                    }
                }

                return params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
            }

            async function loadResults(url = buildUrl()) {
                if (!target) {
                    return;
                }

                controller?.abort();
                controller = new AbortController();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo cargar la busqueda.');
                    }

                    target.innerHTML = await response.text();
                    window.history.replaceState({}, '', url);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        form.submit();
                    }
                }
            }

            function submitWithDelay() {
                clearTimeout(timeout);

                timeout = setTimeout(() => {
                    loadResults();
                }, 500);
            }

            input?.addEventListener('input', submitWithDelay);

            selects.forEach(select => {
                select.addEventListener('change', () => {
                    loadResults();
                });
            });

            clearButton?.addEventListener('click', () => {
                form.querySelectorAll('input, select').forEach(field => {
                    if (field.name !== 'per_page') {
                        field.value = '';
                    }
                });

                loadResults(form.action);
            });

            form.addEventListener('submit', event => {
                event.preventDefault();
                loadResults();
            });

        });
    </script>
</x-app-layout>
