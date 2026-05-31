<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Registrar Egreso de Caja
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Caja</div>
                        <div class="font-bold">#{{ $caja->id }} - {{ $caja->usuario->name ?? auth()->user()->name }}</div>
                    </div>

                    <div class="p-4 bg-gray-100 rounded">
                        <div class="text-sm text-gray-500">Estado</div>
                        <div class="font-bold text-green-700">{{ $caja->estado }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('cajas.egreso.store', $caja) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            No. de factura o recibo
                        </label>

                        <input type="text"
                               name="referencia"
                               value="{{ old('referencia') }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            Fecha de movimiento
                        </label>

                        <input type="datetime-local"
                               name="fecha_movimiento"
                               value="{{ old('fecha_movimiento', now()->format('Y-m-d\TH:i')) }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium mb-1">
                            Cantidad
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0.01"
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
                                  class="w-full border-gray-300 rounded">{{ old('descripcion') }}</textarea>
                    </div>

                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded text-sm text-amber-800">
                        Usuario responsable: <strong>{{ auth()->user()->name }}</strong>.
                        Una vez cerrada la caja, ya no se podran registrar egresos.
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('cajas.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: #b45309; color: white;">
                            Guardar Egreso
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
