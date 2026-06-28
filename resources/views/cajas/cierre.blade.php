<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cierre de Caja
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Resumen antes del cierre</h3>
                        <p class="text-sm text-gray-500">Verifica ventas, egresos y efectivo contado.</p>
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

                <div class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Apertura</div>
                        <div class="text-xl font-bold">Q {{ number_format($caja->monto_apertura, 2) }}</div>
                    </div>

                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Ventas</div>
                        <div class="text-xl font-bold">Q {{ number_format($ventas, 2) }}</div>
                    </div>

                    <div class="p-4 bg-red-50 rounded">
                        <div class="text-sm text-red-600">Egresos</div>
                        <div class="text-xl font-bold text-red-700">Q {{ number_format($egresos, 2) }}</div>
                    </div>

                    <div class="p-4 bg-emerald-50 rounded">
                        <div class="text-sm text-emerald-700">Transferencias</div>
                        <div class="text-xl font-bold text-emerald-800">Q {{ number_format($transferencias, 2) }}</div>
                    </div>

                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Total sistema</div>
                        <div class="text-xl font-bold">Q {{ number_format($totalSistema, 2) }}</div>
                    </div>
                </div>

                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                    El total de sistema descuenta egresos y transferencias registradas antes del cierre.
                    Salidas registradas: <strong>Q {{ number_format($salidas, 2) }}</strong>.
                </div>

                <form method="POST" action="{{ route('cajas.cierre.store', $caja) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            Monto contado en caja
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="monto_cierre"
                               value="{{ old('monto_cierre', number_format($totalSistema, 2, '.', '')) }}"
                               class="w-full border-gray-300 rounded">

                        <p class="mt-2 text-sm text-gray-500">
                            El sistema sugiere Q {{ number_format($totalSistema, 2) }}. Si el efectivo contado es distinto, ajusta el monto y quedara registrada la diferencia.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            Observación
                        </label>

                        <textarea name="observacion"
                                  rows="3"
                                  class="w-full border-gray-300 rounded">{{ old('observacion') }}</textarea>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('cajas.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: red; color: white;">
                            Cerrar Caja
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
