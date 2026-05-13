<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cajas
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
                <a href="{{ route('cajas.apertura') }}"
                   class="px-4 py-2 rounded"
                   style="background-color: green; color: white;">
                    Abrir Caja
                </a>
            </div>

            <div class="bg-white shadow rounded p-4 overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">Usuario</th>
                            <th class="p-2 border">Sucursal</th>
                            <th class="p-2 border">Apertura</th>
                            <th class="p-2 border">Cierre</th>
                            <th class="p-2 border">Sistema</th>
                            <th class="p-2 border">Diferencia</th>
                            <th class="p-2 border">Estado</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($cajas as $caja)
                            <tr>
                                <td class="p-2 border">{{ $caja->usuario->name }}</td>
                                <td class="p-2 border">{{ $caja->sucursal->nombre }}</td>
                                <td class="p-2 border">Q {{ number_format($caja->monto_apertura, 2) }}</td>
                                <td class="p-2 border">
                                    {{ $caja->monto_cierre !== null ? 'Q '.number_format($caja->monto_cierre, 2) : '-' }}
                                </td>
                                <td class="p-2 border">Q {{ number_format($caja->total_sistema, 2) }}</td>
                                <td class="p-2 border">Q {{ number_format($caja->diferencia, 2) }}</td>
                                <td class="p-2 border">
                                    @if($caja->estado === 'ABIERTA')
                                        <span class="text-green-600 font-bold">ABIERTA</span>
                                    @else
                                        <span class="text-red-600 font-bold">CERRADA</span>
                                    @endif
                                </td>
                                <td class="p-2 border">
                                    <a href="{{ route('cajas.show', $caja) }}" class="text-blue-600 font-bold">
                                        Ver
                                    </a>

                                    @if($caja->estado === 'ABIERTA')
                                        <a href="{{ route('cajas.cierre', $caja) }}" class="text-red-600 font-bold ml-3">
                                            Cerrar
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-500">
                                    No hay cajas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $cajas->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>