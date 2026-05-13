<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reporte de Ventas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('reportes.ventas') }}"
                  class="bg-white shadow rounded p-4 mb-6 grid grid-cols-1 md:grid-cols-5 gap-4">

                <div>
                    <label class="block text-sm font-medium">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}"
                           class="w-full border-gray-300 rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium">Fecha fin</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}"
                           class="w-full border-gray-300 rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium">Sucursal</label>
                    <select name="sucursal_id" class="w-full border-gray-300 rounded">
                        <option value="">Todas</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected(request('sucursal_id') == $sucursal->id)>
                                {{ $sucursal->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium">Usuario</label>
                    <select name="user_id" class="w-full border-gray-300 rounded">
                        <option value="">Todos</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected(request('user_id') == $usuario->id)>
                                {{ $usuario->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 rounded"
                            style="background-color: blue; color: white;">
                        Filtrar
                    </button>

                    <a href="{{ route('reportes.ventas') }}" class="px-4 py-2 rounded"
                       style="background-color: gray; color: white;">
                        Limpiar
                    </a>
                </div>
            </form>

            <div class="mb-6 bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Total vendido</div>
                <div class="text-2xl font-bold">
                    Q {{ number_format($totalVentas, 2) }}
                </div>
            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Factura</th>
                            <th class="p-2 border">Cliente</th>
                            <th class="p-2 border">Sucursal</th>
                            <th class="p-2 border">Usuario</th>
                            <th class="p-2 border">Total</th>
                            <th class="p-2 border">Fecha</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($ventas as $venta)
                            <tr>
                                <td class="p-2 border">{{ $venta->numero_factura }}</td>
                                <td class="p-2 border">{{ $venta->cliente?->nombre ?? 'Consumidor Final' }}</td>
                                <td class="p-2 border">{{ $venta->sucursal->nombre }}</td>
                                <td class="p-2 border">{{ $venta->usuario->name }}</td>
                                <td class="p-2 border">Q {{ number_format($venta->total, 2) }}</td>
                                <td class="p-2 border">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-500">
                                    No hay ventas con esos filtros.
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