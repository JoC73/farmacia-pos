<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ajustar Existencia
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">
                            Producto
                        </div>
                        <div class="font-bold text-gray-900">
                            {{ $inventario->producto->nombre ?? 'Producto eliminado' }}
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">
                            Sucursal
                        </div>
                        <div class="font-bold text-gray-900">
                            {{ $inventario->sucursal->nombre ?? 'Sucursal eliminada' }}
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">
                            Existencia actual
                        </div>
                        <div class="text-2xl font-bold text-blue-700">
                            {{ $inventario->existencia }}
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded p-4">
                        <div class="text-sm text-gray-500">
                            Stock mínimo
                        </div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ $inventario->producto->stock_minimo ?? 0 }}
                        </div>
                    </div>

                </div>

                <form method="POST" action="{{ route('inventarios.ajustar.update', $inventario) }}">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Nueva existencia
                            </label>
                            <input type="number"
                                   min="0"
                                   name="existencia"
                                   value="{{ old('existencia', $inventario->existencia) }}"
                                   class="w-full border-gray-300 rounded"
                                   required>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Observación
                            </label>
                            <textarea name="observacion"
                                      rows="3"
                                      class="w-full border-gray-300 rounded"
                                      placeholder="Ejemplo: ajuste por conteo físico, merma o corrección administrativa">{{ old('observacion') }}</textarea>
                        </div>

                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('inventarios.index') }}"
                           class="px-4 py-2 rounded bg-gray-700 text-white">
                            Volver
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded bg-green-700 text-white"
                                onclick="return confirm('¿Confirmar ajuste de existencia? Esta acción quedará registrada en movimientos de inventario.')">
                            Guardar Ajuste
                        </button>
                    </div>
                </form>

            </div>

        </div>

    </div>

</x-app-layout>
