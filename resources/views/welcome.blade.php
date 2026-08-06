<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'HCEApp') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-950 antialiased">
        <main class="flex min-h-screen items-center justify-center px-6">
            <div class="max-w-md rounded-md border border-zinc-200 bg-white p-6 text-center shadow-sm">
                <p class="text-sm font-medium text-[#0f766e]">{{ config('app.name', 'HCEApp') }}</p>
                <h1 class="mt-2 text-2xl font-semibold">Historia clinica de emergencias</h1>
                <a href="{{ route('inicio') }}" class="mt-5 inline-flex rounded-md bg-[#0f2d2e] px-4 py-2 text-sm font-semibold text-white">
                    Continuar
                </a>
            </div>
        </main>
    </body>
</html>
