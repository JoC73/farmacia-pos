<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Categorías
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4">
                <a href="{{ route('categorias.create') }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded">
                    Nueva Categoría
                </a>
            </div>

            <div class="bg-white shadow rounded p-4">
                <table class="w-full border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Nombre</th>
                            <th class="p-2 border">Descripción</th>
                            <th class="p-2 border">Estado</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categorias as $categoria)
                            <tr>
                                <td class="p-2 border">{{ $categoria->nombre }}</td>
                                <td class="p-2 border">{{ $categoria->descripcion }}</td>
                                <td class="p-2 border">
                                    {{ $categoria->estado ? 'Activa' : 'Inactiva' }}
                                </td>
                                <td class="p-2 border">
                                    <a href="{{ route('categorias.edit', $categoria) }}"
                                       class="text-blue-600">
                                        Editar
                                    </a>

                                    <form action="{{ route('categorias.destroy', $categoria) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                onclick="return confirm('¿Deseas desactivar esta categoría?')"
                                                class="text-red-600 ml-3">
                                            Desactivar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500">
                                    No hay categorías registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $categorias->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>