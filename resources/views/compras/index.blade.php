<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Compras
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

                @can('compras.crear')

                    <a href="{{ route('compras.create') }}"
                       class="px-4 py-2 rounded"
                       style="background-color: blue; color: white;">

                        Nueva Compra

                    </a>

                @endcan

            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">

                <table class="w-full border text-sm">

                    <thead>

                        <tr class="bg-gray-100">

                            <th class="p-2 border">Factura</th>

                            <th class="p-2 border">Proveedor</th>

                            <th class="p-2 border">Sucursal</th>

                            <th class="p-2 border">Usuario</th>

                            <th class="p-2 border">Total</th>

                            <th class="p-2 border">Fecha</th>

                            <th class="p-2 border">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($compras as $compra)

                            <tr>

                                <td class="p-2 border">

                                    {{ $compra->numero_factura ?? '-' }}

                                </td>

                                <td class="p-2 border">

                                    {{ $compra->proveedor?->nombre ?? '-' }}

                                </td>

                                <td class="p-2 border">

                                    {{ $compra->sucursal->nombre }}

                                </td>

                                <td class="p-2 border">

                                    {{ $compra->usuario->name }}

                                </td>

                                <td class="p-2 border">

                                    Q {{ number_format($compra->total, 2) }}

                                </td>

                                <td class="p-2 border">

                                    {{ $compra->created_at->format('d/m/Y H:i') }}

                                </td>

                                <td class="p-2 border text-center">

                                    @can('compras.ver')

                                        <a href="{{ route('compras.show', $compra) }}"
                                           class="text-blue-600 font-bold">

                                            Ver

                                        </a>

                                    @else

                                        <span class="text-gray-400">

                                            Sin acceso

                                        </span>

                                    @endcan

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    class="p-4 text-center text-gray-500">

                                    No hay compras registradas.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

                <div class="mt-4">

                    {{ $compras->links() }}

                </div>

            </div>

        </div>

    </div>

</x-app-layout>