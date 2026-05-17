<x-app-layout>
    @php
        $branchCreationEnabled = \App\Models\PremiumModule::enabled('branch_creation') || Auth::user()->hasRole('Super Usuario');
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sucursales
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
                <a href="{{ route('sucursales.create') }}"
                   class="px-4 py-2 rounded"
                   style="background-color: {{ $branchCreationEnabled ? 'blue' : '#334155' }}; color: white;">
                    Nueva Sucursal
                    @unless($branchCreationEnabled)
                        <span class="ml-1 text-xs">(Premium)</span>
                    @endunless
                </a>
            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Nombre</th>
                            <th class="p-2 border">Dirección</th>
                            <th class="p-2 border">Teléfono</th>
                            <th class="p-2 border">Estado</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sucursales as $sucursal)
                            <tr>
                                <td class="p-2 border">{{ $sucursal->nombre }}</td>
                                <td class="p-2 border">{{ $sucursal->direccion ?? '-' }}</td>
                                <td class="p-2 border">{{ $sucursal->telefono ?? '-' }}</td>
                                <td class="p-2 border">
                                    {{ $sucursal->estado ? 'ACTIVA' : 'INACTIVA' }}
                                </td>
<td class="p-2 border">
    <a href="{{ route('sucursales.edit', $sucursal) }}"
       class="text-blue-600">
        Editar
    </a>

    <form method="POST"
          action="{{ route('sucursales.destroy', $sucursal) }}"
          class="inline">
        @csrf
        @method('DELETE')

        <button type="submit"
                onclick="return confirm('¿Desactivar sucursal?')"
                class="text-red-600 ml-3">
            Desactivar
        </button>
    </form>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">
                                    No hay sucursales registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $sucursales->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
