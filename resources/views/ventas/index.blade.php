<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ventas
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))

                <div class="mb-4 bg-green-100 border border-green-300 text-green-700 p-4 rounded">

                    {{ session('success') }}

                </div>

            @endif

            @if(session('error'))

                <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded">

                    {{ session('error') }}

                </div>

            @endif

            <div class="mb-4">

                @can('ventas.crear')

                    <a href="{{ route('ventas.create') }}"
                       class="px-4 py-2 rounded"
                       style="background-color: blue; color: white;">

                        Nueva Venta

                    </a>

                @endcan

            </div>

            @if(auth()->user()->hasRole('Super Usuario') && $sucursales->isNotEmpty())
                <div class="mb-4 rounded border border-emerald-200 bg-white p-4 shadow">
                    <div class="mb-3">
                        <h3 class="font-bold text-gray-800">
                            Descarga de ventas por sucursal
                        </h3>
                        <p class="text-sm text-gray-500">
                            Disponible solo para Super Usuario. Incluye resumen de ventas y detalle por producto.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach($sucursales as $sucursal)
                            <a href="{{ route('ventas.sucursales.descargar', $sucursal) }}"
                               class="rounded bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                                Descargar {{ $sucursal->nombre }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white shadow rounded p-4 overflow-x-auto">

                <table class="w-full border text-sm">

                    <thead>

                        <tr class="bg-gray-100">

                            <th class="p-2 border">Factura</th>

                            <th class="p-2 border">Cliente</th>

                            <th class="p-2 border">Sucursal</th>

                            <th class="p-2 border">Usuario</th>

                            <th class="p-2 border">Total</th>

                            <th class="p-2 border">Estado</th>

                            <th class="p-2 border">Fecha</th>

                            <th class="p-2 border">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse ($ventas as $venta)

                            <tr>

                                <td class="p-2 border">

                                    {{ $venta->numero_factura }}

                                </td>

                                <td class="p-2 border">

                                    {{ $venta->cliente?->nombre ?? 'Consumidor Final' }}

                                </td>

                                <td class="p-2 border">

                                    {{ $venta->sucursal->nombre }}

                                </td>

                                <td class="p-2 border">

                                    {{ $venta->usuario->name }}

                                </td>

                                <td class="p-2 border">

                                    Q {{ number_format($venta->total, 2) }}

                                </td>

                                <td class="p-2 border">

                                    @if($venta->estado === 'ANULADA')
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-700">
                                            ANULADA
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-700">
                                            {{ $venta->estado }}
                                        </span>
                                    @endif

                                </td>

                                <td class="p-2 border">

                                    {{ $venta->created_at->format('d/m/Y H:i') }}

                                </td>

                                <td class="p-2 border text-center">

                                    @can('ventas.ver')

                                        <a href="{{ route('ventas.show', $venta) }}"
                                           class="text-blue-600 font-bold">

                                            Ver

                                        </a>

                                        @can('ventas.anular')
                                            @if($venta->estado !== 'ANULADA' && auth()->user()->hasAnyRole(['Administrador', 'Super Usuario']))
                                                <a href="{{ route('ventas.show', $venta) }}#anular"
                                                   class="text-red-600 font-bold ml-3">
                                                    Anular
                                                </a>
                                            @endif
                                        @endcan

                                    @else

                                        <span class="text-gray-400">

                                            Sin acceso

                                        </span>

                                    @endcan

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8"
                                    class="p-4 text-center text-gray-500">

                                    No hay ventas registradas.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

                <div class="mt-4">

                    {{ $ventas->links() }}

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
