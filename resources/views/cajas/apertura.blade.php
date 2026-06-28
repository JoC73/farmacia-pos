<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Apertura de Caja
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Nueva apertura</h3>
                        <p class="text-sm text-gray-500">
                            Inicia la caja con el saldo trasladado del ultimo cierre de la sucursal.
                        </p>
                    </div>

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

                <form method="POST" action="{{ route('cajas.apertura.store') }}">
                    @csrf

                    @php
                        $selectedSucursalId = (int) old('sucursal_id', $sucursales->count() === 1 ? $sucursales->first()->id : 0);
                        $selectedSaldo = $saldosSugeridos[$selectedSucursalId] ?? null;
                        $hasPreviousClose = (bool) ($selectedSaldo['tiene_historial'] ?? false);
                        $openingValue = old('monto_apertura', number_format((float) ($selectedSaldo['monto'] ?? 0), 2, '.', ''));
                    @endphp

                    @if($sucursales->count() > 1)
                        <div class="mb-4">
                            <label class="block font-medium mb-1">
                                Sucursal
                            </label>

                            <select id="sucursal-apertura"
                                    name="sucursal_id"
                                    class="w-full border-gray-300 rounded"
                                    required>
                                <option value="">Seleccione sucursal</option>
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}" @selected($selectedSucursalId === $sucursal->id)>
                                        {{ $sucursal->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($sucursales->count() === 1)
                        <input type="hidden" name="sucursal_id" value="{{ $sucursales->first()->id }}">

                        <div class="mb-4 rounded bg-gray-100 p-3">
                            <div class="text-sm text-gray-500">
                                Sucursal
                            </div>
                            <div class="font-bold text-gray-800">
                                {{ $sucursales->first()->nombre }}
                            </div>
                        </div>
                    @endif

                    <div id="saldo-sugerido-card" class="mb-4 rounded border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        <div class="font-bold">Saldo sugerido para apertura</div>
                        <div id="saldo-sugerido-text">
                            @if($hasPreviousClose)
                                Se usara el cierre de la caja #{{ $selectedSaldo['caja_id'] }}:
                                <strong>Q {{ number_format((float) $selectedSaldo['monto'], 2) }}</strong>
                                @if($selectedSaldo['fecha_cierre'])
                                    <span class="text-blue-700">({{ $selectedSaldo['fecha_cierre'] }})</span>
                                @endif
                            @else
                                Esta sucursal no tiene caja cerrada previa. Ingresa el fondo inicial.
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium mb-1">
                            Monto de apertura
                        </label>

                        <input type="number"
                               id="monto-apertura"
                               step="0.01"
                               min="0"
                               name="monto_apertura"
                               value="{{ $openingValue }}"
                               @if($hasPreviousClose && ! $puedeCorregirApertura) readonly @endif
                               class="w-full border-gray-300 rounded">

                        <p id="apertura-help" class="mt-2 text-sm text-gray-500">
                            @if($hasPreviousClose && $puedeCorregirApertura)
                                El sistema sugiere el ultimo cierre. Puedes corregirlo solo si hubo un error operativo.
                            @elseif($hasPreviousClose)
                                Este monto viene del ultimo cierre y no puede ser modificado por tu rol.
                            @else
                                Al ser la primera caja de la sucursal, este sera el fondo inicial.
                            @endif
                        </p>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('cajas.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">
                            Abrir Caja
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        (() => {
            const saldos = @json($saldosSugeridos);
            const puedeCorregir = @json($puedeCorregirApertura);
            const sucursalSelect = document.getElementById('sucursal-apertura');
            const montoInput = document.getElementById('monto-apertura');
            const saldoText = document.getElementById('saldo-sugerido-text');
            const helpText = document.getElementById('apertura-help');

            if (!montoInput || !saldoText || !helpText) {
                return;
            }

            const money = (value) => Number(value || 0).toFixed(2);

            const render = (sucursalId) => {
                const saldo = saldos[sucursalId] || { monto: 0, tiene_historial: false };
                const tieneHistorial = Boolean(saldo.tiene_historial);

                montoInput.value = money(saldo.monto);
                montoInput.readOnly = tieneHistorial && !puedeCorregir;

                if (tieneHistorial) {
                    saldoText.innerHTML = `Se usara el cierre de la caja #${saldo.caja_id}: <strong>Q ${money(saldo.monto)}</strong>${saldo.fecha_cierre ? ` <span class="text-blue-700">(${saldo.fecha_cierre})</span>` : ''}`;
                    helpText.textContent = puedeCorregir
                        ? 'El sistema sugiere el ultimo cierre. Puedes corregirlo solo si hubo un error operativo.'
                        : 'Este monto viene del ultimo cierre y no puede ser modificado por tu rol.';
                } else {
                    saldoText.textContent = 'Esta sucursal no tiene caja cerrada previa. Ingresa el fondo inicial.';
                    helpText.textContent = 'Al ser la primera caja de la sucursal, este sera el fondo inicial.';
                }
            };

            if (sucursalSelect) {
                sucursalSelect.addEventListener('change', () => render(sucursalSelect.value));
            }
        })();
    </script>
</x-app-layout>
