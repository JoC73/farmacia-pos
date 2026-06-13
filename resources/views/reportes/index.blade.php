<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reportes
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <a href="{{ route('reportes.ventas') }}"
                   class="bg-white shadow rounded p-6 hover:bg-gray-50 transition">

                    <h3 class="text-lg font-bold mb-2">

                        Reporte de Ventas

                    </h3>

                    <p class="text-gray-600 text-sm">

                        Ventas por fecha, sucursal y usuario.

                    </p>

                </a>

                @can('reportes.caja')
                    <a href="{{ route('reportes.movimientos-sucursal') }}"
                       class="bg-white shadow rounded p-6 hover:bg-gray-50 transition">

                        <h3 class="text-lg font-bold mb-2">

                            Movimientos por Sucursal

                        </h3>

                        <p class="text-gray-600 text-sm">

                            Resumen de ventas, compras, egresos y cajas abiertas.

                        </p>

                    </a>
                @endcan

            </div>

        </div>

    </div>

</x-app-layout>
