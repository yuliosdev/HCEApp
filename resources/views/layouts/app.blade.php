<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HCEApp') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-950 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-zinc-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-[#0f2d2e] text-sm font-bold text-white">
                            HC
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ config('app.name', 'HCEApp') }}</span>
                            <span class="block truncate text-xs text-zinc-500">Emergencias</span>
                        </span>
                    </a>

                    <nav class="hidden items-center gap-1 md:flex" aria-label="Navegacion principal">
                        <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 text-sm font-medium text-[#0f2d2e] hover:bg-teal-50">
                            Panel
                        </a>
                    </nav>

                    <div class="flex items-center gap-3">
                        <span class="hidden max-w-48 truncate text-sm text-zinc-600 sm:block">
                            {{ auth()->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                                Salir
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </body>
</html>
