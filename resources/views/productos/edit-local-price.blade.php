<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cambiar precio de venta
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <div class="mb-6 rounded border border-emerald-100 bg-emerald-50 p-4">
                    <div class="text-sm font-semibold text-emerald-900">
                        {{ $inventario->sucursal->nombre ?? auth()->user()->sucursal?->nombre ?? 'Sucursal asignada' }}
                    </div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">
                        {{ $inventario->nombre_mostrado }}
                    </div>
                    <div class="mt-2 grid gap-3 text-sm text-gray-700 md:grid-cols-3">
                        <div>
                            <span class="block text-gray-500">Codigo</span>
                            <span class="font-semibold">{{ $producto->codigo_barra }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500">Precio catalogo</span>
                            <span class="font-semibold">Q {{ number_format($producto->precio_venta, 2) }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500">Stock actual</span>
                            <span class="font-semibold">{{ $inventario->existencia }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('productos.update', $producto) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="precio_venta" class="block font-medium">
                            Precio de venta en esta sucursal
                        </label>
                        <input id="precio_venta"
                               type="number"
                               step="0.01"
                               min="0"
                               name="precio_venta"
                               value="{{ old('precio_venta', $inventario->precio_venta_mostrado) }}"
                               class="mt-1 w-full rounded border-gray-300 text-lg font-semibold"
                               autofocus>
                        @error('precio_venta')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500">
                            Este precio se usa inmediatamente en ventas para esta sucursal.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('productos.index') }}" class="rounded bg-gray-500 px-4 py-2 text-white">
                            Cancelar
                        </a>

                        <button type="submit" class="rounded bg-emerald-600 px-4 py-2 font-semibold text-white">
                            Guardar precio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
