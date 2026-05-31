<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Farmacia POS') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100">

<div x-data="{ mobileMenuOpen: false }" class="min-h-screen md:flex">

    @include('layouts.navigation')

    <div class="flex-1 min-w-0 pt-14 md:pt-0">

        <div class="bg-white border-b border-gray-200 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center gap-4">

            <div class="min-w-0 flex-1">
                @isset($header)
                    {{ $header }}
                @else
                    <h2 class="font-semibold text-xl text-gray-800">
                        Farmacia POS
                    </h2>
                @endisset
            </div>

            <div class="text-xs sm:text-sm text-gray-600 text-right shrink-0">
                <div class="font-semibold max-w-28 sm:max-w-none truncate">
                    {{ Auth::user()->name }}
                </div>
                <div class="max-w-28 sm:max-w-none truncate">
                    {{ Auth::user()->sucursal->nombre ?? 'Sin sucursal' }}
                </div>
            </div>

        </div>

        <main>
            {{ $slot }}
        </main>

    </div>

</div>

</body>
</html>
