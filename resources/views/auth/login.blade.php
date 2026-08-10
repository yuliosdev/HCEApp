@extends('layouts.guest')

@section('content')
    <div class="login-card rounded-md border p-6 backdrop-blur md:p-7">
        <div class="mb-7 text-center">
            <img src="{{ $configuracion->logo_path }}" alt="Logotipo del sistema" class="mx-auto h-20 w-20 object-contain">
            <p class="login-kicker mt-4 text-xs font-semibold uppercase tracking-[0.24em]">{{ $configuracion->nombre_sistema }}</p>
            <h1 class="login-heading mt-2 text-2xl font-semibold">Ingreso al sistema</h1>
            <p class="login-muted mt-2 text-sm leading-6">{{ $configuracion->subtitulo_login }}</p>
        </div>

        @if (session('estado'))
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('estado') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.guardar') }}" class="space-y-5" data-login-transition-form>
            @csrf

            <div>
                <label for="email" class="login-heading mb-1.5 block text-sm font-medium">Correo electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="login-field block w-full rounded-md border px-3 py-2.5 text-sm outline-none transition"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="login-heading mb-1.5 block text-sm font-medium">Contraseña</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="login-field block w-full rounded-md border px-3 py-2.5 text-sm outline-none transition"
                >
                @error('password')
                    <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                @enderror
                <a href="{{ route('password.request') }}" class="login-link mt-2 inline-flex text-sm font-medium">
                    Recupere su clave
                </a>
            </div>

            <div class="flex items-center justify-between gap-4">
                <label class="login-muted flex items-center gap-2 text-sm">
                    <input name="recordarme" type="checkbox" value="1" class="login-checkbox h-4 w-4 rounded border-[var(--hce-login-field-border)] focus:ring-[var(--hce-color-highlight)]">
                    Recordarme
                </label>
                <span class="login-muted text-xs">Bloqueo temporal al quinto intento fallido.</span>
            </div>

            <button type="submit" data-loading-text="Validando acceso..." class="login-submit flex w-full items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--hce-color-highlight)] focus:ring-offset-2">
                Ingresar de forma segura
            </button>
        </form>
    </div>
@endsection
