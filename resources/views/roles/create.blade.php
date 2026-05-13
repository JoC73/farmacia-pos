<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo Rol
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
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

                <form method="POST" action="{{ route('roles.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block font-medium mb-1">Nombre del rol</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name') }}"
                               class="w-full border-gray-300 rounded">
                    </div>

                    <h3 class="font-bold mb-3">Permisos</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach($permissions as $permission)
                            <label class="flex items-center gap-2 border rounded p-2">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission->name }}">
                                <span>{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('roles.index') }}"
                           class="px-4 py-2 rounded"
                           style="background-color: gray; color: white;">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-4 py-2 rounded"
                                style="background-color: green; color: white;">
                            Guardar Rol
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>