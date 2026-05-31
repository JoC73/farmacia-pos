<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Factura / Venta
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded p-8">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 print:hidden">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Comprobante de venta</h3>
                        <p class="text-sm text-gray-500">Factura {{ $venta->numero_factura }}</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('ventas.index') }}"
                           class="px-4 py-2 rounded text-center"
                           style="background-color: #6b7280; color: white;">
                            Volver
                        </a>

                        <button onclick="window.print()"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">
                            Imprimir
                        </button>
                    </div>
                </div>

                <!-- ENCABEZADO -->
                <div class="text-center mb-8">

                    <h1 class="text-2xl font-bold">
                        FARMACIA POS
                    </h1>

                    <p>
                        Factura No:
                        <strong>{{ $venta->numero_factura }}</strong>
                    </p>

                    <p>
                        Fecha:
                        {{ $venta->created_at->format('d/m/Y H:i') }}
                    </p>

                </div>

                <!-- CLIENTE -->
                <div class="mb-6">

                    <h3 class="font-bold text-lg mb-2">
                        Cliente
                    </h3>

                    <p>

                        <strong>Nombre:</strong>

                        {{ $venta->cliente?->nombre ?? 'Consumidor Final' }}

                    </p>

                    <p>

                        <strong>NIT:</strong>

                        {{ $venta->cliente?->nit ?? 'CF' }}

                    </p>

                </div>

                <!-- INFORMACIÓN -->
                <div class="grid grid-cols-2 gap-4 mb-6">

                    <div>

                        <strong>Sucursal:</strong><br>

                        {{ $venta->sucursal->nombre }}

                    </div>

                    <div>

                        <strong>Usuario:</strong><br>

                        {{ $venta->usuario->name }}

                    </div>

                </div>

                <!-- DETALLE -->
                <div class="overflow-x-auto">

                    <table class="w-full border text-sm">

                        <thead>

                            <tr class="bg-gray-100">

                                <th class="border p-2 text-left">
                                    Producto
                                </th>

                                <th class="border p-2 text-center">
                                    Cantidad
                                </th>

                                <th class="border p-2 text-right">
                                    Precio
                                </th>

                                <th class="border p-2 text-right">
                                    Subtotal
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($venta->detalles as $detalle)

                                <tr>

                                    <td class="border p-2">

                                        {{ $detalle->producto->nombre }}

                                    </td>

                                    <td class="border p-2 text-center">

                                        {{ $detalle->cantidad }}

                                    </td>

                                    <td class="border p-2 text-right">

                                        Q {{ number_format($detalle->precio_unitario, 2) }}

                                    </td>

                                    <td class="border p-2 text-right">

                                        Q {{ number_format($detalle->subtotal, 2) }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <!-- TOTALES -->
                <div class="mt-6 flex justify-end">

                    <div class="w-72">

                        <div class="flex justify-between py-1">

                            <span>Subtotal:</span>

                            <span>
                                Q {{ number_format($venta->subtotal, 2) }}
                            </span>

                        </div>

                        <div class="flex justify-between py-1">

                            <span>Descuento:</span>

                            <span>
                                Q {{ number_format($venta->descuento, 2) }}
                            </span>

                        </div>

                        <div class="flex justify-between py-2 text-xl font-bold border-t mt-2">

                            <span>Total:</span>

                            <span>
                                Q {{ number_format($venta->total, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

                <!-- PIE -->
                <div class="text-center mt-10 text-sm text-gray-500">

                    Gracias por su compra.

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
