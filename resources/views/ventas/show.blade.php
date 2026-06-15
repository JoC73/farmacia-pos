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

                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-300 text-green-700 p-4 rounded print:hidden">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded print:hidden">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded print:hidden">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($venta->estado === 'ANULADA')
                    <div class="mb-6 border border-red-200 bg-red-50 text-red-800 rounded p-4">
                        <div class="font-bold">Venta anulada</div>
                        <div class="text-sm">
                            Anulada por {{ $venta->anulador->name ?? '-' }}
                            el {{ optional($venta->fecha_anulacion)->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '-' }}.
                        </div>
                        <div class="text-sm mt-1">
                            Motivo: {{ $venta->motivo_anulacion ?? '-' }}
                        </div>
                    </div>
                @endif

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
                        {{ $venta->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                    </p>

                    @if($venta->estado === 'ANULADA')
                        <p class="mt-2 text-red-700 font-bold">
                            DOCUMENTO ANULADO
                        </p>
                    @endif

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

                @can('ventas.anular')
                    @if($venta->estado !== 'ANULADA' && auth()->user()->hasAnyRole(['Administrador', 'Super Usuario']))
                        <div id="anular" class="mt-8 border-t pt-6 print:hidden">
                            <h3 class="font-bold text-lg text-red-700 mb-2">
                                Anular venta
                            </h3>

                            <p class="text-sm text-gray-600 mb-4">
                                Esta accion devuelve el stock vendido y excluye la venta del cierre de caja mientras la caja siga abierta.
                            </p>

                            <form method="POST"
                                  action="{{ route('ventas.anular', $venta) }}"
                                  onsubmit="return confirm('¿Confirmas anular esta venta? Esta accion no se puede deshacer.');">
                                @csrf

                                <div class="mb-4">
                                    <label class="block font-medium mb-1">
                                        Motivo de anulacion
                                    </label>

                                    <textarea name="motivo_anulacion"
                                              rows="3"
                                              required
                                              class="w-full border-gray-300 rounded"
                                              placeholder="Ejemplo: Error en producto, venta duplicada, cliente solicito cancelacion...">{{ old('motivo_anulacion') }}</textarea>
                                </div>

                                <button type="submit"
                                        class="px-4 py-2 rounded"
                                        style="background-color: #dc2626; color: white;">
                                    Anular Venta
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan

            </div>

        </div>

    </div>

</x-app-layout>
