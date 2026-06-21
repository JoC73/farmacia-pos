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
                        <p class="text-sm text-gray-500">Inicia la caja del dia con el efectivo inicial.</p>
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

                    @if($sucursales->count() > 1)
                        <div class="mb-4">
                            <label class="block font-medium mb-1">
                                Sucursal
                            </label>

                            <select name="sucursal_id"
                                    class="w-full border-gray-300 rounded"
                                    required>
                                <option value="">Seleccione sucursal</option>
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}" @selected(old('sucursal_id') == $sucursal->id)>
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

                    <div>
                        <label class="block font-medium mb-1">
                            Monto de apertura
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="monto_apertura"
                               value="{{ old('monto_apertura', 0) }}"
                               class="w-full border-gray-300 rounded">
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
</x-app-layout>
