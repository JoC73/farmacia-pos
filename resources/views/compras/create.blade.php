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
                    <div class="bg-gray-50 p-4 rounded border">

                        <h3 class="font-bold mb-4">
                            Productos
                        </h3>

                        <div id="productos-container">

                            <div class="producto-item grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">

                                <!-- PRODUCTO -->
                                <div>

                                    <label class="block text-sm font-medium mb-1">
                                        Producto
                                    </label>

                                    <select name="productos[0][producto_id]"
                                            class="w-full border-gray-300 rounded producto-select">

                                        <option value="">
                                            Seleccione producto
                                        </option>

                                        @foreach($productos as $producto)

                                            <option value="{{ $producto->id }}">

                                                {{ $producto->nombre }}

                                            </option>

                                        @endforeach

                                    </select>

                                </div>

                                <!-- CANTIDAD -->
                                <div>

                                    <label class="block text-sm font-medium mb-1">
                                        Cantidad
                                    </label>

                                    <input type="number"
                                           min="1"
                                           value="1"
                                           name="productos[0][cantidad]"
                                           class="w-full border-gray-300 rounded cantidad-input">

                                </div>

                                <!-- COSTO -->
                                <div>

                                    <label class="block text-sm font-medium mb-1">
                                        Costo
                                    </label>

                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           value="0"
                                           name="productos[0][costo]"
                                           class="w-full border-gray-300 rounded costo-input">

                                </div>

                                <!-- SUBTOTAL -->
                                <div>

                                    <label class="block text-sm font-medium mb-1">
                                        Subtotal
                                    </label>

                                    <input type="text"
                                           readonly
                                           class="w-full bg-gray-100 border-gray-300 rounded subtotal-input">

                                </div>

                            </div>

                        </div>

                        <button type="button"
                                id="agregar-producto"
                                class="mt-2 px-4 py-2 rounded"
                                style="background-color: blue; color: white;">

                            Agregar Producto

                        </button>

                    </div>

                    <!-- TOTAL -->
                    <div class="mt-6">

                        <h3 class="text-xl font-bold">

                            Total:
                            Q <span id="total-general">0.00</span>

                        </h3>

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

    let index = 1;

    function calcularTotales()
    {
        let totalGeneral = 0;

        document.querySelectorAll('.producto-item').forEach(item => {

            const cantidad =
                parseFloat(item.querySelector('.cantidad-input').value || 0);

            const costo =
                parseFloat(item.querySelector('.costo-input').value || 0);

            const subtotal = cantidad * costo;

            item.querySelector('.subtotal-input').value =
                subtotal.toFixed(2);

            totalGeneral += subtotal;

        });

        document.getElementById('total-general').innerText =
            totalGeneral.toFixed(2);
    }

    document.addEventListener('input', calcularTotales);

    document.getElementById('agregar-producto')
        .addEventListener('click', function () {

            const container =
                document.getElementById('productos-container');

            const item =
                document.querySelector('.producto-item');

            const clone = item.cloneNode(true);

            clone.querySelectorAll('select, input').forEach(el => {

                if (el.name.includes('producto_id')) {

                    el.name = `productos[${index}][producto_id]`;

                    el.selectedIndex = 0;
                }

                if (el.name.includes('cantidad')) {

                    el.name = `productos[${index}][cantidad]`;

                    el.value = 1;
                }

                if (el.name.includes('costo')) {

                    el.name = `productos[${index}][costo]`;

                    el.value = 0;
                }

                if (el.classList.contains('subtotal-input')) {

                    el.value = '';
                }

            });

            container.appendChild(clone);

            index++;

        });

</script>

</x-app-layout>
