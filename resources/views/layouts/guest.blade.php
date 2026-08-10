<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $configuracion->nombre_sistema ?? config('app.name', 'HCEApp') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|montserrat:600,700,800|poppins:500,600,700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[var(--hce-color-primary)] text-[var(--hce-login-text)] antialiased">
        <main
            data-login-shell
            class="login-shell relative flex min-h-screen items-center justify-center overflow-hidden bg-cover bg-center px-5 py-8 sm:px-8"
            style="--login-bg-image: url('{{ $configuracion->login_background_path ?? asset('images/login/login-background-default.png') }}');"
        >
            <div class="login-scanline pointer-events-none absolute inset-0"></div>
            <div class="login-grid pointer-events-none absolute inset-0"></div>

            <section class="relative z-10 grid w-full max-w-6xl items-center gap-10 lg:grid-cols-[minmax(0,1fr)_430px]">
                <div class="login-copy hidden max-w-2xl text-white lg:block">
                    <div class="mb-8 flex items-center gap-4">
                        <img src="{{ $configuracion->logo_path ?? asset('images/login/logo-default.png') }}" alt="Logotipo del sistema" class="h-16 w-16 object-contain">
                        <div>
                            <p class="login-brand-name text-sm font-semibold tracking-[0.24em]">{{ $configuracion->nombre_sistema ?? 'HCEApp' }}</p>
                            <p class="login-brand-subtitle mt-1 text-sm">{{ $configuracion->subtitulo_login ?? 'Historia clinica electronica de emergencias' }}</p>
                        </div>
                    </div>

                    <h1 class="max-w-3xl text-4xl font-semibold leading-tight text-white xl:text-5xl">
                        {{ $configuracion->titulo_login ?? 'Acceso seguro a HCEApp' }}
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-zinc-200">
                        {{ $configuracion->texto_institucional_login ?? 'Plataforma clinica para atencion de emergencias con trazabilidad, seguridad y control institucional.' }}
                    </p>
                </div>

                <section data-login-panel class="login-panel w-full">
                    @yield('content')
                </section>
            </section>
        </main>

        @livewireScripts
    </body>
</html>
