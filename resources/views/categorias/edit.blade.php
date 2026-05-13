<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Categoría
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded p-6">

                <form method="POST" action="{{ route('categorias.update', $categoria) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block font-medium">Nombre</label>
                        <input type="text"
                               name="nombre"
                               value="{{ old('nombre', $categoria->nombre) }}"
                               class="w-full border-gray-300 rounded mt-1">

                        @error('nombre')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Descripción</label>
                        <textarea name="descripcion"
                                  class="w-full border-gray-300 rounded mt-1">{{ old('descripcion', $categoria->descripcion) }}</textarea>

                        @error('descripcion')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   name="estado"
                                   value="1"
                                   {{ $categoria->estado ? 'checked' : '' }}>
                            <span class="ml-2">Categoría activa</span>
                        </label>
                    </div>

                    <div class="flex justify-between">
                        <a href="{{ route('categorias.index') }}"
                           class="px-4 py-2 bg-gray-500 text-white rounded">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded">
                            Actualizar
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>