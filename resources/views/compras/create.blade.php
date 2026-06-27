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

                    <div class="rounded border bg-white">
                        <div class="border-b p-4">
                            <div>
                                <h3 class="font-bold text-gray-800">
                                    Productos
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Busca, agrega y edita lineas de compra desde una lista compacta.
                                </p>
                            </div>

                            <div class="relative mt-4">
                                <label for="buscar-producto-compra" class="mb-1 block text-sm font-semibold text-gray-700">
                                    Buscar producto
                                </label>
                                <input type="search"
                                       id="buscar-producto-compra"
                                       class="w-full rounded border-gray-300"
                                       placeholder="Nombre, codigo, laboratorio o categoria"
                                       autocomplete="off">
                                <div id="estado-busqueda-producto"
                                     class="mt-1 min-h-5 text-sm text-gray-500"></div>

                                <div id="productos-resultados-compra"
                                     class="absolute z-50 mt-1 hidden max-h-80 w-full overflow-y-auto rounded border border-gray-200 bg-white shadow-xl">
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[980px] text-sm">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-700">
                                        <th class="border p-2 text-left w-12">#</th>
                                        <th class="border p-2 text-left">Producto</th>
                                        <th class="border p-2 text-right w-28">Cantidad</th>
                                        <th class="border p-2 text-right w-32">Costo</th>
                                        <th class="border p-2 text-left w-40">Vence</th>
                                        <th class="border p-2 text-right w-36">Subtotal</th>
                                        <th class="border p-2 text-center w-24">Accion</th>
                                    </tr>
                                </thead>

                                <tbody id="productos-body"></tbody>
                            </table>
                        </div>

                        <div id="sin-productos" class="border-t p-6 text-center text-gray-500">
                            Busca un producto y agregalo a la compra.
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
    const buscarProductoInput = document.getElementById('buscar-producto-compra');
    const estadoBusqueda = document.getElementById('estado-busqueda-producto');
    const resultadosCompra = document.getElementById('productos-resultados-compra');
    const productosBody = document.getElementById('productos-body');
    const sinProductos = document.getElementById('sin-productos');
    const lineasTotal = document.getElementById('lineas-total');
    const totalGeneral = document.getElementById('total-general');

    let productSearchTimeout;
    let productSearchController;
    let nextRowId = 0;
    const productosSeleccionados = [];
    const searchCache = new Map();

    buscarProductoInput.addEventListener('input', () => {
        const term = buscarProductoInput.value.trim();
        clearTimeout(productSearchTimeout);

        if (!term.length) {
            cerrarResultados();
            setEstado('');
            return;
        }

        if (term.length < 2 && !/^\d+$/.test(term)) {
            renderResultados([], 'Escribe al menos 2 caracteres.');
            return;
        }

        const normalizedTerm = normalize(term);

        if (searchCache.has(normalizedTerm)) {
            renderResultados(searchCache.get(normalizedTerm));
            return;
        }

        productSearchTimeout = setTimeout(() => buscarProductos(term), 120);
    });

    resultadosCompra.addEventListener('click', event => {
        const option = event.target.closest('[data-producto-option]');

        if (!option) {
            return;
        }

        agregarProducto({
            id: option.dataset.id,
            nombre: option.dataset.nombre,
            codigo_barra: option.dataset.codigo,
            costo: parseFloat(option.dataset.costo || 0),
        });
    });

    productosBody.addEventListener('input', event => {
        const row = event.target.closest('[data-row-id]');

        if (!row) {
            return;
        }

        const item = productosSeleccionados.find(producto => producto.rowId === row.dataset.rowId);

        if (!item) {
            return;
        }

        if (event.target.matches('[data-cantidad]')) {
            item.cantidad = Math.max(1, parseInt(event.target.value || 1, 10));
        }

        if (event.target.matches('[data-costo]')) {
            item.costo = Math.max(0, parseFloat(event.target.value || 0));
        }

        if (event.target.matches('[data-vencimiento]')) {
            item.fecha_vencimiento = event.target.value;
        }

        recalcularTotalesCompra();
    });

    productosBody.addEventListener('click', event => {
        const removeButton = event.target.closest('[data-remove-row]');

        if (!removeButton) {
            return;
        }

        const index = productosSeleccionados.findIndex(producto => producto.rowId === removeButton.dataset.removeRow);

        if (index >= 0) {
            productosSeleccionados.splice(index, 1);
            renderProductosSeleccionados();
        }
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('#productos-resultados-compra') && event.target !== buscarProductoInput) {
            cerrarResultados();
        }
    });

    async function buscarProductos(term) {
        productSearchController?.abort();
        productSearchController = new AbortController();
        setEstado('Buscando productos...');

        try {
            const url = new URL(buscarProductosUrl, window.location.origin);
            url.searchParams.set('q', term);

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

            const productos = await response.json();
            searchCache.set(normalize(term), productos);
            renderResultados(productos);
            setEstado('');
        } catch (error) {
            if (error.name !== 'AbortError') {
                renderResultados([], 'No se pudo completar la busqueda.');
            }
        }
    }

    function renderResultados(productos, mensaje = 'No hay coincidencias.') {
        resultadosCompra.innerHTML = '';
        resultadosCompra.classList.remove('hidden');

        if (!productos.length) {
            resultadosCompra.innerHTML = `<div class="p-3 text-sm text-gray-500">${escapeHtml(mensaje)}</div>`;
            return;
        }

        productos.forEach(producto => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'grid w-full grid-cols-[minmax(0,1fr)_110px] gap-3 px-3 py-2 text-left hover:bg-blue-50';
            button.dataset.productoOption = 'true';
            button.dataset.id = producto.id;
            button.dataset.nombre = producto.nombre;
            button.dataset.codigo = producto.codigo_barra;
            button.dataset.costo = producto.costo;
            button.innerHTML = `
                <div class="min-w-0">
                    <div class="truncate font-semibold text-gray-900">${escapeHtml(producto.nombre)}</div>
                    <div class="text-xs text-gray-500">Codigo: ${escapeHtml(producto.codigo_barra)}</div>
                </div>
                <div class="text-right font-bold text-green-700">Q ${Number(producto.costo).toFixed(2)}</div>
            `;

            resultadosCompra.appendChild(button);
        });
    }

    function agregarProducto(producto) {
        const existente = productosSeleccionados.find(item => item.id === producto.id);

        if (existente) {
            existente.cantidad++;
        } else {
            productosSeleccionados.push({
                rowId: String(nextRowId++),
                id: producto.id,
                nombre: producto.nombre,
                codigo_barra: producto.codigo_barra,
                cantidad: 1,
                costo: producto.costo > 0 ? producto.costo : 0,
                fecha_vencimiento: '',
            });
        }

        buscarProductoInput.value = '';
        cerrarResultados();
        setEstado('');
        renderProductosSeleccionados();
        buscarProductoInput.focus();
    }

    function renderProductosSeleccionados() {
        productosBody.innerHTML = '';
        let total = 0;

        productosSeleccionados.forEach((item, position) => {
            const subtotal = item.cantidad * item.costo;
            total += subtotal;

            const row = document.createElement('tr');
            row.dataset.rowId = item.rowId;
            row.innerHTML = `
                <td class="border p-2 text-center">${position + 1}</td>
                <td class="border p-2">
                    <input type="hidden" name="productos[${position}][producto_id]" value="${escapeHtml(item.id)}">
                    <div class="font-semibold text-gray-900">${escapeHtml(item.nombre)}</div>
                    <div class="text-xs text-gray-500">Codigo: ${escapeHtml(item.codigo_barra)}</div>
                </td>
                <td class="border p-2">
                    <input type="number" min="1" name="productos[${position}][cantidad]" value="${item.cantidad}" data-cantidad class="w-full rounded border-gray-300 text-right">
                </td>
                <td class="border p-2">
                    <input type="number" step="0.01" min="0.01" name="productos[${position}][costo]" value="${Number(item.costo).toFixed(2)}" data-costo class="w-full rounded border-gray-300 text-right">
                </td>
                <td class="border p-2">
                    <input type="date" name="productos[${position}][fecha_vencimiento]" value="${escapeHtml(item.fecha_vencimiento)}" data-vencimiento class="w-full rounded border-gray-300">
                </td>
                <td class="border p-2 text-right font-bold" data-subtotal>Q ${subtotal.toFixed(2)}</td>
                <td class="border p-2 text-center">
                    <button type="button" data-remove-row="${item.rowId}" class="rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700">
                        Quitar
                    </button>
                </td>
            `;

            productosBody.appendChild(row);
        });

        lineasTotal.textContent = productosSeleccionados.length;
        totalGeneral.textContent = total.toFixed(2);
        sinProductos.classList.toggle('hidden', productosSeleccionados.length > 0);
    }

    function recalcularTotalesCompra() {
        let total = 0;

        productosSeleccionados.forEach(item => {
            const subtotal = item.cantidad * item.costo;
            total += subtotal;

            const row = productosBody.querySelector(`[data-row-id="${item.rowId}"]`);

            if (row) {
                row.querySelector('[data-subtotal]').textContent = `Q ${subtotal.toFixed(2)}`;
            }
        });

        totalGeneral.textContent = total.toFixed(2);
    }

    function cerrarResultados() {
        resultadosCompra.classList.add('hidden');
        resultadosCompra.innerHTML = '';
    }

    function setEstado(mensaje) {
        estadoBusqueda.textContent = mensaje;
    }

    function normalize(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
</script>

</x-app-layout>
