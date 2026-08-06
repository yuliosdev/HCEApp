@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-[#0f766e]">Panel operativo</p>
            <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Historias clinicas de emergencia</h1>
        </div>
        <a href="#" class="inline-flex items-center justify-center rounded-md bg-[#b42318] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#912018]">
            Nueva atencion
        </a>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-md border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Atenciones abiertas</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ $episodiosAbiertos }}</p>
        </article>
        <article class="rounded-md border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Atenciones cerradas</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ $episodiosCerrados }}</p>
        </article>
        <article class="rounded-md border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Pacientes registrados</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ $pacientesRegistrados }}</p>
        </article>
        <article class="rounded-md border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Profesionales de salud</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ $profesionalesActivos }}</p>
        </article>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="rounded-md border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="text-base font-semibold text-zinc-950">Bandeja de emergencia</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">Paciente</th>
                            <th class="px-5 py-3">Prioridad</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Ingreso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-zinc-500">
                                Aun no hay atenciones registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="rounded-md border border-zinc-200 bg-white p-5">
            <h2 class="text-base font-semibold text-zinc-950">Prioridades</h2>
            <div class="mt-4 space-y-3">
                @foreach ($prioridades as $prioridad)
                    @php
                        $colorPrioridad = match ($prioridad->color?->value) {
                            'Rojo' => '#dc2626',
                            'Naranja' => '#ea580c',
                            'Amarillo' => '#ca8a04',
                            'Verde' => '#16a34a',
                            default => '#71717a',
                        };
                    @endphp
                    <div class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 px-3 py-2">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $colorPrioridad }}"></span>
                            <span class="truncate text-sm font-medium text-zinc-800">{{ $prioridad->prioridad }}</span>
                        </div>
                        <span class="text-xs text-zinc-500">Orden {{ $prioridad->orden }}</span>
                    </div>
                @endforeach
            </div>
        </aside>
    </section>
@endsection
