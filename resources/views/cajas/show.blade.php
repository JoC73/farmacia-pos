<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle de Caja
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 print:hidden">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Resumen de caja</h3>
                        <p class="text-sm text-gray-500">Consulta movimientos, cierre y diferencia registrada.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('cajas.index') }}"
                           class="px-4 py-2 rounded text-center"
                           style="background-color: #6b7280; color: white;">
                            Volver
                        </a>

                        <button onclick="window.print()"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">
                            Imprimir
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-300 text-green-700 p-4 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <strong>Usuario:</strong><br>
                        {{ $caja->usuario->name }}
                    </div>

                    <div>
                        <strong>Sucursal:</strong><br>
                        {{ $caja->sucursal->nombre }}
                    </div>

                    <div>
                        <strong>Fecha apertura:</strong><br>
                        {{ $caja->fecha_apertura }}
                    </div>

                    <div>
                        <strong>Fecha cierre:</strong><br>
                        {{ $caja->fecha_cierre ?? '-' }}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Apertura</div>
                        <div class="text-xl font-bold">Q {{ number_format($caja->monto_apertura, 2) }}</div>
                    </div>

                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Cierre</div>
                        <div class="text-xl font-bold">
                            {{ $caja->monto_cierre !== null ? 'Q '.number_format($caja->monto_cierre, 2) : '-' }}
                        </div>
                    </div>

                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Sistema</div>
                        <div class="text-xl font-bold">Q {{ number_format($caja->total_sistema, 2) }}</div>
                    </div>

                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Diferencia</div>
                        <div class="text-xl font-bold">Q {{ number_format($caja->diferencia, 2) }}</div>
                    </div>
                </div>

                <h3 class="font-bold text-lg mb-3">Movimientos</h3>

                <div class="overflow-x-auto">
                    <table class="w-full border text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 border">Tipo</th>
                                <th class="p-2 border">Monto</th>
                                <th class="p-2 border">Referencia</th>
                                <th class="p-2 border">Descripción</th>
                                <th class="p-2 border">Usuario</th>
                                <th class="p-2 border">Fecha</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($caja->movimientos as $movimiento)
                                <tr>
                                    <td class="p-2 border">{{ $movimiento->tipo }}</td>
                                    <td class="p-2 border">Q {{ number_format($movimiento->monto, 2) }}</td>
                                    <td class="p-2 border">{{ $movimiento->referencia ?? '-' }}</td>
                                    <td class="p-2 border">{{ $movimiento->descripcion ?? '-' }}</td>
                                    <td class="p-2 border">{{ $movimiento->usuario->name ?? '-' }}</td>
                                    <td class="p-2 border">
                                        {{ optional($movimiento->fecha_movimiento)->format('d/m/Y H:i') ?? $movimiento->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-500">
                                        No hay movimientos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
