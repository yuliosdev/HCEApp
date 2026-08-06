<article class="selector-mfc rounded-md border border-zinc-200 bg-zinc-50 p-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-zinc-950">Componente MFC</h3>
            <p class="mt-1 text-sm text-zinc-600">PHP, Blade, CSS y JS viven separados.</p>
        </div>
        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-zinc-700">MFC</span>
    </div>

    <div class="mt-4 grid grid-cols-3 rounded-md border border-zinc-200 bg-white p-1">
        @foreach ($modos as $opcion)
            <button type="button" wire:click="seleccionar('{{ $opcion }}')" class="rounded px-2 py-2 text-sm font-medium {{ $modo === $opcion ? 'bg-[#0f2d2e] text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                {{ $opcion }}
            </button>
        @endforeach
    </div>

    <div class="selector-mfc__estado mt-4 rounded-md bg-white p-3 text-sm text-zinc-700">
        Modo activo: <strong class="text-[#0f2d2e]">{{ $modo }}</strong>
    </div>
</article>
