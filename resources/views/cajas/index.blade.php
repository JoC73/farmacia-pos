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
                @if($cajaAbiertaActual)
                    <div class="bg-emerald-50 border border-emerald-200 rounded p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div>
                            <div class="font-bold text-emerald-800">
                                Caja abierta en tu sucursal
                            </div>
                            <div class="text-sm text-emerald-700">
                                Caja #{{ $cajaAbiertaActual->id }} abierta por {{ $cajaAbiertaActual->usuario->name ?? '-' }}
                                con Q {{ number_format($cajaAbiertaActual->monto_apertura, 2) }}.
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('ventas.create') }}"
                               class="px-4 py-2 rounded text-white"
                               style="background-color: #2563eb;">
                                Ir a Ventas
                            </a>

                            <a href="{{ route('cajas.show', $cajaAbiertaActual) }}"
                               class="px-4 py-2 rounded text-white"
                               style="background-color: #047857;">
                                Ver Caja
                            </a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('cajas.apertura') }}"
                       class="px-4 py-2 rounded"
                       style="background-color: green; color: white;">
                        Abrir Caja
                    </a>
                @endif
            </div>

            @can('caja.ver_cierres')
                <div class="mb-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="bg-white shadow rounded p-4">
                        <div class="text-sm text-gray-500">Transferencias del mes</div>
                        <div class="text-2xl font-bold text-emerald-700">
                            Q {{ number_format($totalTransferenciasMes, 2) }}
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-white shadow rounded p-4">
                        <h3 class="font-bold text-gray-800 mb-3">Historial reciente de transferencias</h3>

                        <div class="overflow-x-auto">
                            <table class="w-full border text-sm">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-2 border text-left">Sucursal</th>
                                        <th class="p-2 border text-left">Referencia</th>
                                        <th class="p-2 border text-right">Monto</th>
                                        <th class="p-2 border text-left">Usuario</th>
                                        <th class="p-2 border text-left">Fecha</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($transferencias as $transferencia)
                                        <tr>
                                            <td class="p-2 border">{{ $transferencia->caja->sucursal->nombre ?? '-' }}</td>
                                            <td class="p-2 border">{{ $transferencia->referencia ?? '-' }}</td>
                                            <td class="p-2 border text-right">Q {{ number_format($transferencia->monto, 2) }}</td>
                                            <td class="p-2 border">{{ $transferencia->usuario->name ?? '-' }}</td>
                                            <td class="p-2 border">
                                                {{ ($transferencia->fecha_movimiento ?? $transferencia->created_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="p-4 text-center text-gray-500">
                                                No hay transferencias registradas.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endcan

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

                                        @can('caja.ver_cierres')
                                            <a href="{{ route('cajas.egreso', $caja) }}" class="text-amber-600 font-bold ml-3">
                                                Egreso
                                            </a>
                                        @endcan

                                        <a href="{{ route('cajas.transferencia', $caja) }}" class="text-emerald-700 font-bold ml-3">
                                            Transferir
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
