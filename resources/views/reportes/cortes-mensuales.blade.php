<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reporte de Cortes Mensuales
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('reportes.cortes-mensuales') }}"
                  class="bg-white shadow rounded p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">

                <div>
                    <label class="block text-sm font-medium">Mes</label>
                    <select name="month" class="w-full border-gray-300 rounded">
                        <option value="">Todos</option>
                        @foreach($months as $value => $label)
                            <option value="{{ $value }}" @selected((string) request('month') === (string) $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium">Año</label>
                    <select name="year" class="w-full border-gray-300 rounded">
                        <option value="">Todos</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
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

                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 rounded"
                            style="background-color: blue; color: white;">
                        Filtrar
                    </button>

                    <a href="{{ route('reportes.cortes-mensuales') }}" class="px-4 py-2 rounded"
                       style="background-color: gray; color: white;">
                        Limpiar
                    </a>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow rounded p-4">
                    <div class="text-sm text-gray-500">Disponible antes del corte</div>
                    <div class="text-2xl font-bold">Q {{ number_format($totales['disponible_antes'], 2) }}</div>
                </div>

                <div class="bg-white shadow rounded p-4">
                    <div class="text-sm text-gray-500">Transferido</div>
                    <div class="text-2xl font-bold text-emerald-700">Q {{ number_format($totales['monto_transferido'], 2) }}</div>
                </div>

                <div class="bg-white shadow rounded p-4">
                    <div class="text-sm text-gray-500">Saldo restante</div>
                    <div class="text-2xl font-bold text-blue-700">Q {{ number_format($totales['saldo_restante'], 2) }}</div>
                </div>
            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <table class="w-full min-w-[980px] border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Periodo</th>
                            <th class="p-2 border">Sucursal</th>
                            <th class="p-2 border">Caja</th>
                            <th class="p-2 border">Disponible</th>
                            <th class="p-2 border">Transferido</th>
                            <th class="p-2 border">Saldo restante</th>
                            <th class="p-2 border">Usuario</th>
                            <th class="p-2 border">Fecha corte</th>
                            <th class="p-2 border">Referencia</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($cortes as $corte)
                            <tr>
                                <td class="p-2 border">
                                    {{ $months[$corte->periodo_month] ?? $corte->periodo_month }} {{ $corte->periodo_year }}
                                </td>
                                <td class="p-2 border">{{ $corte->sucursal->nombre ?? '-' }}</td>
                                <td class="p-2 border text-center">
                                    {{ $corte->caja_id ? '#'.$corte->caja_id : '-' }}
                                </td>
                                <td class="p-2 border text-right">Q {{ number_format($corte->disponible_antes, 2) }}</td>
                                <td class="p-2 border text-right">Q {{ number_format($corte->monto_transferido, 2) }}</td>
                                <td class="p-2 border text-right">Q {{ number_format($corte->saldo_restante, 2) }}</td>
                                <td class="p-2 border">{{ $corte->usuario->name ?? '-' }}</td>
                                <td class="p-2 border">
                                    {{ $corte->fecha_corte ? $corte->fecha_corte->timezone(config('app.timezone'))->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="p-2 border">{{ $corte->referencia ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-4 text-center text-gray-500">
                                    No hay cortes mensuales con esos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $cortes->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
