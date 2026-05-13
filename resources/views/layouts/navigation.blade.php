<!-- SIDEBAR DESKTOP -->
<aside class="hidden md:flex md:flex-col w-64 min-h-screen bg-slate-900 text-white">

    <div class="p-6 border-b border-slate-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="block h-10 w-auto fill-current text-white" />

            <div>
                <div class="font-bold text-lg">
                    Farmacia POS
                </div>
                <div class="text-xs text-slate-400">
                    Sistema de Gestión
                </div>
            </div>
        </a>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">

        @can('dashboard.ver')
            <a href="{{ route('dashboard') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Dashboard
            </a>
        @endcan

        @can('ventas.ver')
            <a href="{{ route('ventas.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('ventas.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Ventas
            </a>
        @endcan

        @can('ventas.ver')
            <a href="{{ route('clientes.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('clientes.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Clientes
            </a>
        @endcan

        @can('productos.ver')
            <a href="{{ route('productos.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('productos.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Productos
            </a>
        @endcan

        @can('productos.ver')
            <a href="{{ route('categorias.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('categorias.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Categorías
            </a>
        @endcan

        @can('inventario.ver')
            <a href="{{ route('inventarios.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('inventarios.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Inventario
            </a>
        @endcan

        @can('compras.ver')
            <a href="{{ route('compras.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('compras.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Compras
            </a>
        @endcan

        @can('compras.ver')
            <a href="{{ route('proveedores.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('proveedores.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Proveedores
            </a>
        @endcan

        @can('caja.ver_cierres')
            <a href="{{ route('cajas.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('cajas.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Cajas
            </a>
        @endcan

        @can('sucursales.ver')
            <a href="{{ route('sucursales.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('sucursales.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Sucursales
            </a>
        @endcan

        @can('usuarios.ver')
            <a href="{{ route('usuarios.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('usuarios.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Usuarios
            </a>
        @endcan

        @can('roles.ver')
            <a href="{{ route('roles.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('roles.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Roles
            </a>
        @endcan

        @can('reportes.ventas')
            <a href="{{ route('reportes.index') }}"
               class="block px-4 py-3 rounded-lg {{ request()->routeIs('reportes.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Reportes
            </a>
        @endcan

    </nav>

    <div class="p-4 border-t border-slate-700">
        <div class="text-sm font-semibold">
            {{ Auth::user()->name }}
        </div>

        <div class="text-xs text-slate-400">
            {{ Auth::user()->sucursal->nombre ?? 'Sin sucursal' }}
        </div>

        <div class="text-xs text-slate-400 mb-3">
            Rol: {{ Auth::user()->roles->first()->name ?? 'Sin rol' }}
        </div>

        <a href="{{ route('profile.edit') }}"
           class="block text-sm text-slate-300 hover:text-white mb-2">
            Perfil
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit"
                    class="text-sm text-red-300 hover:text-red-100">
                Cerrar sesión
            </button>
        </form>
    </div>

</aside>

<!-- SIDEBAR MÓVIL -->
<div class="md:hidden">
    <div class="fixed inset-x-0 top-0 z-40 flex h-14 items-center justify-between bg-slate-900 px-4 text-white shadow">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2">
            <x-application-logo class="block h-8 w-auto shrink-0 fill-current text-white" />
            <span class="truncate text-sm font-bold">
                Farmacia POS
            </span>
        </a>

        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-md text-slate-200 hover:bg-slate-800 hover:text-white"
            aria-label="Abrir menú"
            x-on:click="mobileMenuOpen = true"
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div
        x-cloak
        x-show="mobileMenuOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-slate-950/60"
        x-on:click="mobileMenuOpen = false"
    ></div>

    <aside
        x-cloak
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 flex w-80 max-w-[86vw] flex-col bg-slate-900 text-white shadow-xl"
    >
        <div class="flex items-center justify-between border-b border-slate-700 p-4">
            <div>
                <div class="font-bold">
                    Farmacia POS
                </div>
                <div class="text-xs text-slate-400">
                    Sistema de Gestión
                </div>
            </div>

            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-md text-slate-300 hover:bg-slate-800 hover:text-white"
                aria-label="Cerrar menú"
                x-on:click="mobileMenuOpen = false"
            >
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">

            @can('dashboard.ver')
                <a href="{{ route('dashboard') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Dashboard
                </a>
            @endcan

            @can('ventas.ver')
                <a href="{{ route('ventas.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('ventas.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Ventas
                </a>
                <a href="{{ route('clientes.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('clientes.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Clientes
                </a>
            @endcan

            @can('productos.ver')
                <a href="{{ route('productos.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('productos.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Productos
                </a>
                <a href="{{ route('categorias.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('categorias.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Categorías
                </a>
            @endcan

            @can('inventario.ver')
                <a href="{{ route('inventarios.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('inventarios.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Inventario
                </a>
            @endcan

            @can('compras.ver')
                <a href="{{ route('compras.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('compras.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Compras
                </a>
                <a href="{{ route('proveedores.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('proveedores.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Proveedores
                </a>
            @endcan

            @can('caja.ver_cierres')
                <a href="{{ route('cajas.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('cajas.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Cajas
                </a>
            @endcan

            @can('sucursales.ver')
                <a href="{{ route('sucursales.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('sucursales.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Sucursales
                </a>
            @endcan

            @can('usuarios.ver')
                <a href="{{ route('usuarios.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('usuarios.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Usuarios
                </a>
            @endcan

            @can('roles.ver')
                <a href="{{ route('roles.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('roles.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Roles
                </a>
            @endcan

            @can('reportes.ventas')
                <a href="{{ route('reportes.index') }}"
                   class="block rounded-lg px-4 py-3 {{ request()->routeIs('reportes.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Reportes
                </a>
            @endcan
        </nav>

        <div class="border-t border-slate-700 p-4">
            <div class="mb-3">
                <div class="text-sm font-semibold">
                    {{ Auth::user()->name }}
                </div>
                <div class="text-xs text-slate-400">
                    {{ Auth::user()->roles->first()->name ?? 'Sin rol' }}
                </div>
            </div>

            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-4 py-3 text-sm text-slate-300 hover:bg-slate-800 hover:text-white">
                Perfil
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full rounded-lg px-4 py-3 text-left text-sm text-red-300 hover:bg-slate-800 hover:text-red-100">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>
</div>
