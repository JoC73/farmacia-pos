<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
                Dashboard Gerencial
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Vista actual: {{ $scopeLabel }}
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- TARJETAS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6">

                <!-- VENTAS HOY -->
                <div class="bg-white shadow rounded p-4 sm:p-6 border-l-4 border-green-500">

                    <div class="text-xs sm:text-sm text-gray-500">
                        Ventas Hoy
                    </div>

                    <div class="text-2xl sm:text-3xl font-bold text-green-600 mt-2 leading-tight">
                        Q {{ number_format($ventasHoy, 2) }}
                    </div>

                </div>

                <!-- VENTAS MES -->
                <div class="bg-white shadow rounded p-4 sm:p-6 border-l-4 border-blue-500">

                    <div class="text-xs sm:text-sm text-gray-500">
                        Ventas del Mes
                    </div>

                    <div class="text-2xl sm:text-3xl font-bold text-blue-600 mt-2 leading-tight">
                        Q {{ number_format($ventasMes, 2) }}
                    </div>

                </div>

                <!-- COMPRAS MES -->
                <div class="bg-white shadow rounded p-4 sm:p-6 border-l-4 border-yellow-500">

                    <div class="text-xs sm:text-sm text-gray-500">
                        Compras del Mes
                    </div>

                    <div class="text-2xl sm:text-3xl font-bold text-yellow-600 mt-2 leading-tight">
                        Q {{ number_format($comprasMes, 2) }}
                    </div>

                </div>

                <!-- CAJAS ABIERTAS -->
                <div class="bg-white shadow rounded p-4 sm:p-6 border-l-4 border-red-500">

                    <div class="text-xs sm:text-sm text-gray-500">
                        Cajas Abiertas
                    </div>

                    <div class="text-2xl sm:text-3xl font-bold text-red-600 mt-2 leading-tight">
                        {{ $cajasAbiertas }}
                    </div>

                </div>

            </div>

            <!-- TOP PRODUCTOS -->
            <div class="bg-white shadow rounded p-4 sm:p-6 mb-6">

                <h3 class="text-base sm:text-lg font-bold mb-4">
                    Productos Más Vendidos
                </h3>

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[560px] border text-sm">

                        <thead>

                            <tr class="bg-gray-100">

                                <th class="p-2 border text-left">
                                    Producto
                                </th>

                                <th class="p-2 border text-center">
                                    Cantidad Vendida
                                </th>

                                <th class="p-2 border text-right">
                                    Total Generado
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($topProductos as $item)

                                <tr>

                                    <td class="p-2 border">

                                        {{ $item->producto?->nombre ?? 'Producto eliminado' }}

                                    </td>

                                    <td class="p-2 border text-center">

                                        {{ $item->total_vendido }}

                                    </td>

                                    <td class="p-2 border text-right">

                                        Q {{ number_format($item->total_generado, 2) }}

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="3"
                                        class="p-4 text-center text-gray-500">

                                        No hay ventas registradas.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- STOCK BAJO -->
            @if($stockBajo->count())

                <div class="bg-white shadow rounded p-4 sm:p-6 mb-6 border-l-4 border-red-500">

                    <h3 class="text-base sm:text-lg font-bold text-red-700 mb-4">

                        Productos con Stock Bajo

                    </h3>

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[640px] border text-sm">

                            <thead>

                                <tr class="bg-red-100">

                                    <th class="p-2 border text-left">
                                        Producto
                                    </th>

                                    <th class="p-2 border text-center">
                                        Existencia
                                    </th>

                                    <th class="p-2 border text-center">
                                        Stock Mínimo
                                    </th>

                                    <th class="p-2 border text-left">
                                        Sucursal
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($stockBajo as $item)

                                    <tr>

                                        <td class="p-2 border">

                                            {{ $item->producto->nombre }}

                                        </td>

                                        <td class="p-2 border text-center text-red-600 font-bold">

                                            {{ $item->existencia }}

                                        </td>

                                        <td class="p-2 border text-center">

                                            {{ $item->producto->stock_minimo }}

                                        </td>

                                        <td class="p-2 border">

                                            {{ $item->sucursal->nombre }}

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif

            <!-- PRODUCTOS POR VENCER -->
            @if($productosPorVencer->count())

                <div class="bg-white shadow rounded p-4 sm:p-6 mb-6 border-l-4 border-yellow-500">

                    <h3 class="text-base sm:text-lg font-bold text-yellow-700 mb-4">

                        Productos Próximos a Vencer

                    </h3>

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[480px] border text-sm">

                            <thead>

                                <tr class="bg-yellow-100">

                                    <th class="p-2 border text-left">
                                        Producto
                                    </th>

                                    <th class="p-2 border text-center">
                                        Fecha Vencimiento
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($productosPorVencer as $producto)

                                    <tr>

                                        <td class="p-2 border">

                                            {{ $producto->nombre }}

                                        </td>

                                        <td class="p-2 border text-center">

                                            {{ \Carbon\Carbon::parse($producto->fecha_vencimiento)->format('d/m/Y') }}

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif

            <!-- PRODUCTOS VENCIDOS -->
            @if($productosVencidos->count())

                <div class="bg-white shadow rounded p-4 sm:p-6 border-l-4 border-red-700">

                    <h3 class="text-base sm:text-lg font-bold text-red-700 mb-4">

                        Productos Vencidos

                    </h3>

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[480px] border text-sm">

                            <thead>

                                <tr class="bg-red-100">

                                    <th class="p-2 border text-left">
                                        Producto
                                    </th>

                                    <th class="p-2 border text-center">
                                        Fecha Vencimiento
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($productosVencidos as $producto)

                                    <tr>

                                        <td class="p-2 border">

                                            {{ $producto->nombre }}

                                        </td>

                                        <td class="p-2 border text-center text-red-700 font-bold">

                                            {{ \Carbon\Carbon::parse($producto->fecha_vencimiento)->format('d/m/Y') }}

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif

        </div>

    </div>

</x-app-layout>
