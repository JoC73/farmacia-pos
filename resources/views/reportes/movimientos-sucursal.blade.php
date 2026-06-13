<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Movimientos por Sucursal
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Vista actual: {{ $scopeLabel }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4 flex justify-end">
                <a href="{{ route('reportes.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-800">
                    Volver
                </a>
            </div>

            <div class="bg-white shadow rounded p-4 sm:p-6">

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1080px] border text-sm">

                        <thead>

                            <tr class="bg-gray-100">

                                <th class="p-2 border text-left">
                                    Sucursal
                                </th>

                                <th class="p-2 border text-right">
                                    Ventas Hoy
                                </th>

                                <th class="p-2 border text-right">
                                    Ventas Mes
                                </th>

                                <th class="p-2 border text-right">
                                    Compras Mes
                                </th>

                                <th class="p-2 border text-right">
                                    Egresos Mes
                                </th>

                                <th class="p-2 border text-right">
                                    Cierre Caja Hoy
                                </th>

                                <th class="p-2 border text-right">
                                    Cierre Caja Mes
                                </th>

                                <th class="p-2 border text-right">
                                    Diferencia Mes
                                </th>

                                <th class="p-2 border text-right">
                                    Flujo Neto Mes
                                </th>

                                <th class="p-2 border text-center">
                                    Cajas Abiertas
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($resumen as $item)

                                <tr>

                                    <td class="p-2 border font-semibold">
                                        {{ $item['sucursal']->nombre }}
                                    </td>

                                    <td class="p-2 border text-right">
                                        Q {{ number_format($item['ventas_hoy'], 2) }}
                                    </td>

                                    <td class="p-2 border text-right">
                                        Q {{ number_format($item['ventas_mes'], 2) }}
                                    </td>

                                    <td class="p-2 border text-right">
                                        Q {{ number_format($item['compras_mes'], 2) }}
                                    </td>

                                    <td class="p-2 border text-right text-red-600">
                                        Q {{ number_format($item['egresos_mes'], 2) }}
                                    </td>

                                    <td class="p-2 border text-right">
                                        Q {{ number_format($item['cierres_hoy'], 2) }}
                                    </td>

                                    <td class="p-2 border text-right">
                                        Q {{ number_format($item['cierres_mes'], 2) }}
                                    </td>

                                    <td class="p-2 border text-right {{ $item['diferencia_mes'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                                        Q {{ number_format($item['diferencia_mes'], 2) }}
                                    </td>

                                    <td class="p-2 border text-right font-bold {{ $item['flujo_neto_mes'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                                        Q {{ number_format($item['flujo_neto_mes'], 2) }}
                                    </td>

                                    <td class="p-2 border text-center">
                                        {{ $item['cajas_abiertas'] }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="10" class="p-4 text-center text-gray-500">
                                        No hay sucursales disponibles para mostrar.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
