<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Proveedores
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

                <a href="{{ route('proveedores.create') }}"
                   class="px-4 py-2 rounded"
                   style="background-color: blue; color: white;">

                    Nuevo Proveedor

                </a>

            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">

                <table class="w-full border text-sm">

                    <thead>

                        <tr class="bg-gray-100">

                            <th class="p-2 border">Nombre</th>

                            <th class="p-2 border">NIT</th>

                            <th class="p-2 border">Teléfono</th>

                            <th class="p-2 border">Dirección</th>

                            <th class="p-2 border">Estado</th>

                            <th class="p-2 border">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($proveedores as $proveedor)

                            <tr>

                                <td class="p-2 border">

                                    {{ $proveedor->nombre }}

                                </td>

                                <td class="p-2 border">

                                    {{ $proveedor->nit ?? '-' }}

                                </td>

                                <td class="p-2 border">

                                    {{ $proveedor->telefono ?? '-' }}

                                </td>

                                <td class="p-2 border">

                                    {{ $proveedor->direccion ?? '-' }}

                                </td>

                                <td class="p-2 border">

                                    @if($proveedor->estado)

                                        <span class="text-green-600 font-bold">
                                            ACTIVO
                                        </span>

                                    @else

                                        <span class="text-red-600 font-bold">
                                            INACTIVO
                                        </span>

                                    @endif

                                </td>

                                <td class="p-2 border">

                                    <div class="flex gap-2">

                                        <a href="{{ route('proveedores.edit', $proveedor) }}"
                                           class="text-blue-600">

                                            Editar

                                        </a>

                                        <form method="POST"
                                              action="{{ route('proveedores.destroy', $proveedor) }}">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="text-red-600"
                                                    onclick="return confirm('¿Desactivar proveedor?')">

                                                Desactivar

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6"
                                    class="p-4 text-center text-gray-500">

                                    No hay proveedores registrados.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

                <div class="mt-4">

                    {{ $proveedores->links() }}

                </div>

            </div>

        </div>

    </div>

</x-app-layout>