<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nueva Compra
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded p-6">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Registrar compra</h3>
                        <p class="text-sm text-gray-500">Ingresa productos, cantidades y costo de adquisicion.</p>
                    </div>

                    <a href="{{ route('compras.index') }}"
                       class="px-4 py-2 rounded text-center"
                       style="background-color: #6b7280; color: white;">
                        Volver
                    </a>
                </div>

                @if ($errors->any())

                    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded">

                        <ul class="list-disc list-inside">

                            @foreach ($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif

            @if(session('error'))
    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded">
        {{ session('error') }}
    </div>
@endif

                <form method="POST"
                      action="{{ route('compras.store') }}">

                    @csrf

                    <!-- PROVEEDOR -->
                    <div class="mb-4">

                        <label class="block font-medium mb-1">
                            Proveedor
                        </label>

                        <select name="proveedor_id"
                                class="w-full border-gray-300 rounded">

                            <option value="">
                                Seleccione proveedor
                            </option>

                            @foreach($proveedores as $proveedor)

                                <option value="{{ $proveedor->id }}">

                                    {{ $proveedor->nombre }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- FACTURA -->
                    <div class="mb-4">

                        <label class="block font-medium mb-1">
                            No. Factura
                        </label>

                        <input type="text"
                               name="numero_factura"
                               class="w-full border-gray-300 rounded">

                    </div>

                    <!-- PRODUCTOS -->
                    <div class="rounded border bg-slate-50">
                        <div class="flex flex-col gap-3 border-b bg-white p-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="font-bold text-gray-800">
                                    Productos
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Agrega productos por linea con cantidad, costo y fecha de vencimiento.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button"
                                        id="agregar-producto"
                                        class="rounded bg-blue-600 px-4 py-2 text-white">
                                    Agregar fila
                                </button>

                                <button type="button"
                                        id="agregar-cinco"
                                        class="rounded bg-slate-700 px-4 py-2 text-white">
                                    +5 filas
                                </button>
                            </div>
                        </div>

                        <div id="productos-body" class="space-y-3 p-4"></div>

                        <div id="sin-productos" class="hidden px-4 pb-4">
                            <div class="rounded border border-dashed border-gray-300 bg-white p-6 text-center text-gray-500">
                                No hay productos agregados.
                            </div>
                        </div>

                        <div class="sticky bottom-0 flex flex-col gap-2 border-t bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-gray-500">
                                Líneas: <span id="lineas-total">0</span>
                            </div>

                            <div class="text-2xl font-bold text-gray-900">
                                Total: Q <span id="total-general">0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- BOTONES -->
                    <div class="mt-6 flex gap-3">

                        <a href="{{ route('compras.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">

                            Cancelar

                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">

                            Guardar Compra

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

<script>
    const buscarProductosUrl = '{{ route('compras.productos.buscar') }}';

    let index = 0;
    let productSearchTimeout;
    let productSearchController;

    function agregarFila()
    {
        const tbody = document.getElementById('productos-body');
        const row = document.createElement('div');

        row.className = 'producto-item rounded border border-gray-200 bg-white p-4 shadow-sm';
        row.innerHTML = `
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <span class="row-number inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-700"></span>
                    <div>
                        <div class="text-sm font-semibold text-gray-800">Linea de compra</div>
                        <div class="producto-seleccionado text-xs text-gray-500">Sin producto seleccionado</div>
                    </div>
                </div>

                <button type="button" class="remover-fila self-start rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 sm:self-auto">
                    Quitar
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="relative lg:col-span-6">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Producto</label>
                    <input type="search"
                           class="producto-search-input w-full rounded border-gray-300"
                           placeholder="Buscar por nombre, codigo o laboratorio..."
                           autocomplete="off">
                    <input type="hidden" name="productos[${index}][producto_id]" class="producto-id-input">
                    <div class="producto-suggestions absolute z-50 mt-1 hidden max-h-72 w-full overflow-y-auto rounded border border-gray-200 bg-white shadow-xl"></div>
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Cantidad</label>
                    <input type="number" min="1" value="1" name="productos[${index}][cantidad]" class="cantidad-input w-full rounded border-gray-300 text-right">
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Costo</label>
                    <input type="number" step="0.01" min="0.01" value="0.00" name="productos[${index}][costo]" class="costo-input w-full rounded border-gray-300 text-right">
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Vence</label>
                    <input type="date" name="productos[${index}][fecha_vencimiento]" class="fecha-vencimiento-input w-full rounded border-gray-300">
                </div>
            </div>

            <div class="mt-3 flex justify-end border-t pt-3">
                <div class="rounded bg-slate-100 px-4 py-2 text-right">
                    <div class="text-xs text-gray-500">Subtotal</div>
                    <div class="subtotal-text text-lg font-bold text-gray-900">Q 0.00</div>
                </div>
            </div>
        `;

        tbody.appendChild(row);
        index++;
        renumerarFilas();
        calcularTotales();
    }

    function renumerarFilas()
    {
        document.querySelectorAll('.producto-item').forEach((row, position) => {
            row.querySelector('.row-number').innerText = position + 1;
        });

        const totalLineas = document.querySelectorAll('.producto-item').length;

        document.getElementById('lineas-total').innerText = totalLineas;
        document.getElementById('sin-productos').classList.toggle('hidden', totalLineas > 0);
    }

    function calcularTotales()
    {
        let totalGeneral = 0;

        document.querySelectorAll('.producto-item').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad-input').value || 0);
            const costo = parseFloat(row.querySelector('.costo-input').value || 0);
            const subtotal = cantidad * costo;

            row.querySelector('.subtotal-text').innerText = `Q ${subtotal.toFixed(2)}`;
            totalGeneral += subtotal;
        });

        document.getElementById('total-general').innerText = totalGeneral.toFixed(2);
    }

    document.addEventListener('input', event => {
        if (event.target.matches('.cantidad-input, .costo-input')) {
            calcularTotales();
        }
    });

    document.addEventListener('change', event => {
        if (event.target.matches('.producto-search-input')) {
            event.target.closest('.producto-item').querySelector('.producto-id-input').value = '';
        }
    });

    document.addEventListener('input', event => {
        if (!event.target.matches('.producto-search-input')) {
            return;
        }

        const input = event.target;
        const row = input.closest('.producto-item');
        row.querySelector('.producto-id-input').value = '';
        row.querySelector('.producto-seleccionado').textContent = 'Sin producto seleccionado';

        clearTimeout(productSearchTimeout);

        if (input.value.trim().length < 2) {
            renderProductoSugerencias(input, [], 'Escribe al menos 2 caracteres.');
            return;
        }

        productSearchTimeout = setTimeout(() => {
            buscarProductoCompra(input);
        }, 250);
    });

    document.addEventListener('click', event => {
        if (event.target.matches('.remover-fila')) {
            event.target.closest('.producto-item').remove();
            renumerarFilas();
            calcularTotales();
        }

        const suggestion = event.target.closest('.producto-suggestion');

        if (suggestion) {
            const row = suggestion.closest('.producto-item');
            const input = row.querySelector('.producto-search-input');
            const productIdInput = row.querySelector('.producto-id-input');
            const costoInput = row.querySelector('.costo-input');
            const selectedText = row.querySelector('.producto-seleccionado');

            input.value = suggestion.dataset.nombre;
            productIdInput.value = suggestion.dataset.id;
            selectedText.textContent = `Codigo: ${suggestion.dataset.codigo || 'N/A'}`;

            if (parseFloat(costoInput.value || 0) <= 0) {
                costoInput.value = parseFloat(suggestion.dataset.costo || 0).toFixed(2);
            }

            row.querySelector('.producto-suggestions').classList.add('hidden');
            calcularTotales();
        }
    });

    async function buscarProductoCompra(input) {
        productSearchController?.abort();
        productSearchController = new AbortController();

        try {
            const url = new URL(buscarProductosUrl, window.location.origin);
            url.searchParams.set('q', input.value.trim());

            mostrarEstadoSugerencias(input, 'Buscando productos...');

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: productSearchController.signal,
            });

            if (!response.ok) {
                throw new Error('No se pudo buscar productos.');
            }

            renderProductoSugerencias(input, await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') {
                renderProductoSugerencias(input, [], 'No se pudo completar la busqueda.');
            }
        }
    }

    function mostrarEstadoSugerencias(input, mensaje) {
        const suggestions = input.closest('.producto-item').querySelector('.producto-suggestions');
        const currentItems = suggestions.querySelectorAll('.producto-suggestion').length;

        suggestions.classList.remove('hidden');

        if (!currentItems) {
            suggestions.innerHTML = `<div class="p-3 text-sm text-gray-500">${escapeHtml(mensaje)}</div>`;
            return;
        }

        let status = suggestions.querySelector('[data-suggestion-status]');

        if (!status) {
            status = document.createElement('div');
            status.dataset.suggestionStatus = 'true';
            status.className = 'border-t bg-gray-50 px-3 py-2 text-xs text-gray-500';
            suggestions.appendChild(status);
        }

        status.textContent = mensaje;
    }

    function renderProductoSugerencias(input, productos, mensaje = 'No hay coincidencias.') {
        const suggestions = input.closest('.producto-item').querySelector('.producto-suggestions');

        suggestions.innerHTML = '';
        suggestions.classList.remove('hidden');

        if (!productos.length) {
            suggestions.innerHTML = `<div class="p-3 text-sm text-gray-500">${escapeHtml(mensaje)}</div>`;
            return;
        }

        productos.forEach(producto => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'producto-suggestion block w-full px-3 py-2 text-left text-sm hover:bg-blue-50';
            button.dataset.id = producto.id;
            button.dataset.nombre = producto.nombre;
            button.dataset.codigo = producto.codigo_barra;
            button.dataset.costo = producto.costo;
            button.innerHTML = `
                <div class="font-semibold text-gray-800">${escapeHtml(producto.nombre)}</div>
                <div class="text-xs text-gray-500">Código: ${escapeHtml(producto.codigo_barra)} | Costo: Q ${Number(producto.costo).toFixed(2)}</div>
            `;

            suggestions.appendChild(button);
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    document.getElementById('agregar-producto').addEventListener('click', agregarFila);

    document.getElementById('agregar-cinco').addEventListener('click', () => {
        for (let i = 0; i < 5; i++) {
            agregarFila();
        }
    });

    agregarFila();
</script>

</x-app-layout>
