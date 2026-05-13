<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Entrada de Inventario
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

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
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 max-h-[520px] overflow-y-auto pr-2">

                            @foreach ($productos as $producto)
                                <button type="button"
                                        class="producto-card text-left border rounded p-4 hover:shadow transition bg-gray-50"
                                        data-id="{{ $producto->id }}"
                                        data-nombre="{{ strtolower($producto->nombre) }}"
                                        data-codigo="{{ strtolower($producto->codigo_barra) }}"
                                        data-label="{{ $producto->nombre }}">

                                    <div class="font-bold text-gray-800">
                                        {{ $producto->nombre }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        Código: {{ $producto->codigo_barra }}
                                    </div>

                                    <div class="mt-2 text-xs text-blue-700 bg-blue-100 inline-block px-2 py-1 rounded">
                                        Producto existente
                                    </div>

                                </button>
                            @endforeach

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

        const buscarInput = document.getElementById('buscar-producto');
        const productoCards = document.querySelectorAll('.producto-card');
        const carritoBody = document.getElementById('carrito-body');
        const inputsHidden = document.getElementById('inputs-hidden');
        const carritoVacio = document.getElementById('carrito-vacio');

        buscarInput.addEventListener('input', function () {
            const texto = this.value.toLowerCase();

            productoCards.forEach(card => {
                const nombre = card.dataset.nombre;
                const codigo = card.dataset.codigo;

                card.style.display =
                    nombre.includes(texto) || codigo.includes(texto)
                        ? 'block'
                        : 'none';
            });
        });

        productoCards.forEach(card => {
            card.addEventListener('click', function () {
                agregarProducto(
                    this.dataset.id,
                    this.dataset.label
                );
            });
        });

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