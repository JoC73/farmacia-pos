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

            @if ($errors->any())
                <div class="rounded bg-red-100 p-4 text-sm text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
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

            <div class="bg-white shadow rounded border-l-4 border-red-600 p-4 sm:p-6">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_420px]">
                    <div>
                        <h3 class="font-bold text-red-700 mb-2">
                            Limpieza segura de productos por sucursal
                        </h3>

                        <p class="text-sm text-gray-600">
                            Esta accion deja una sucursal lista para una nueva carga masiva sin borrar historial.
                            Oculta los productos activos de la sucursal seleccionada y coloca sus existencias en 0,
                            conservando ventas, compras, cajas, cierres y las demas sucursales.
                        </p>

                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[560px] border text-sm">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border p-2 text-left">Sucursal</th>
                                        <th class="border p-2 text-right">Inventarios activos</th>
                                        <th class="border p-2 text-right">Existencia</th>
                                        <th class="border p-2 text-right">Ventas</th>
                                        <th class="border p-2 text-right">Compras</th>
                                        <th class="border p-2 text-right">Cajas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sucursales as $sucursal)
                                        @php($stats = $branchCleanupStats[$sucursal->id] ?? [])
                                        <tr>
                                            <td class="border p-2 font-semibold">{{ $sucursal->nombre }}</td>
                                            <td class="border p-2 text-right">{{ $stats['inventarios'] ?? 0 }}</td>
                                            <td class="border p-2 text-right">{{ $stats['existencia'] ?? 0 }}</td>
                                            <td class="border p-2 text-right">{{ $stats['ventas'] ?? 0 }}</td>
                                            <td class="border p-2 text-right">{{ $stats['compras'] ?? 0 }}</td>
                                            <td class="border p-2 text-right">{{ $stats['cajas'] ?? 0 }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="border p-4 text-center text-gray-500">
                                                No hay sucursales activas.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('premium.branch-cleanup') }}"
                          class="rounded border border-red-200 bg-red-50 p-4"
                          onsubmit="return confirm('Esta accion ocultara productos activos y pondra existencias en 0 solo en la sucursal seleccionada. No borrara ventas, cajas ni historial. ¿Deseas continuar?')">
                        @csrf

                        <div class="mb-4">
                            <label for="cleanup-sucursal" class="mb-1 block text-sm font-semibold text-gray-700">
                                Sucursal a limpiar
                            </label>
                            <select id="cleanup-sucursal"
                                    name="sucursal_id"
                                    class="w-full rounded border-gray-300"
                                    required>
                                <option value="">Selecciona sucursal</option>
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="cleanup-confirmation" class="mb-1 block text-sm font-semibold text-gray-700">
                                Confirmacion
                            </label>
                            <input id="cleanup-confirmation"
                                   type="text"
                                   name="confirmation"
                                   class="w-full rounded border-gray-300"
                                   placeholder="Escribe BORRAR INVENTARIO"
                                   required>
                            <p class="mt-1 text-xs text-gray-600">
                                Para evitar errores, escribe exactamente <strong>BORRAR INVENTARIO</strong>.
                            </p>
                        </div>

                        <div class="mb-4 rounded bg-white p-3 text-sm text-red-700">
                            No se eliminaran ventas, compras, cajas, cierres ni movimientos de caja. Los productos de
                            la sucursal quedaran ocultos operativamente y sus existencias pasaran a 0 con movimiento
                            de inventario de salida cuando corresponda.
                        </div>

                        <button type="submit"
                                class="w-full rounded bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800">
                            Borrar inventario seguro
                        </button>
                    </form>
                </div>
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
