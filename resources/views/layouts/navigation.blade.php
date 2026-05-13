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

<!-- MENÚ MÓVIL SIN JAVASCRIPT -->
<div class="md:hidden bg-slate-900 text-white w-full">

    <details class="p-4">
        <summary class="cursor-pointer font-bold">
            ☰ Menú
        </summary>

        <nav class="mt-4 space-y-2">

            @can('dashboard.ver')
                <a href="{{ route('dashboard') }}" class="block py-2">Dashboard</a>
            @endcan

            @can('ventas.ver')
                <a href="{{ route('ventas.index') }}" class="block py-2">Ventas</a>
                <a href="{{ route('clientes.index') }}" class="block py-2">Clientes</a>
            @endcan

            @can('productos.ver')
                <a href="{{ route('productos.index') }}" class="block py-2">Productos</a>
                <a href="{{ route('categorias.index') }}" class="block py-2">Categorías</a>
            @endcan

            @can('inventario.ver')
                <a href="{{ route('inventarios.index') }}" class="block py-2">Inventario</a>
            @endcan

            @can('compras.ver')
                <a href="{{ route('compras.index') }}" class="block py-2">Compras</a>
                <a href="{{ route('proveedores.index') }}" class="block py-2">Proveedores</a>
            @endcan

            @can('caja.ver_cierres')
                <a href="{{ route('cajas.index') }}" class="block py-2">Cajas</a>
            @endcan

            @can('sucursales.ver')
                <a href="{{ route('sucursales.index') }}" class="block py-2">Sucursales</a>
            @endcan

            @can('usuarios.ver')
                <a href="{{ route('usuarios.index') }}" class="block py-2">Usuarios</a>
            @endcan

            @can('roles.ver')
                <a href="{{ route('roles.index') }}" class="block py-2">Roles</a>
            @endcan

            @can('reportes.ventas')
                <a href="{{ route('reportes.index') }}" class="block py-2">Reportes</a>
            @endcan

            <hr class="border-slate-700">

            <a href="{{ route('profile.edit') }}" class="block py-2">Perfil</a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block py-2 text-red-300">
                    Cerrar sesión
                </button>
            </form>

        </nav>
    </details>

</div>