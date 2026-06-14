<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Sucursal
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">

                @unless($sucursal->estado)
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 p-4 rounded">
                        Esta sucursal se encuentra inhabilitada. Puede revisar sus datos o reactivarla marcando la casilla "Sucursal activa".
                    </div>
                @endunless

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 p-4 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('sucursales.update', $sucursal) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Nombre</label>
                            <input type="text"
                                   name="nombre"
                                   value="{{ old('nombre', $sucursal->nombre) }}"
                                   class="w-full border-gray-300 rounded">
                        </div>

                        <div>
                            <label class="block font-medium mb-1">Teléfono</label>
                            <input type="text"
                                   name="telefono"
                                   value="{{ old('telefono', $sucursal->telefono) }}"
                                   class="w-full border-gray-300 rounded">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block font-medium mb-1">Dirección</label>
                            <input type="text"
                                   name="direccion"
                                   value="{{ old('direccion', $sucursal->direccion) }}"
                                   class="w-full border-gray-300 rounded">
                        </div>

                        <div class="md:col-span-2">
                            @if($sucursal->estado || Auth::user()->hasRole('Super Usuario'))
                                <label class="inline-flex items-center">
                                    <input type="checkbox"
                                           name="estado"
                                           value="1"
                                           {{ $sucursal->estado ? 'checked' : '' }}>
                                    <span class="ml-2">Sucursal activa</span>
                                </label>
                            @else
                                <div class="text-sm text-gray-500">
                                    La reactivacion de sucursales solo puede realizarla el Super Usuario.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('sucursales.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">
                            Actualizar Sucursal
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
