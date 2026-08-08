@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section>
            <p class="text-sm font-medium text-teal-700">Configuracion</p>
            <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Identidad visual del login</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-600">
                Desde aqui se ajustan los textos, logotipo e imagen de fondo que se presentan en el acceso al sistema.
            </p>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <form method="POST" action="{{ route('configuracion.login.actualizar') }}" enctype="multipart/form-data" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-zinc-800">Nombre del sistema</span>
                        <input name="nombre_sistema" type="text" value="{{ old('nombre_sistema', $configuracion->nombre_sistema) }}" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                        @error('nombre_sistema') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-zinc-800">Subtitulo</span>
                        <input name="subtitulo_login" type="text" value="{{ old('subtitulo_login', $configuracion->subtitulo_login) }}" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                        @error('subtitulo_login') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1.5 block text-sm font-medium text-zinc-800">Titulo principal del login</span>
                        <input name="titulo_login" type="text" value="{{ old('titulo_login', $configuracion->titulo_login) }}" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                        @error('titulo_login') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1.5 block text-sm font-medium text-zinc-800">Texto institucional</span>
                        <textarea name="texto_institucional_login" rows="3" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">{{ old('texto_institucional_login', $configuracion->texto_institucional_login) }}</textarea>
                        @error('texto_institucional_login') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-zinc-800">Logotipo</span>
                        <input name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full rounded-md border border-zinc-300 bg-white text-sm text-zinc-700 file:me-4 file:border-0 file:bg-zinc-100 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-zinc-700">
                        @error('logo') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-zinc-800">Fondo del login</span>
                        <input name="login_background" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full rounded-md border border-zinc-300 bg-white text-sm text-zinc-700 file:me-4 file:border-0 file:bg-zinc-100 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-zinc-700">
                        @error('login_background') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-zinc-200 pt-5">
                    <a href="{{ route('login') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                        Ver login
                    </a>
                    <button type="submit" class="rounded-md bg-[#0f2d2e] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#143b3c]">
                        Guardar configuracion
                    </button>
                </div>
            </form>

            <aside class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-950">Vista previa actual</h2>
                <div class="mt-4 overflow-hidden rounded-md border border-zinc-200 bg-zinc-950">
                    <div class="min-h-72 bg-cover bg-center p-4" style="background-image: linear-gradient(130deg, rgba(4, 13, 18, 0.9), rgba(4, 13, 18, 0.48)), url('{{ $configuracion->login_background_path }}');">
                        <div class="rounded-md bg-white/95 p-4 shadow-xl">
                            <img src="{{ $configuracion->logo_path }}" alt="Logotipo actual" class="h-16 w-16 object-contain">
                            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-teal-700">{{ $configuracion->nombre_sistema }}</p>
                            <h3 class="mt-2 text-lg font-semibold text-zinc-950">{{ $configuracion->titulo_login }}</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $configuracion->subtitulo_login }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
@endsection
