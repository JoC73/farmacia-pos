<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
            Modulos Premium
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="rounded bg-green-100 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow rounded p-4 sm:p-6">
                <h3 class="font-bold text-gray-800 mb-2">
                    Centro de activacion premium
                </h3>

                <p class="text-sm text-gray-600">
                    Solo el Super Usuario puede activar o desactivar estos modulos. Los usuarios administradores
                    podran ver algunos modulos bloqueados, pero no podran desbloquearlos.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($modules as $module)
                    <div class="bg-white shadow rounded p-4 border-l-4 {{ $module->enabled ? 'border-green-500' : 'border-gray-300' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-bold text-gray-900">
                                    {{ $module->name }}
                                </h4>

                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $module->description }}
                                </p>
                            </div>

                            <span class="shrink-0 rounded px-2 py-1 text-xs font-semibold {{ $module->enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $module->enabled ? 'Activo' : 'Bloqueado' }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('premium.toggle', $module) }}" class="mt-4">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    onclick="return confirm('Confirmas cambiar el estado de este modulo premium?')"
                                    class="w-full rounded px-4 py-2 text-white {{ $module->enabled ? 'bg-red-600' : 'bg-blue-600' }}">
                                {{ $module->enabled ? 'Desactivar modulo' : 'Activar modulo' }}
                            </button>
                        </form>

                        @if($module->enabled_at)
                            <div class="mt-3 text-xs text-gray-500">
                                Activado el {{ $module->enabled_at->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
