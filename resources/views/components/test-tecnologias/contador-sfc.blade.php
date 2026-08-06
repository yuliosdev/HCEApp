<?php

use Livewire\Component;

new class extends Component
{
    public int $contador = 0;

    public function incrementar(): void
    {
        $this->contador++;

        toastr()->info("Contador SFC en {$this->contador}.", [], 'Livewire SFC');
    }

    public function reiniciar(): void
    {
        $this->contador = 0;

        flash()->info('Contador reiniciado.', [], 'Livewire SFC');
    }
};
?>

<article class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-zinc-950">Componente SFC</h3>
            <p class="mt-1 text-sm text-zinc-600">PHP y Blade viven en un solo archivo.</p>
        </div>
        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-zinc-700">SFC</span>
    </div>

    <div class="mt-4 flex items-center justify-between rounded-md bg-white p-3">
        <span class="text-sm text-zinc-600">Clicks registrados</span>
        <strong class="text-2xl font-semibold text-[#0f2d2e]">{{ $contador }}</strong>
    </div>

    <div class="mt-4 flex gap-2">
        <button type="button" wire:click="incrementar" class="rounded-md bg-[#0f2d2e] px-3 py-2 text-sm font-semibold text-white hover:bg-[#143b3c]">
            Incrementar
        </button>
        <button type="button" wire:click="reiniciar" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
            Reiniciar
        </button>
    </div>
</article>
