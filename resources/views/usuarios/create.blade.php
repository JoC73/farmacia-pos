<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo Usuario
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
                      action="{{ route('usuarios.store') }}">

                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>

                            <label class="block font-medium mb-1">
                                Nombre
                            </label>

                            <input type="text"
                                   name="name"
                                   value="{{ old('name') }}"
                                   class="w-full border-gray-300 rounded">

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Correo
                            </label>

                            <input type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   class="w-full border-gray-300 rounded">

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Contraseña
                            </label>

                            <input type="password"
                                   name="password"
                                   class="w-full border-gray-300 rounded">

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Confirmar contraseña
                            </label>

                            <input type="password"
                                   name="password_confirmation"
                                   class="w-full border-gray-300 rounded">

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Sucursal
                            </label>

                            <select name="sucursal_id"
                                    class="w-full border-gray-300 rounded">

                                <option value="">
                                    Seleccione sucursal
                                </option>

                                @foreach($sucursales as $sucursal)

                                    <option value="{{ $sucursal->id }}">

                                        {{ $sucursal->nombre }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div>

                            <label class="block font-medium mb-1">
                                Rol
                            </label>

                            <select name="rol"
                                    class="w-full border-gray-300 rounded">

                                <option value="">
                                    Seleccione rol
                                </option>

                                @foreach($roles as $rol)

                                    <option value="{{ $rol->name }}">

                                        {{ $rol->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="mt-6 flex gap-3">

                        <a href="{{ route('usuarios.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">

                            Cancelar

                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">

                            Guardar Usuario

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>