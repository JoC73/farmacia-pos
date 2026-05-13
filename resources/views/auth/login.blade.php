<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso | Farmacia Evelyn</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-sky-50">

    <div class="min-h-screen flex items-center justify-center px-6 py-8">

        <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 bg-white rounded-3xl shadow-2xl overflow-hidden">

            <!-- PANEL INFORMATIVO -->
            <div class="hidden lg:flex flex-col justify-between p-12 bg-emerald-900 text-white relative overflow-hidden">

                <div class="absolute -top-20 -right-20 w-72 h-72 bg-emerald-700 rounded-full opacity-30"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-sky-700 rounded-full opacity-20"></div>

                <div class="relative z-10">

                    <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl mb-8">
                        +
                    </div>

                    <h1 class="text-4xl font-bold leading-tight">
                        Farmacia Evelyn
                    </h1>

                    <p class="mt-4 text-emerald-100 text-lg">
                        Sistema de control farmacéutico
                    </p>

                </div>

                <div class="relative z-10 grid grid-cols-2 gap-4 text-sm">

                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="font-bold mb-1">Ventas</div>
                        <div class="text-emerald-100">Control rápido de productos.</div>
                    </div>

                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="font-bold mb-1">Inventario</div>
                        <div class="text-emerald-100">Stock por sucursal.</div>
                    </div>

                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="font-bold mb-1">Caja</div>
                        <div class="text-emerald-100">Apertura y cierre diario.</div>
                    </div>

                    <div class="bg-white/10 rounded-2xl p-4">
                        <div class="font-bold mb-1">Reportes</div>
                        <div class="text-emerald-100">Información para decisión.</div>
                    </div>

                </div>

                <div class="relative z-10 text-sm text-emerald-100">
                    By <span class="font-bold text-white">@J_Systems</span>
                </div>

            </div>

            <!-- LOGIN -->
            <div class="flex items-center justify-center p-8 md:p-14">

                <div class="w-full max-w-md">

                    <div class="mb-8">

                        <div class="lg:hidden mb-6 text-center">
                            <div class="text-3xl font-bold text-emerald-900">
                                Farmacia Evelyn
                            </div>
                            <div class="text-sm text-gray-500">
                                Sistema de control farmacéutico
                            </div>
                        </div>

                        <h2 class="text-3xl font-bold text-gray-900">
                            Bienvenido
                        </h2>

                        <p class="text-gray-500 mt-2">
                            Inicia sesión.
                        </p>

                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div>
                            <x-input-label for="email" value="Correo electrónico" />

                            <x-text-input id="email"
                                          class="block mt-2 w-full h-12"
                                          type="email"
                                          name="email"
                                          :value="old('email')"
                                          required
                                          autofocus
                                          autocomplete="username" />

                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="mt-5">
                            <x-input-label for="password" value="Contraseña" />

                            <x-text-input id="password"
                                          class="block mt-2 w-full h-12"
                                          type="password"
                                          name="password"
                                          required
                                          autocomplete="current-password" />

                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="mt-5 flex items-center justify-between">

                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me"
                                       type="checkbox"
                                       class="rounded border-gray-300 text-emerald-700 shadow-sm focus:ring-emerald-500"
                                       name="remember">

                                <span class="ms-2 text-sm text-gray-600">
                                    Recordarme
                                </span>
                            </label>

                            @if (Route::has('password.request'))
                                <a class="text-sm text-emerald-700 hover:text-emerald-900 font-medium"
                                   href="{{ route('password.request') }}">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif

                        </div>

                        <button type="submit"
                                class="mt-8 w-full h-12 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl transition shadow">
                            Entrar al sistema
                        </button>

                    </form>

                    <div class="mt-8 text-center text-xs text-gray-400">
                        Divide y Vencerás.
                    </div>

                </div>

            </div>

        </div>

    </div>

</body>
</html>