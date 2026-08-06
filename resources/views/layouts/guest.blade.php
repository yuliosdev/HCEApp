<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HCEApp') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-950 antialiased">
        <main class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_480px]">
            <section class="hidden bg-[#0f2d2e] px-10 py-8 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-md bg-white text-sm font-bold text-[#0f2d2e]">
                        HC
                    </div>
                    <div>
                        <p class="text-sm font-semibold">{{ config('app.name', 'HCEApp') }}</p>
                        <p class="text-xs text-teal-100">Historia clinica de emergencias</p>
                    </div>
                </div>

                <div class="max-w-xl">
                    <p class="mb-4 text-sm font-medium text-teal-100">Ingreso seguro</p>
                    <h1 class="text-4xl font-semibold leading-tight">Gestion clinica de emergencia desde una base confiable.</h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-teal-50">
                        Acceso para personal autorizado, con trazabilidad por usuario y datos preparados para el flujo de atencion.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-md border border-white/15 bg-white/10 p-4">
                        <p class="text-2xl font-semibold">2</p>
                        <p class="mt-1 text-teal-50">Estados de atencion</p>
                    </div>
                    <div class="rounded-md border border-white/15 bg-white/10 p-4">
                        <p class="text-2xl font-semibold">4</p>
                        <p class="mt-1 text-teal-50">Prioridades base</p>
                    </div>
                    <div class="rounded-md border border-white/15 bg-white/10 p-4">
                        <p class="text-2xl font-semibold">8</p>
                        <p class="mt-1 text-teal-50">Signos vitales</p>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8">
                @yield('content')
            </section>
        </main>

        @livewireScripts
    </body>
</html>
