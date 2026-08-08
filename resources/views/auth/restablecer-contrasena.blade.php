@extends('layouts.guest')

@section('content')
    <div class="login-card rounded-md border p-6 backdrop-blur md:p-7">
        <div class="mb-7 text-center">
            <img src="{{ $configuracion->logo_path }}" alt="Logotipo del sistema" class="mx-auto h-20 w-20 object-contain">
            <p class="login-kicker mt-4 text-xs font-semibold uppercase tracking-[0.24em]">{{ $configuracion->nombre_sistema }}</p>
            <h1 class="login-heading mt-2 text-2xl font-semibold">Restablecer contrasena</h1>
            <p class="login-muted mt-2 text-sm leading-6">
                Registre una nueva clave segura para recuperar el acceso.
            </p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="login-heading mb-1.5 block text-sm font-medium">Correo electronico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $email) }}"
                    required
                    autocomplete="username"
                    class="login-field block w-full rounded-md border px-3 py-2.5 text-sm outline-none transition"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="login-heading mb-1.5 block text-sm font-medium">Nueva contrasena</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="login-field block w-full rounded-md border px-3 py-2.5 text-sm outline-none transition"
                >
                @error('password')
                    <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="login-heading mb-1.5 block text-sm font-medium">Confirmar contrasena</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="login-field block w-full rounded-md border px-3 py-2.5 text-sm outline-none transition"
                >
            </div>

            <button type="submit" class="login-submit flex w-full items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[var(--hce-color-highlight)] focus:ring-offset-2">
                Guardar nueva contrasena
            </button>
        </form>
    </div>
@endsection
