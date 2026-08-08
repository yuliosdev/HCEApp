@extends('layouts.guest')

@section('content')
    <div class="login-card rounded-md border p-6 backdrop-blur md:p-7">
        <div class="mb-7 text-center">
            <img src="{{ $configuracion->logo_path }}" alt="Logotipo del sistema" class="mx-auto h-20 w-20 object-contain">
            <p class="login-kicker mt-4 text-xs font-semibold uppercase tracking-[0.24em]">{{ $configuracion->nombre_sistema }}</p>
            <h1 class="login-heading mt-2 text-2xl font-semibold">Recuperar contrasena</h1>
            <p class="login-muted mt-2 text-sm leading-6">
                Ingrese su correo institucional para recibir un enlace de restablecimiento.
            </p>
        </div>

        @if (session('estado'))
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('estado') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="login-heading mb-1.5 block text-sm font-medium">Correo electronico</label>
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

            <button type="submit" class="login-submit flex w-full items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--hce-color-highlight)] focus:ring-offset-2">
                Enviar enlace de recuperacion
            </button>
        </form>

        <div class="mt-5 text-center">
            <a href="{{ route('login') }}" class="login-link text-sm font-medium">
                Volver al inicio de sesion
            </a>
        </div>
    </div>
@endsection
