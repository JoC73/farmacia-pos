<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Usuarios
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

                @can('usuarios.crear')
                    <a href="{{ route('usuarios.create') }}"
                       class="px-4 py-2 rounded"
                       style="background-color: blue; color: white;">

                        Nuevo Usuario

                    </a>
                @endcan

            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">

                <table class="w-full border text-sm">

                    <thead>

                        <tr class="bg-gray-100">

                            <th class="p-2 border">Nombre</th>

                            <th class="p-2 border">Correo</th>

                            <th class="p-2 border">Sucursal</th>

                            <th class="p-2 border">Rol</th>

                            <th class="p-2 border">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($usuarios as $usuario)

                            <tr>

                                <td class="p-2 border">

                                    {{ $usuario->name }}

                                </td>

                                <td class="p-2 border">

                                    {{ $usuario->email }}

                                </td>

                                <td class="p-2 border">

                                    {{ $usuario->sucursal->nombre ?? '-' }}

                                </td>

                                <td class="p-2 border">

                                    {{ $usuario->roles->first()?->name ?? '-' }}

                                </td>

                                <td class="p-2 border whitespace-nowrap">

                                    @can('usuarios.editar')

                                        <a href="{{ route('usuarios.edit', $usuario) }}"
                                           class="text-blue-600">

                                            Editar

                                        </a>

                                    @endcan

                                    @can('usuarios.eliminar')

                                        @if($usuario->id !== auth()->id())

                                            <form method="POST"
                                                  action="{{ route('usuarios.destroy', $usuario) }}"
                                                  class="inline">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                        onclick="return confirm('¿Eliminar usuario?')"
                                                        class="text-red-600 ml-3">

                                                    Eliminar

                                                </button>

                                            </form>

                                        @endif

                                    @endcan

                                    @cannot('usuarios.editar')

                                        @cannot('usuarios.eliminar')

                                            <span class="text-gray-400">

                                                Sin acciones

                                            </span>

                                        @endcannot

                                    @endcannot

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5"
                                    class="p-4 text-center text-gray-500">

                                    No hay usuarios registrados.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

                <div class="mt-4">

                    {{ $usuarios->links() }}

                </div>

            </div>

        </div>

    </div>

</x-app-layout>