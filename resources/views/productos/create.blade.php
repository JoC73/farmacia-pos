<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo Producto
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded p-6">

                <form method="POST" action="{{ route('productos.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium">Código de barras</label>
                            <input type="text" name="codigo_barra" value="{{ old('codigo_barra') }}"
                                   class="w-full border-gray-300 rounded mt-1">
                            @error('codigo_barra')
                                <p class="text-red-600 text-sm">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium">Nombre del producto</label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}"
                                   class="w-full border-gray-300 rounded mt-1">
                            @error('nombre')
                                <p class="text-red-600 text-sm">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium">Categoría</label>
                            <select name="categoria_id" class="w-full border-gray-300 rounded mt-1">
                                <option value="">Sin categoría</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium">Laboratorio / Casa comercial</label>
                            <input type="text" name="laboratorio" value="{{ old('laboratorio') }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Costo</label>
                            <input type="number" step="0.01" min="0" name="costo" value="{{ old('costo', 0) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Precio de venta</label>
                            <input type="number" step="0.01" min="0" name="precio_venta" value="{{ old('precio_venta', 0) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Stock mínimo</label>
                            <input type="number" min="0" name="stock_minimo" value="{{ old('stock_minimo', 5) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Fecha de vencimiento</label>
                            <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                    </div>

                    <div class="mt-4">
                        <label class="block font-medium">Descripción</label>
                        <textarea name="descripcion" class="w-full border-gray-300 rounded mt-1">{{ old('descripcion') }}</textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('productos.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded">
                            Cancelar
                        </a>

                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                            Guardar Producto
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>