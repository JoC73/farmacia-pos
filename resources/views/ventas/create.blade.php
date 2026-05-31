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
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 max-h-[520px] overflow-y-auto pr-2">

                            @forelse ($productos as $producto)

                                <button type="button"
                                        class="producto-card text-left border rounded p-4 hover:shadow transition bg-gray-50"
                                        data-id="{{ $producto->id }}"
                                        data-nombre="{{ strtolower($producto->nombre) }}"
                                        data-codigo="{{ strtolower($producto->codigo_barra) }}"
                                        data-precio="{{ $producto->precio_venta }}"
                                        data-stock="{{ $producto->inventario_actual ?? 0 }}"
                                        data-label="{{ $producto->nombre }}">

                                    <div class="font-bold text-gray-800">
                                        {{ $producto->nombre }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        Código: {{ $producto->codigo_barra }}
                                    </div>

                                    <div class="mt-2 flex justify-between items-center">

                                        <span class="font-bold text-green-700">
                                            Q {{ number_format($producto->precio_venta, 2) }}
                                        </span>

                                        <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-700">
                                            Stock: {{ $producto->inventario_actual ?? 0 }}
                                        </span>

                                    </div>

                                </button>

                            @empty

                                <div class="col-span-3 text-center text-gray-500 p-6">
                                    No hay productos con existencia disponible.
                                </div>

                            @endforelse

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

        const buscarInput = document.getElementById('buscar-producto');
        const productoCards = document.querySelectorAll('.producto-card');
        const carritoBody = document.getElementById('carrito-body');
        const inputsHidden = document.getElementById('inputs-hidden');
        const totalGeneral = document.getElementById('total-general');
        const carritoVacio = document.getElementById('carrito-vacio');

        buscarInput.addEventListener('input', function () {
            const texto = this.value.toLowerCase();

            productoCards.forEach(card => {
                const nombre = card.dataset.nombre;
                const codigo = card.dataset.codigo;

                if (nombre.includes(texto) || codigo.includes(texto)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        productoCards.forEach(card => {
            card.addEventListener('click', function () {
                const id = this.dataset.id;
                const nombre = this.dataset.label;
                const precio = parseFloat(this.dataset.precio);
                const stock = parseInt(this.dataset.stock);

                agregarProducto(id, nombre, precio, stock);
            });
        });

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
