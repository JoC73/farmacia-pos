<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Transferencia a Jefe
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Registrar transferencia</h3>
                        <p class="text-sm text-gray-500">
                            Registra efectivo enviado al jefe sin perder el control diario de caja.
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

                <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Caja</div>
                        <div class="font-bold">#{{ $caja->id }}</div>
                    </div>

                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Sucursal</div>
                        <div class="font-bold">{{ $caja->sucursal->nombre ?? '-' }}</div>
                    </div>

                    <div class="p-4 bg-emerald-50 rounded">
                        <div class="text-sm text-emerald-700">Efectivo disponible</div>
                        <div class="text-xl font-bold text-emerald-800">Q {{ number_format($disponible, 2) }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('cajas.transferencia.store', $caja) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            No. de boleta, recibo o referencia
                        </label>

                        <input type="text"
                               name="referencia"
                               value="{{ old('referencia') }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            Fecha de transferencia
                        </label>

                        <input type="datetime-local"
                               name="fecha_movimiento"
                               value="{{ old('fecha_movimiento', now()->format('Y-m-d\TH:i')) }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center justify-between gap-3">
                            <label class="block font-medium mb-1">
                                Monto a transferir
                            </label>

                            <button type="button"
                                    class="text-sm font-semibold text-blue-700"
                                    onclick="document.getElementById('monto-transferencia').value = '{{ number_format($disponible, 2, '.', '') }}'">
                                Usar disponible
                            </button>
                        </div>

                        <input id="monto-transferencia"
                               type="number"
                               step="0.01"
                               min="0.01"
                               max="{{ number_format($disponible, 2, '.', '') }}"
                               name="monto"
                               value="{{ old('monto') }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            Observacion
                        </label>

                        <textarea name="descripcion"
                                  rows="3"
                                  class="w-full border-gray-300 rounded"
                                  placeholder="Ejemplo: Transferencia mensual de ventas a jefe">{{ old('descripcion') }}</textarea>
                    </div>

                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                        Responsable: <strong>{{ auth()->user()->name }}</strong>.
                        Esta salida queda en el historial de caja y sera tomada en cuenta al cerrar.
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('cajas.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: #047857; color: white;">
                            Guardar Transferencia
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
