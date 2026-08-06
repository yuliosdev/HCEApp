<?php

namespace App\Livewire\Forms\TestTecnologias;

use Livewire\Attributes\Validate;
use Livewire\Form;

class NotificacionTecnologiaForm extends Form
{
    #[Validate('required|string|min:3|max:80')]
    public string $paciente = 'Paciente demostracion';

    #[Validate('required|string|min:8|max:20')]
    public string $documento = '12345678';

    #[Validate('required|string|in:Baja,Media,Alta,Critica')]
    public string $prioridad = 'Alta';

    #[Validate('required|string|in:flash,toastr,noty,sweetalert')]
    public string $adaptador = 'toastr';

    #[Validate('nullable|string|max:180')]
    public string $observacion = 'Prueba funcional de formulario Livewire con modal Preline.';

    public function limpiar(): void
    {
        $this->paciente = '';
        $this->documento = '';
        $this->prioridad = 'Media';
        $this->adaptador = 'toastr';
        $this->observacion = '';
    }
}
