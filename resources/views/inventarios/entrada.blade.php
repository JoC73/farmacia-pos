<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Entrada de Inventario
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                <a href="{{ route('inventarios.index') }}"
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

            <form method="POST" action="{{ route('inventarios.entrada.store') }}" id="form-entrada">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="lg:col-span-2 bg-white shadow rounded p-6">

                        <div class="mb-4">
                            <label class="block font-medium mb-1">
                                Sucursal destino
                            </label>

                            <select name="sucursal_id"
                                    class="w-full border-gray-300 rounded"
                                    required>
                                <option value="">
                                    Seleccione una sucursal
                                </option>

                                @foreach ($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}">
                                        {{ $sucursal->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block font-medium mb-1">
                                Buscar producto
                            </label>

                            <input type="text"
                                   id="buscar-producto"
                                   placeholder="Buscar por nombre o código..."
                                   class="w-full border-gray-300 rounded px-3 py-2">
                            <div id="estado-busqueda-producto"
                                 class="mt-1 min-h-5 text-sm text-gray-500"></div>
                        </div>

                        <div id="productos-resultados"
                             class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 max-h-[520px] overflow-y-auto pr-2">
                        </div>

                    </div>

                    <div class="bg-white shadow rounded p-6">

                        <h3 class="text-lg font-bold mb-4">
                            Productos a ingresar
                        </h3>

                        <div id="carrito-vacio" class="text-gray-500 text-sm mb-4">
                            No hay productos agregados.
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border p-2 text-left">Producto</th>
                                        <th class="border p-2 text-center">Cant.</th>
                                        <th class="border p-2"></th>
                                    </tr>
                                </thead>

                                <tbody id="carrito-body"></tbody>
                            </table>
                        </div>

                        <div id="inputs-hidden"></div>

                        <div class="mt-4">
                            <label class="block font-medium mb-1">
                                Observación general
                            </label>

                            <textarea name="observacion"
                                      rows="3"
                                      class="w-full border-gray-300 rounded"
                                      placeholder="Ejemplo: Entrada por compra, ajuste o abastecimiento..."></textarea>
                        </div>

                        <div class="mt-6 flex flex-col gap-3">

                            <button type="submit"
                                    class="w-full px-4 py-3 rounded font-bold"
                                    style="background-color: green; color: white;">
                                Guardar Entrada
                            </button>

                            <a href="{{ route('inventarios.index') }}"
                               class="w-full text-center px-4 py-3 rounded"
                               style="background-color: gray; color: white;">
                                Cancelar
                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <script>
        let carrito = [];

        const productosIniciales = {{ Illuminate\Support\Js::from($productos) }};
        const buscarProductosUrl = '{{ route('inventarios.entrada.productos.buscar') }}';
        const buscarInput = document.getElementById('buscar-producto');
        const estadoBusquedaProducto = document.getElementById('estado-busqueda-producto');
        const productosResultados = document.getElementById('productos-resultados');
        const carritoBody = document.getElementById('carrito-body');
        const inputsHidden = document.getElementById('inputs-hidden');
        const carritoVacio = document.getElementById('carrito-vacio');
        let searchTimeout;
        let searchController;

        renderProductos(productosIniciales);

        buscarInput.addEventListener('input', function () {
            const texto = this.value.trim();

            clearTimeout(searchTimeout);

            if (texto.length === 0) {
                searchController?.abort();
                setSearchStatus('');
                renderProductos(productosIniciales);
                return;
            }

            if (texto.length < 2) {
                searchController?.abort();
                setSearchStatus('Escribe al menos 2 caracteres para buscar.');
                return;
            }

            searchTimeout = setTimeout(() => {
                buscarProductos(texto);
            }, 250);
        });

        productosResultados.addEventListener('click', function (event) {
            const card = event.target.closest('[data-producto-card]');

            if (!card) {
                return;
            }

            agregarProducto(card.dataset.id, card.dataset.label);
        });

        async function buscarProductos(texto) {
            searchController?.abort();
            searchController = new AbortController();
            setSearchStatus('Buscando productos...');

            try {
                const url = new URL(buscarProductosUrl, window.location.origin);
                url.searchParams.set('q', texto);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: searchController.signal,
                });

                if (!response.ok) {
                    throw new Error('No se pudo buscar productos.');
                }

                renderProductos(await response.json());
                setSearchStatus('');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setSearchStatus('No se pudo completar la busqueda. Intenta nuevamente.');
                }
            }
        }

        function renderProductos(productos) {
            productosResultados.innerHTML = '';

            if (!productos.length) {
                renderMensaje('No hay productos disponibles.');
                return;
            }

            productos.forEach(producto => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'text-left border rounded p-4 hover:shadow transition bg-gray-50';
                button.dataset.productoCard = 'true';
                button.dataset.id = producto.id;
                button.dataset.label = producto.nombre;

                button.innerHTML = `
                    <div class="font-bold text-gray-800">${escapeHtml(producto.nombre)}</div>
                    <div class="text-sm text-gray-500">Código: ${escapeHtml(producto.codigo_barra)}</div>
                    <div class="mt-2 text-xs text-blue-700 bg-blue-100 inline-block px-2 py-1 rounded">
                        Producto existente
                    </div>
                `;

                productosResultados.appendChild(button);
            });
        }

        function renderMensaje(mensaje) {
            productosResultados.innerHTML = `
                <div class="col-span-3 text-center text-gray-500 p-6">
                    ${escapeHtml(mensaje)}
                </div>
            `;
        }

        function setSearchStatus(mensaje) {
            estadoBusquedaProducto.textContent = mensaje;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function agregarProducto(id, nombre) {
            const existente = carrito.find(item => item.id === id);

            if (existente) {
                existente.cantidad++;
            } else {
                carrito.push({
                    id,
                    nombre,
                    cantidad: 1
                });
            }

            renderCarrito();
        }

        function cambiarCantidad(id, cantidad) {
            const item = carrito.find(item => item.id === id);

            cantidad = parseInt(cantidad);

            if (cantidad < 1 || isNaN(cantidad)) {
                cantidad = 1;
            }

            item.cantidad = cantidad;

            renderCarrito();
        }

        function eliminarProducto(id) {
            carrito = carrito.filter(item => item.id !== id);
            renderCarrito();
        }

        function renderCarrito() {
            carritoBody.innerHTML = '';
            inputsHidden.innerHTML = '';

            carritoVacio.style.display = carrito.length === 0 ? 'block' : 'none';

            carrito.forEach((item, index) => {
                carritoBody.innerHTML += `
                    <tr>
                        <td class="border p-2">
                            ${item.nombre}
                        </td>

                        <td class="border p-2 text-center">
                            <input type="number"
                                   min="1"
                                   value="${item.cantidad}"
                                   onchange="cambiarCantidad('${item.id}', this.value)"
                                   class="w-20 border-gray-300 rounded text-center">
                        </td>

                        <td class="border p-2 text-center">
                            <button type="button"
                                    onclick="eliminarProducto('${item.id}')"
                                    class="text-red-600 font-bold">
                                X
                            </button>
                        </td>
                    </tr>
                `;

                inputsHidden.innerHTML += `
                    <input type="hidden" name="productos[${index}][producto_id]" value="${item.id}">
                    <input type="hidden" name="productos[${index}][cantidad]" value="${item.cantidad}">
                `;
            });
        }

        document.getElementById('form-entrada').addEventListener('submit', function (e) {
            if (carrito.length === 0) {
                e.preventDefault();
                alert('Debe agregar al menos un producto.');
            }
        });
    </script>

</x-app-layout>
