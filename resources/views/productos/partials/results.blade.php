<div class="bg-white shadow rounded p-4 overflow-x-auto" data-async-results>
    <div class="mb-3 text-sm text-gray-600">
        Mostrando {{ $productos->firstItem() ?? 0 }}-{{ $productos->lastItem() ?? 0 }}
        de {{ $productos->total() }} productos
    </div>

    <table class="w-full border text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2 border">Código</th>
                <th class="p-2 border">Producto</th>
                <th class="p-2 border">Categoría</th>
                <th class="p-2 border">Costo</th>
                <th class="p-2 border">Precio</th>
                <th class="p-2 border">Stock mín.</th>
                <th class="p-2 border">Vence</th>
                <th class="p-2 border">Estado</th>
                <th class="p-2 border">Acciones</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($productos as $producto)
                <tr>
                    <td class="p-2 border">
                        {{ $producto->codigo_barra }}
                    </td>

                    <td class="p-2 border">
                        {{ $producto->nombre }}
                    </td>

                    <td class="p-2 border">
                        {{ $producto->categoria->nombre ?? 'Sin categoría' }}
                    </td>

                    <td class="p-2 border">
                        Q {{ number_format($producto->costo, 2) }}
                    </td>

                    <td class="p-2 border">
                        Q {{ number_format($producto->precio_venta, 2) }}
                    </td>

                    <td class="p-2 border">
                        {{ $producto->stock_minimo }}
                    </td>

                    <td class="p-2 border">
                        @php
                            $localInventarioForDate = $producto->relationLoaded('inventarios')
                                ? $producto->inventarios->first()
                                : null;
                            $fechaVencimiento = $localInventarioForDate?->fecha_vencimiento ?? $producto->fecha_vencimiento;
                        @endphp

                        {{ $fechaVencimiento ? \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') : 'Sin fecha' }}
                    </td>

                    <td class="p-2 border">
                        {{ $producto->estado ? 'Activo' : 'Inactivo' }}
                    </td>

                    <td class="p-2 border whitespace-nowrap">
                        @if($canManageGlobalProducts)
                            <a href="{{ route('productos.edit', $producto) }}"
                               class="text-blue-600">
                                Editar
                            </a>

                            <form action="{{ route('productos.destroy', $producto) }}"
                                  method="POST"
                                  class="inline">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        onclick="return confirm('¿Deseas desactivar este producto?')"
                                        class="text-red-600 ml-3">
                                    Desactivar
                                </button>
                            </form>
                        @elseif($canAdjustLocalInventory)
                            @php
                                $localInventario = $producto->inventarios->first();
                            @endphp

                            @if($localInventario)
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('inventarios.ajustar', $localInventario) }}"
                                       class="inline-flex rounded bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">
                                        Stock
                                    </a>

                                    <button type="button"
                                            class="inline-flex rounded bg-blue-600 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-700"
                                            data-open-expiry-modal
                                            data-action="{{ route('inventarios.vencimiento.update', $localInventario) }}"
                                            data-producto="{{ $producto->nombre }}"
                                            data-sucursal="{{ $localInventario->sucursal->nombre ?? auth()->user()->sucursal?->nombre ?? 'Sucursal asignada' }}"
                                            data-fecha="{{ optional($localInventario->fecha_vencimiento)->format('Y-m-d') }}">
                                        {{ $localInventario->fecha_vencimiento ? 'Editar fecha' : 'Agregar fecha' }}
                                    </button>
                                </div>
                            @else
                                <span class="text-gray-400">
                                    Sin inventario local
                                </span>
                            @endif
                        @else
                            <span class="text-gray-400">
                                Gestion global protegida
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="p-4 text-center text-gray-500">
                        No hay productos registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4" data-async-pagination>
        {{ $productos->links() }}
    </div>
</div>
