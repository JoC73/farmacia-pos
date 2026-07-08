<div class="bg-white shadow rounded p-4 overflow-x-auto" data-async-results>
    <div class="mb-3 text-sm text-gray-600">
        Mostrando {{ $inventarios->firstItem() ?? 0 }}-{{ $inventarios->lastItem() ?? 0 }}
        de {{ $inventarios->total() }} registros de inventario
    </div>

    <table class="w-full border text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2 border">Producto</th>
                <th class="p-2 border">Sucursal</th>
                <th class="p-2 border">Existencia</th>
                <th class="p-2 border">Stock mínimo</th>
                <th class="p-2 border">Vence</th>
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
                        {{ $inventario->nombre_mostrado }}
                        @if($inventario->nombre_local && $inventario->producto && $inventario->nombre_local !== $inventario->producto->nombre)
                            <div class="text-xs text-gray-500">
                                Catalogo: {{ $inventario->producto->nombre }}
                            </div>
                        @endif
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
                        {{ optional($inventario->fecha_vencimiento)->format('d/m/Y') ?? 'Sin fecha' }}
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
                                Ajustar stock / fecha
                            </a>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canAdjustInventory ? 7 : 6 }}" class="p-4 text-center text-gray-500">
                        No hay inventario registrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4" data-async-pagination>
        {{ $inventarios->links() }}
    </div>
</div>
