<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Punto de Venta
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                <a href="{{ route('ventas.index') }}"
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

            <form method="POST" action="{{ route('ventas.store') }}" id="form-venta">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- PANEL PRODUCTOS -->
                    <div class="lg:col-span-2 bg-white shadow rounded p-6">

                        <div class="mb-4">
                            <label class="block font-medium mb-1">
                                Cliente
                            </label>

                            <select name="cliente_id"
                                    class="w-full border-gray-300 rounded">

                                <option value="">
                                    Consumidor Final
                                </option>

                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">
                                        {{ $cliente->nombre }}
                                        @if($cliente->nit)
                                            - {{ $cliente->nit }}
                                        @endif
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

                        <div class="rounded border bg-white">
                            <div class="grid grid-cols-[minmax(0,1fr)_90px_90px] gap-2 border-b bg-gray-100 px-3 py-2 text-xs font-bold uppercase text-gray-600">
                                <div>Producto</div>
                                <div class="text-right">Precio</div>
                                <div class="text-right">Stock</div>
                            </div>

                            <div id="productos-resultados"
                                 class="max-h-[520px] divide-y overflow-y-auto">
                            </div>
                        </div>

                    </div>

                    <!-- PANEL CARRITO -->
                    <div class="bg-white shadow rounded p-6">

                        <h3 class="text-lg font-bold mb-4">
                            Carrito de venta
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
                                        <th class="border p-2 text-right">Subtotal</th>
                                        <th class="border p-2"></th>
                                    </tr>
                                </thead>

                                <tbody id="carrito-body"></tbody>
                            </table>
                        </div>

                        <div id="inputs-hidden"></div>

                        <div class="mt-6 border-t pt-4">

                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span>Q <span id="total-general">0.00</span></span>
                            </div>

                        </div>

                        <div class="mt-6 flex flex-col gap-3">

                            <button type="submit"
                                    class="w-full px-4 py-3 rounded font-bold"
                                    style="background-color: green; color: white;">

                                Finalizar Venta

                            </button>

                            <a href="{{ route('ventas.index') }}"
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
        const buscarProductosUrl = '{{ route('ventas.productos.buscar') }}';
        const buscarInput = document.getElementById('buscar-producto');
        const estadoBusquedaProducto = document.getElementById('estado-busqueda-producto');
        const productosResultados = document.getElementById('productos-resultados');
        const carritoBody = document.getElementById('carrito-body');
        const inputsHidden = document.getElementById('inputs-hidden');
        const totalGeneral = document.getElementById('total-general');
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

            agregarProducto(
                card.dataset.id,
                card.dataset.label,
                parseFloat(card.dataset.precio),
                parseInt(card.dataset.stock)
            );
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
                renderMensaje('No hay productos con existencia disponible.');
                return;
            }

            productos.forEach(producto => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'grid w-full grid-cols-[minmax(0,1fr)_90px_90px] items-center gap-2 px-3 py-3 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none';
                button.dataset.productoCard = 'true';
                button.dataset.id = producto.id;
                button.dataset.precio = producto.precio_venta;
                button.dataset.stock = producto.inventario_actual;
                button.dataset.label = producto.nombre;

                button.innerHTML = `
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-gray-900">${escapeHtml(producto.nombre)}</div>
                        <div class="text-xs text-gray-500">Codigo: ${escapeHtml(producto.codigo_barra)}</div>
                    </div>
                    <div class="text-right font-bold text-green-700">
                        Q ${Number(producto.precio_venta).toFixed(2)}
                    </div>
                    <div class="text-right">
                        <span class="inline-flex min-w-10 justify-center rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                            ${producto.inventario_actual}
                        </span>
                    </div>
                `;

                productosResultados.appendChild(button);
            });
        }

        function renderMensaje(mensaje) {
            productosResultados.innerHTML = `
                <div class="text-center text-gray-500 p-6">
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

        function agregarProducto(id, nombre, precio, stock) {
            const existente = carrito.find(item => item.id === id);

            if (existente) {
                if (existente.cantidad + 1 > existente.stock) {
                    alert('No hay suficiente stock disponible.');
                    return;
                }

                existente.cantidad++;
            } else {
                if (stock <= 0) {
                    alert('Producto sin existencia.');
                    return;
                }

                carrito.push({
                    id,
                    nombre,
                    precio,
                    stock,
                    cantidad: 1
                });
            }

            renderCarrito();
        }

        function cambiarCantidad(id, cantidad) {
            const item = carrito.find(item => item.id === id);

            cantidad = parseInt(cantidad);

            if (cantidad < 1) {
                cantidad = 1;
            }

            if (cantidad > item.stock) {
                alert('No puedes vender más que la existencia disponible.');
                cantidad = item.stock;
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

            let total = 0;

            carritoVacio.style.display = carrito.length === 0 ? 'block' : 'none';

            carrito.forEach((item, index) => {
                const subtotal = item.precio * item.cantidad;
                total += subtotal;

                carritoBody.innerHTML += `
                    <tr>
                        <td class="border p-2">
                            <div class="font-bold">${item.nombre}</div>
                            <div class="text-xs text-gray-500">Q ${item.precio.toFixed(2)} | Stock: ${item.stock}</div>
                        </td>

                        <td class="border p-2 text-center">
                            <input type="number"
                                   min="1"
                                   max="${item.stock}"
                                   value="${item.cantidad}"
                                   onchange="cambiarCantidad('${item.id}', this.value)"
                                   class="w-16 border-gray-300 rounded text-center">
                        </td>

                        <td class="border p-2 text-right">
                            Q ${subtotal.toFixed(2)}
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

            totalGeneral.innerText = total.toFixed(2);
        }

        document.getElementById('form-venta').addEventListener('submit', function (e) {
            if (carrito.length === 0) {
                e.preventDefault();
                alert('Debe agregar al menos un producto a la venta.');
            }
        });
    </script>

</x-app-layout>
