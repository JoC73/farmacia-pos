<x-app-layout>
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

            <form method="GET"
                  action="{{ route('productos.index') }}"
                  class="mb-4 bg-white shadow rounded p-4"
                  data-async-filter-form
                  data-results-target="#productos-results">
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

                    <button type="button"
                            class="px-4 py-2 bg-gray-600 text-white rounded text-center"
                            data-clear-filter>
                        Limpiar
                    </button>
                </div>
            </form>

            <div id="productos-results">
                @include('productos.partials.results', [
                    'productos' => $productos,
                    'canManageGlobalProducts' => $canManageGlobalProducts,
                    'canAdjustLocalInventory' => $canAdjustLocalInventory,
                ])
            </div>

            @if($canAdjustLocalInventory)
                <div id="expiry-modal"
                     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4"
                     aria-hidden="true">
                    <div class="w-full max-w-md rounded bg-white shadow-xl">
                        <div class="border-b p-5">
                            <h3 class="text-lg font-bold text-gray-900">
                                Fecha de vencimiento
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Actualiza solo la fecha del producto en tu sucursal.
                            </p>
                        </div>

                        <form id="expiry-form" method="POST" action="">
                            @csrf
                            @method('PATCH')

                            <div class="space-y-4 p-5">
                                <div class="rounded bg-gray-50 p-4">
                                    <div class="text-sm text-gray-500">Producto</div>
                                    <div id="expiry-product-name" class="font-bold text-gray-900">-</div>
                                    <div id="expiry-branch-name" class="mt-1 text-sm text-gray-500">-</div>
                                </div>

                                <div>
                                    <label for="expiry-date" class="mb-1 block text-sm font-semibold text-gray-700">
                                        Fecha de vencimiento
                                    </label>
                                    <input id="expiry-date"
                                           type="date"
                                           name="fecha_vencimiento"
                                           class="w-full rounded border-gray-300">
                                    <p class="mt-1 text-sm text-gray-500">
                                        Puedes dejarla vacía para quitar la fecha registrada.
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 border-t bg-gray-50 p-4">
                                <button type="button"
                                        class="rounded bg-gray-600 px-4 py-2 text-white"
                                        data-close-expiry-modal>
                                    Cancelar
                                </button>

                                <button type="submit"
                                        class="rounded bg-blue-600 px-4 py-2 font-semibold text-white">
                                    Guardar fecha
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

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

            target?.addEventListener('click', event => {
                const link = event.target.closest('[data-async-pagination] a');

                if (link) {
                    event.preventDefault();
                    loadResults(link.href);
                }
            });
        });

        const expiryModal = document.getElementById('expiry-modal');
        const expiryForm = document.getElementById('expiry-form');
        const expiryDate = document.getElementById('expiry-date');
        const expiryProductName = document.getElementById('expiry-product-name');
        const expiryBranchName = document.getElementById('expiry-branch-name');

        document.addEventListener('click', event => {
            const openButton = event.target.closest('[data-open-expiry-modal]');

            if (openButton && expiryModal) {
                expiryForm.action = openButton.dataset.action;
                expiryDate.value = openButton.dataset.fecha || '';
                expiryProductName.textContent = openButton.dataset.producto || '-';
                expiryBranchName.textContent = openButton.dataset.sucursal || '-';
                expiryModal.classList.remove('hidden');
                expiryModal.classList.add('flex');
                expiryModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => expiryDate.focus(), 50);
            }

            if (event.target.closest('[data-close-expiry-modal]') && expiryModal) {
                expiryModal.classList.add('hidden');
                expiryModal.classList.remove('flex');
                expiryModal.setAttribute('aria-hidden', 'true');
            }

            if (event.target === expiryModal) {
                expiryModal.classList.add('hidden');
                expiryModal.classList.remove('flex');
                expiryModal.setAttribute('aria-hidden', 'true');
            }
        });
    </script>
</x-app-layout>
