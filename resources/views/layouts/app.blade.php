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

<div class="min-h-screen flex">

    @include('layouts.navigation')

    <div class="flex-1 min-w-0">

        <div class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">

            <div>
                @isset($header)
                    {{ $header }}
                @else
                    <h2 class="font-semibold text-xl text-gray-800">
                        Farmacia POS
                    </h2>
                @endisset
            </div>

            <div class="text-sm text-gray-600 text-right">
                <div class="font-semibold">
                    {{ Auth::user()->name }}
                </div>
                <div>
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