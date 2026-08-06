<?php

use Livewire\Component;

new class extends Component
{
    public string $modo = 'Operativo';

    public array $modos = ['Operativo', 'Clinico', 'Auditoria'];

    public function seleccionar(string $modo): void
    {
        if (! in_array($modo, $this->modos, true)) {
            return;
        }

        $this->modo = $modo;

        noty()->info("Modo {$modo} seleccionado.", [], 'Livewire MFC');
    }
};
