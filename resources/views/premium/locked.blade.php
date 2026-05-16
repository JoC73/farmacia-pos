<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
            Modulo Premium
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 border-l-4 border-yellow-500">
                <div class="text-sm font-semibold text-yellow-700 mb-2">
                    Funcion bloqueada
                </div>

                <h3 class="text-xl font-bold text-gray-900">
                    {{ $module?->name ?? 'Modulo premium' }}
                </h3>

                <p class="mt-2 text-gray-600">
                    {{ $module?->description ?? 'Esta funcion requiere activacion premium.' }}
                </p>

                <p class="mt-4 text-sm text-gray-600">
                    Solicita al Super Usuario que active este modulo desde el centro de activacion premium.
                </p>

                <a href="{{ url()->previous() }}"
                   class="mt-6 inline-flex rounded bg-slate-800 px-4 py-2 text-white">
                    Volver
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
