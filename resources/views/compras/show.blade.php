<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Compra
        </h2>

    </x-slot>

    <div class="py-6">

        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded p-8">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 print:hidden">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Comprobante de compra</h3>
                        <p class="text-sm text-gray-500">Factura {{ $compra->numero_factura }}</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('compras.index') }}"
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

                <div class="text-center mb-8">

                    <h1 class="text-2xl font-bold">
                        FACTURA COMPRA
                    </h1>

                    <p>
                        Factura:
                        <strong>{{ $compra->numero_factura }}</strong>
                    </p>

                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">

                    <div>

                        <strong>Proveedor:</strong><br>

                        {{ $compra->proveedor?->nombre ?? '-' }}

                    </div>

                    <div>

                        <strong>Usuario:</strong><br>

                        {{ $compra->usuario->name }}

                    </div>

                </div>

                <table class="w-full border text-sm">

                    <thead>

                        <tr class="bg-gray-100">

                            <th class="border p-2">
                                Producto
                            </th>

                            <th class="border p-2">
                                Cantidad
                            </th>

                            <th class="border p-2">
                                Costo
                            </th>

                            <th class="border p-2">
                                Vence
                            </th>

                            <th class="border p-2">
                                Subtotal
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($compra->detalles as $detalle)

                            <tr>

                                <td class="border p-2">

                                    {{ $detalle->producto?->inventarios?->first()?->nombre_mostrado ?? $detalle->producto?->nombre ?? 'Producto eliminado' }}

                                </td>

                                <td class="border p-2 text-center">

                                    {{ $detalle->cantidad }}

                                </td>

                                <td class="border p-2 text-right">

                                    Q {{ number_format($detalle->costo_unitario, 2) }}

                                </td>

                                <td class="border p-2 text-center">

                                    {{ optional($detalle->fecha_vencimiento)->format('d/m/Y') ?? 'Sin fecha' }}

                                </td>

                                <td class="border p-2 text-right">

                                    Q {{ number_format($detalle->subtotal, 2) }}

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

                <div class="mt-6 text-right">

                    <h3 class="text-xl font-bold">

                        Total:
                        Q {{ number_format($compra->total, 2) }}

                    </h3>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
