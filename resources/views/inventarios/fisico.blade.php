<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
            Inventario Fisico
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($importErrors->isNotEmpty())
                <div class="rounded bg-red-100 p-4 text-sm text-red-800">
                    <div class="font-semibold mb-2">
                        Revisa el archivo antes de continuar:
                    </div>

                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow rounded p-4 sm:p-6">
                    <h3 class="font-bold text-gray-800 mb-2">
                        1. Descargar plantilla por sucursal
                    </h3>

                    <p class="text-sm text-gray-600 mb-4">
                        Descarga el inventario actual, realiza el conteo fisico y llena la columna existencia_fisica.
                    </p>

                    <form method="GET" action="{{ route('inventarios.fisico.plantilla') }}" class="space-y-4">
                        <div>
                            <label for="download_sucursal_id" class="block text-sm font-medium text-gray-700">
                                Sucursal
                            </label>

                            <select id="download_sucursal_id" name="sucursal_id" required class="mt-1 block w-full rounded border-gray-300">
                                <option value="">Selecciona una sucursal</option>

                                @foreach ($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}">
                                        {{ $sucursal->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-white">
                            Descargar CSV
                        </button>
                    </form>
                </div>

                <div class="bg-white shadow rounded p-4 sm:p-6">
                    <h3 class="font-bold text-gray-800 mb-2">
                        2. Subir conteo corregido
                    </h3>

                    <p class="text-sm text-gray-600 mb-4">
                        El sistema mostrara una vista previa. Nada se aplica hasta confirmar.
                    </p>

                    <form method="POST" action="{{ route('inventarios.fisico.preview') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label for="upload_sucursal_id" class="block text-sm font-medium text-gray-700">
                                Sucursal
                            </label>

                            <select id="upload_sucursal_id" name="sucursal_id" required class="mt-1 block w-full rounded border-gray-300">
                                <option value="">Selecciona una sucursal</option>

                                @foreach ($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}" @selected($selectedSucursal === $sucursal->id)>
                                        {{ $sucursal->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="archivo" class="block text-sm font-medium text-gray-700">
                                Archivo CSV
                            </label>

                            <input id="archivo" name="archivo" type="file" accept=".csv,text/csv" required class="mt-1 block w-full text-sm">
                        </div>

                        <button type="submit" class="inline-flex items-center rounded bg-slate-800 px-4 py-2 text-white">
                            Validar archivo
                        </button>
                    </form>
                </div>
            </div>

            @if ($previewRows->isNotEmpty() && $importErrors->isEmpty())
                <div class="bg-white shadow rounded p-4 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-gray-800">
                                Vista previa de ajustes
                            </h3>

                            <p class="text-sm text-gray-600">
                                Se aplicaran solamente las filas con diferencia distinta de cero.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('inventarios.fisico.confirmar') }}">
                            @csrf
                            <input type="hidden" name="sucursal_id" value="{{ $selectedSucursal }}">
                            <input type="hidden" name="rows" value="{{ e($previewRows->toJson()) }}">

                            <button type="submit"
                                    onclick="return confirm('Deseas aplicar estos ajustes de inventario fisico?')"
                                    class="rounded bg-green-600 px-4 py-2 text-white">
                                Confirmar ajustes
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] border text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 border text-left">Codigo</th>
                                    <th class="p-2 border text-left">Producto</th>
                                    <th class="p-2 border text-right">Sistema</th>
                                    <th class="p-2 border text-right">Fisico</th>
                                    <th class="p-2 border text-right">Diferencia</th>
                                    <th class="p-2 border text-left">Observacion</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($previewRows as $row)
                                    <tr>
                                        <td class="p-2 border">
                                            {{ $row['codigo_barra'] }}
                                        </td>

                                        <td class="p-2 border">
                                            {{ $row['producto'] }}
                                        </td>

                                        <td class="p-2 border text-right">
                                            {{ $row['existencia_sistema'] }}
                                        </td>

                                        <td class="p-2 border text-right">
                                            {{ $row['existencia_fisica'] }}
                                        </td>

                                        <td class="p-2 border text-right font-semibold {{ $row['diferencia'] < 0 ? 'text-red-600' : ($row['diferencia'] > 0 ? 'text-green-600' : 'text-gray-500') }}">
                                            {{ $row['diferencia'] > 0 ? '+' : '' }}{{ $row['diferencia'] }}
                                        </td>

                                        <td class="p-2 border">
                                            {{ $row['observacion'] ?: 'Ajuste por inventario fisico' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
