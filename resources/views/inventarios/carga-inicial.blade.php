<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
            Carga Inicial Masiva
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="rounded bg-red-100 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($importErrors->isNotEmpty())
                <div class="rounded bg-red-100 p-4 text-sm text-red-800">
                    <div class="font-semibold mb-2">Revisa el archivo antes de continuar:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow rounded p-4 sm:p-6 border-l-4 border-indigo-500">
                <h3 class="font-bold text-gray-800 mb-2">
                    Importacion inicial de productos por sucursal
                </h3>

                <p class="text-sm text-gray-600">
                    Este modulo premium permite crear o actualizar productos globales desde Excel y registrar su
                    existencia inicial en una o varias sucursales. Si no se coloca codigo_barra, el sistema intentara
                    reconocer el producto por nombre/laboratorio antes de generar un codigo interno.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow rounded p-4 sm:p-6">
                    <h3 class="font-bold text-gray-800 mb-2">
                        1. Descargar plantilla
                    </h3>

                    <p class="text-sm text-gray-600 mb-4">
                        Llena los productos que deseas crear o actualizar. El codigo de barra es opcional.
                    </p>

                    <a href="{{ route('inventarios.carga-inicial.plantilla') }}"
                       class="inline-flex rounded bg-blue-600 px-4 py-2 text-white">
                        Descargar Excel
                    </a>
                </div>

                <div class="bg-white shadow rounded p-4 sm:p-6">
                    <h3 class="font-bold text-gray-800 mb-2">
                        2. Subir archivo
                    </h3>

                    <form method="POST" action="{{ route('inventarios.carga-inicial.preview') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Sucursales a afectar
                            </label>

                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($sucursales as $sucursal)
                                    <label class="flex items-center gap-2 rounded border border-gray-200 p-2 text-sm">
                                        <input type="checkbox"
                                               name="sucursal_ids[]"
                                               value="{{ $sucursal->id }}"
                                               @checked(in_array($sucursal->id, $selectedSucursales ?? [], true))
                                               class="rounded border-gray-300">
                                        <span>{{ $sucursal->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                La existencia del Excel se aplicara por igual a cada sucursal seleccionada.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Archivo Excel
                            </label>

                            <input name="archivo" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="mt-1 block w-full text-sm">
                        </div>

                        <button type="submit" class="inline-flex rounded bg-slate-800 px-4 py-2 text-white">
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
                                Vista previa
                            </h3>
                            <p class="text-sm text-gray-600">
                                Revisa los productos antes de aplicar la carga inicial en {{ count($selectedSucursales ?? []) }} sucursal(es).
                            </p>
                        </div>

                        <form method="POST" action="{{ route('inventarios.carga-inicial.confirmar') }}">
                            @csrf
                            @foreach (($selectedSucursales ?? []) as $sucursalId)
                                <input type="hidden" name="sucursal_ids[]" value="{{ $sucursalId }}">
                            @endforeach
                            <input type="hidden" name="preview_token" value="{{ $previewToken }}">

                            <button type="submit"
                                    onclick="return confirm('Deseas aplicar esta carga inicial masiva?')"
                                    class="rounded bg-green-600 px-4 py-2 text-white">
                                Confirmar carga
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] border text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 border text-left">Accion</th>
                                    <th class="p-2 border text-left">Codigo</th>
                                    <th class="p-2 border text-left">Producto</th>
                                    <th class="p-2 border text-left">Categoria</th>
                                    <th class="p-2 border text-left">Laboratorio</th>
                                    <th class="p-2 border text-right">Costo</th>
                                    <th class="p-2 border text-right">Precio</th>
                                    <th class="p-2 border text-right">Stock min.</th>
                                    <th class="p-2 border text-center">Vence</th>
                                    <th class="p-2 border text-right">Existencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($previewRows as $row)
                                    <tr>
                                        <td class="p-2 border font-semibold">
                                            {{ $row['accion'] }}
                                        </td>
                                        <td class="p-2 border">
                                            {{ $row['codigo_barra'] }}
                                            @if($row['codigo_generado'])
                                                <span class="text-xs text-indigo-600">(generado)</span>
                                            @endif
                                        </td>
                                        <td class="p-2 border">{{ $row['nombre'] }}</td>
                                        <td class="p-2 border">{{ $row['categoria'] ?: '-' }}</td>
                                        <td class="p-2 border">{{ $row['laboratorio'] ?: '-' }}</td>
                                        <td class="p-2 border text-right">Q {{ number_format($row['costo'], 2) }}</td>
                                        <td class="p-2 border text-right">Q {{ number_format($row['precio_venta'], 2) }}</td>
                                        <td class="p-2 border text-right">{{ $row['stock_minimo'] }}</td>
                                        <td class="p-2 border text-center">
                                            {{ $row['fecha_vencimiento'] ? \Carbon\Carbon::parse($row['fecha_vencimiento'])->format('d/m/Y') : 'Sin fecha' }}
                                        </td>
                                        <td class="p-2 border text-right font-semibold">{{ $row['existencia_inicial'] }}</td>
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
