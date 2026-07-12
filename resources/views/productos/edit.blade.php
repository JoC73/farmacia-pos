<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Producto
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded p-6">

                <form method="POST" action="{{ route('productos.update', $producto) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium">Código de barras</label>
                            <input type="text" name="codigo_barra" value="{{ old('codigo_barra', $producto->codigo_barra) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                            @error('codigo_barra')
                                <p class="text-red-600 text-sm">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium">Nombre del producto</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}"
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
                                    <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)>
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium">Laboratorio / Casa comercial</label>
                            <input type="text" name="laboratorio" value="{{ old('laboratorio', $producto->laboratorio) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Costo</label>
                            <input type="number" step="0.01" min="0" name="costo" value="{{ old('costo', $producto->costo) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Precio de venta</label>
                            <input type="number" step="0.01" min="0" name="precio_venta" value="{{ old('precio_venta', $producto->precio_venta) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Stock mínimo</label>
                            <input type="number" min="0" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                        <div>
                            <label class="block font-medium">Fecha de vencimiento</label>
                            <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', $producto->fecha_vencimiento) }}"
                                   class="w-full border-gray-300 rounded mt-1">
                        </div>

                    </div>

                    <div class="mt-4">
                        <label class="block font-medium">Descripción</label>
                        <textarea name="descripcion" class="w-full border-gray-300 rounded mt-1">{{ old('descripcion', $producto->descripcion) }}</textarea>
                    </div>

                    <div class="mt-6 rounded border border-blue-100 bg-blue-50 p-4">
                        <h3 class="font-semibold text-blue-950">
                            Aplicar cambios en sucursales
                        </h3>
                        <p class="mt-1 text-sm text-blue-900">
                            Los precios que ve cada sucursal pueden venir del Excel. Si deseas que este cambio global se refleje en las sucursales, elige una opcion de aplicacion.
                        </p>

                        @php
                            $syncMode = old('aplicar_en_sucursales', 'catalogo');
                            $selectedSucursalIds = collect(old('sucursal_ids', []))->map(fn($id) => (int) $id)->all();
                        @endphp

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <label class="flex cursor-pointer items-start gap-3 rounded border bg-white p-3">
                                <input type="radio"
                                       name="aplicar_en_sucursales"
                                       value="catalogo"
                                       class="mt-1"
                                       @checked($syncMode === 'catalogo')>
                                <span>
                                    <span class="block font-semibold">Solo catalogo</span>
                                    <span class="text-sm text-gray-600">No toca precios ni datos locales de sucursales.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded border bg-white p-3">
                                <input type="radio"
                                       name="aplicar_en_sucursales"
                                       value="todas"
                                       class="mt-1"
                                       @checked($syncMode === 'todas')>
                                <span>
                                    <span class="block font-semibold">Todas las sucursales</span>
                                    <span class="text-sm text-gray-600">Actualiza inventarios activos donde el producto ya existe.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded border bg-white p-3">
                                <input type="radio"
                                       name="aplicar_en_sucursales"
                                       value="seleccionadas"
                                       class="mt-1"
                                       @checked($syncMode === 'seleccionadas')>
                                <span>
                                    <span class="block font-semibold">Elegir sucursales</span>
                                    <span class="text-sm text-gray-600">Replica solo en las sucursales marcadas.</span>
                                </span>
                            </label>
                        </div>

                        <div class="mt-4 rounded border bg-white p-3">
                            <div class="mb-2 text-sm font-semibold text-gray-700">
                                Sucursales disponibles
                            </div>

                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                @foreach($sucursales as $sucursal)
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox"
                                               name="sucursal_ids[]"
                                               value="{{ $sucursal->id }}"
                                               @checked(in_array($sucursal->id, $selectedSucursalIds, true))>
                                        <span>{{ $sucursal->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <p class="mt-2 text-xs text-gray-500">
                                Esta seleccion solo aplica cuando eliges "Elegir sucursales". No crea inventario nuevo; solo actualiza el inventario activo existente.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="estado" value="1" {{ $producto->estado ? 'checked' : '' }}>
                            <span class="ml-2">Producto activo</span>
                        </label>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('productos.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded">
                            Cancelar
                        </a>

                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                            Actualizar Producto
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>
