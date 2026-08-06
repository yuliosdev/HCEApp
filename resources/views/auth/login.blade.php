@extends('layouts.guest')

@section('content')
    <div class="w-full max-w-md rounded-md border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-md bg-[#0f2d2e] text-sm font-bold text-white lg:hidden">
                HC
            </div>
            <p class="text-sm font-medium text-[#0f2d2e]">Acceso autorizado</p>
            <h1 class="mt-2 text-2xl font-semibold text-zinc-950">Iniciar sesion</h1>
        </div>

        @if (session('estado'))
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('estado') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.guardar') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-800">Correo electronico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="block w-full rounded-md border border-zinc-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#0f766e] focus:ring-2 focus:ring-teal-100"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-800">Contrasena</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="block w-full rounded-md border border-zinc-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#0f766e] focus:ring-2 focus:ring-teal-100"
                >
                @error('password')
                    <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between gap-4">
                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input name="recordarme" type="checkbox" value="1" class="h-4 w-4 rounded border-zinc-300 text-[#0f766e] focus:ring-[#0f766e]">
                    Recordarme
                </label>
            </div>

            <button type="submit" class="flex w-full items-center justify-center rounded-md bg-[#0f2d2e] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#123f40] focus:outline-none focus:ring-2 focus:ring-[#0f766e] focus:ring-offset-2">
                Ingresar
            </button>
        </form>

        <p class="mt-5 text-sm text-zinc-500">
            Usuario inicial: <span class="font-medium text-zinc-700">admin@hceapp.test</span>
        </p>
    </div>
@endsection
