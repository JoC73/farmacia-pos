<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Corte Mensual de Caja
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                <a href="{{ route('cajas.index') }}"
                   class="px-4 py-2 rounded text-center"
                   style="background-color: #6b7280; color: white;">
                    Volver
                </a>
            </div>

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow rounded p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ ucfirst($periodo['label']) }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        Registra el efectivo enviado al jefe y define cuanto queda disponible en caja.
                    </p>
                </div>

                <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded bg-gray-100 p-4">
                        <div class="text-sm text-gray-500">Apertura</div>
                        <div class="text-xl font-bold">Q {{ number_format($resumen['saldo_inicial'], 2) }}</div>
                    </div>

                    <div class="rounded bg-gray-100 p-4">
                        <div class="text-sm text-gray-500">Ventas</div>
                        <div class="text-xl font-bold">Q {{ number_format($resumen['ventas'], 2) }}</div>
                    </div>

                    <div class="rounded bg-red-50 p-4">
                        <div class="text-sm text-red-600">Egresos</div>
                        <div class="text-xl font-bold text-red-700">Q {{ number_format($resumen['egresos'], 2) }}</div>
                    </div>

                    <div class="rounded bg-emerald-50 p-4">
                        <div class="text-sm text-emerald-700">Disponible</div>
                        <div class="text-xl font-bold text-emerald-800">Q {{ number_format($resumen['disponible_antes'], 2) }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('cajas.corte-mensual.store') }}">
                    @csrf

                    <input type="hidden" name="periodo_year" value="{{ $periodo['year'] }}">
                    <input type="hidden" name="periodo_month" value="{{ $periodo['month'] }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Monto a transferir</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   max="{{ number_format($resumen['disponible_antes'], 2, '.', '') }}"
                                   id="monto-transferido"
                                   name="monto_transferido"
                                   value="{{ old('monto_transferido', number_format($resumen['disponible_antes'], 2, '.', '')) }}"
                                   class="w-full border-gray-300 rounded">
                        </div>

                        <div>
                            <label class="block font-medium mb-1">Saldo que queda en caja</label>
                            <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-lg font-bold">
                                Q <span id="saldo-restante">0.00</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">Referencia</label>
                            <input type="text"
                                   name="referencia"
                                   value="{{ old('referencia') }}"
                                   placeholder="Boleta o comprobante"
                                   class="w-full border-gray-300 rounded">
                        </div>

                        <div>
                            <label class="block font-medium mb-1">Observación</label>
                            <input type="text"
                                   name="observacion"
                                   value="{{ old('observacion') }}"
                                   placeholder="Opcional"
                                   class="w-full border-gray-300 rounded">
                        </div>
                    </div>

                    <div class="mt-6 rounded border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        Si transfieres todo, la caja queda en Q 0.00. Si dejas una parte, ese monto queda como efectivo disponible para operar.
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                        <a href="{{ route('cajas.index') }}"
                           class="px-4 py-2 rounded text-center"
                           style="background-color: #6b7280; color: white;">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded font-bold"
                                style="background-color: green; color: white;">
                            Registrar Corte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const disponible = {{ Illuminate\Support\Js::from((float) $resumen['disponible_antes']) }};
        const montoInput = document.getElementById('monto-transferido');
        const saldoRestante = document.getElementById('saldo-restante');

        function actualizarSaldoRestante() {
            const transferido = Math.max(0, Number(montoInput.value || 0));
            saldoRestante.textContent = Math.max(0, disponible - transferido).toFixed(2);
        }

        montoInput.addEventListener('input', actualizarSaldoRestante);
        actualizarSaldoRestante();
    </script>
</x-app-layout>
