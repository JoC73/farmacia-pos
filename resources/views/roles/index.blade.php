<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Roles y Permisos
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-700 p-4 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4">
                <a href="{{ route('roles.create') }}"
                   class="px-4 py-2 rounded"
                   style="background-color: blue; color: white;">
                    Nuevo Rol
                </a>
            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Rol</th>
                            <th class="p-2 border">Permisos</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td class="p-2 border font-bold">{{ $role->name }}</td>
                                <td class="p-2 border">
                                    {{ $role->permissions->pluck('name')->join(', ') }}
                                </td>
                                <td class="p-2 border">
                                    <a href="{{ route('roles.edit', $role) }}"
                                       class="text-blue-600 font-bold">
                                        Editar permisos
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-gray-500">
                                    No hay roles registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>