<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Cliente
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

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

                <form method="POST"
                      action="{{ route('clientes.update', $cliente) }}">

                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>

                            <label class="block font-medium mb-1">
                                NIT
                            </label>

                            <input type="text"
                                   name="nit"
                                   value="{{ old('nit', $cliente->nit) }}"
                                   class="w-full border-gray-300 rounded">

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Nombre
                            </label>

                            <input type="text"
                                   name="nombre"
                                   value="{{ old('nombre', $cliente->nombre) }}"
                                   class="w-full border-gray-300 rounded">

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Teléfono
                            </label>

                            <input type="text"
                                   name="telefono"
                                   value="{{ old('telefono', $cliente->telefono) }}"
                                   class="w-full border-gray-300 rounded">

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Dirección
                            </label>

                            <input type="text"
                                   name="direccion"
                                   value="{{ old('direccion', $cliente->direccion) }}"
                                   class="w-full border-gray-300 rounded">

                        </div>

                    </div>

                    <div class="mt-6 flex gap-3">

                        <a href="{{ route('clientes.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">

                            Cancelar

                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">

                            Actualizar Cliente

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>