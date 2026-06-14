<x-app-layout>
    @php
        $canAdjustInventory = Auth::user()->hasAnyRole(['Administrador', 'Administrador Global', 'Super Usuario']);
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Inventario por Sucursal
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap gap-2">
                @can('inventario.ajustar')
                    <a href="{{ route('inventarios.entrada') }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded">
                        Nueva Entrada
                    </a>

                    <a href="{{ route('inventarios.carga-inicial') }}"
                       class="px-4 py-2 bg-indigo-700 text-white rounded">
                        Carga Inicial
                    </a>
                @endcan

                @can('inventario.ajustar')
                    <a href="{{ route('inventarios.fisico') }}"
                       class="px-4 py-2 bg-slate-800 text-white rounded">
                        Inventario Fisico
                    </a>
                @endcan
            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Producto</th>
                            <th class="p-2 border">Sucursal</th>
                            <th class="p-2 border">Existencia</th>
                            <th class="p-2 border">Stock mínimo</th>
                            <th class="p-2 border">Estado</th>
                            @if($canAdjustInventory)
                                <th class="p-2 border">Acciones</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($inventarios as $inventario)
                            <tr>
                                <td class="p-2 border">
                                    {{ $inventario->producto->nombre ?? 'Producto eliminado' }}
                                </td>

                                <td class="p-2 border">
                                    {{ $inventario->sucursal->nombre ?? 'Sucursal eliminada' }}
                                </td>

                                <td class="p-2 border">
                                    {{ $inventario->existencia }}
                                </td>

                                <td class="p-2 border">
                                    {{ $inventario->producto->stock_minimo ?? 0 }}
                                </td>

                                <td class="p-2 border">
                                    @if($inventario->producto && $inventario->existencia <= $inventario->producto->stock_minimo)
                                        <span class="text-red-600 font-bold">
                                            STOCK BAJO
                                        </span>
                                    @else
                                        <span class="text-green-600 font-bold">
                                            NORMAL
                                        </span>
                                    @endif
                                </td>
                                @if($canAdjustInventory)
                                    <td class="p-2 border text-center">
                                        <a href="{{ route('inventarios.ajustar', $inventario) }}"
                                           class="inline-flex items-center px-3 py-1 bg-amber-600 text-white rounded text-xs font-semibold hover:bg-amber-700">
                                            Ajustar
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canAdjustInventory ? 6 : 5 }}" class="p-4 text-center text-gray-500">
                                    No hay inventario registrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $inventarios->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
