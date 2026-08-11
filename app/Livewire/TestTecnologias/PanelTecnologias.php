<?php

namespace App\Livewire\TestTecnologias;

use App\Livewire\Forms\TestTecnologias\NotificacionTecnologiaForm;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PanelTecnologias extends Component
{
    use WithPagination;

    public NotificacionTecnologiaForm $form;

    #[Url(except: '')]
    public string $busqueda = '';

    #[Url(except: 'Todas')]
    public string $filtroPrioridad = 'Todas';

    #[Url(except: 4)]
    public int $porPagina = 4;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroPrioridad(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda = '';
        $this->filtroPrioridad = 'Todas';
        $this->resetPage();

        flash()->info('Filtros restablecidos.', [], 'Laboratorio tecnico');
    }

    public function limpiarFormulario(): void
    {
        $this->form->limpiar();
        flash()->info('Formulario de prueba limpiado.', [], 'Livewire Form');
    }

    public function registrarDesdeModal(): void
    {
        $this->form->validate();

        $this->emitirNotificacion(
            $this->form->adaptador,
            "Paciente {$this->form->paciente} validado desde modal.",
            'Formulario modal'
        );

        $this->dispatch('cerrar-overlay-preline', id: 'modal-formulario-tecnologia');
    }

    public function registrarDesdeDrawer(): void
    {
        $this->form->validate();

        $this->emitirNotificacion(
            $this->form->adaptador,
            "Paciente {$this->form->paciente} validado desde drawer.",
            'Formulario drawer'
        );

        $this->dispatch('cerrar-overlay-preline', id: 'drawer-formulario-tecnologia');
    }

    public function emitirNotificacion(string $adaptador, ?string $mensaje = null, ?string $titulo = null): void
    {
        $mensaje ??= match ($adaptador) {
            'sweetalert' => 'SweetAlert2 listo para alertas destacadas.',
            'noty' => 'Noty listo para avisos operativos.',
            'toastr' => 'Toastr listo para mensajes breves.',
            default => 'PHPFlasher base listo para mensajes generales.',
        };

        $titulo ??= 'PHPFlasher';

        match ($adaptador) {
            'sweetalert' => sweetalert()->success($mensaje, [], $titulo),
            'noty' => noty()->warning($mensaje, [], $titulo),
            'toastr' => toastr()->success($mensaje, [], $titulo),
            default => flash()->success($mensaje, [], $titulo),
        };
    }

    public function render()
    {
        return view('livewire.test-tecnologias.panel-tecnologias', [
            'atenciones' => $this->paginarAtenciones(),
            'resumen' => $this->resumenTecnologias(),
            'prioridades' => ['Todas', 'Baja', 'Media', 'Alta', 'Critica'],
        ]);
    }

    private function paginarAtenciones(): LengthAwarePaginator
    {
        $datos = $this->atencionesDemostracion()
            ->filter(function (array $atencion): bool {
                if ($this->filtroPrioridad !== 'Todas' && $atencion['prioridad'] !== $this->filtroPrioridad) {
                    return false;
                }

                if ($this->busqueda === '') {
                    return true;
                }

                $busqueda = Str::lower($this->busqueda);

                return Str::contains(Str::lower($atencion['paciente']), $busqueda)
                    || Str::contains(Str::lower($atencion['documento']), $busqueda)
                    || Str::contains(Str::lower($atencion['servicio']), $busqueda);
            })
            ->values();

        $pagina = $this->getPage();
        $items = $datos
            ->forPage($pagina, $this->porPagina)
            ->values();

        return new LengthAwarePaginator(
            $items,
            $datos->count(),
            $this->porPagina,
            $pagina,
            [
                'path' => route('test.tecnologias'),
                'pageName' => 'page',
                'query' => $this->parametrosPaginacion(),
            ]
        );
    }

    /**
     * Mantiene los filtros demostrativos disponibles en la URL.
     *
     * @return array<string, mixed>
     */
    private function parametrosPaginacion(): array
    {
        return collect([
            'busqueda' => $this->busqueda !== '' ? $this->busqueda : null,
            'filtroPrioridad' => $this->filtroPrioridad !== 'Todas' ? $this->filtroPrioridad : null,
            'porPagina' => $this->porPagina !== 4 ? $this->porPagina : null,
        ])->filter(fn ($valor): bool => $valor !== null)->all();
    }

    private function resumenTecnologias(): array
    {
        return [
            ['nombre' => 'Tailwind CSS 4', 'estado' => 'Compilando', 'detalle' => 'Utilidades, grillas, estados hover y responsive.'],
            ['nombre' => 'Livewire 4', 'estado' => 'Interactivo', 'detalle' => 'Class component, SFC, MFC, Form object y paginacion.'],
            ['nombre' => 'Preline UI', 'estado' => 'Activo', 'detalle' => 'Modal, drawer, tabs, dropdown, tooltip, accordion y paginacion.'],
            ['nombre' => 'PHPFlasher', 'estado' => 'Notificando', 'detalle' => 'Flash, Toastr, Noty y SweetAlert2 disponibles.'],
        ];
    }

    private function atencionesDemostracion(): Collection
    {
        return collect([
            ['paciente' => 'Ana Torres Medina', 'documento' => '45871236', 'prioridad' => 'Critica', 'servicio' => 'Shock trauma', 'estado' => 'Abierta', 'tiempo' => '03 min'],
            ['paciente' => 'Carlos Rojas Paredes', 'documento' => '10245879', 'prioridad' => 'Alta', 'servicio' => 'Topico medicina', 'estado' => 'Abierta', 'tiempo' => '08 min'],
            ['paciente' => 'Maria Salas Vera', 'documento' => '70451289', 'prioridad' => 'Media', 'servicio' => 'Observacion', 'estado' => 'Abierta', 'tiempo' => '15 min'],
            ['paciente' => 'Luis Campos Nina', 'documento' => '33457891', 'prioridad' => 'Baja', 'servicio' => 'Triaje', 'estado' => 'Cerrada', 'tiempo' => '22 min'],
            ['paciente' => 'Rosa Quispe Huaman', 'documento' => '62678941', 'prioridad' => 'Alta', 'servicio' => 'Topico cirugia', 'estado' => 'Abierta', 'tiempo' => '11 min'],
            ['paciente' => 'Jorge Nunez Soto', 'documento' => '80123654', 'prioridad' => 'Media', 'servicio' => 'Observacion', 'estado' => 'Cerrada', 'tiempo' => '34 min'],
            ['paciente' => 'Lucia Vega Ramos', 'documento' => '40789652', 'prioridad' => 'Critica', 'servicio' => 'Shock trauma', 'estado' => 'Abierta', 'tiempo' => '02 min'],
            ['paciente' => 'Pedro Medina Castro', 'documento' => '22114578', 'prioridad' => 'Baja', 'servicio' => 'Triaje', 'estado' => 'Cerrada', 'tiempo' => '41 min'],
            ['paciente' => 'Elena Flores Caceres', 'documento' => '77321456', 'prioridad' => 'Alta', 'servicio' => 'Topico pediatria', 'estado' => 'Abierta', 'tiempo' => '09 min'],
            ['paciente' => 'Miguel Herrera Leon', 'documento' => '61237890', 'prioridad' => 'Media', 'servicio' => 'Observacion', 'estado' => 'Abierta', 'tiempo' => '18 min'],
            ['paciente' => 'Patricia Silva Mora', 'documento' => '53127864', 'prioridad' => 'Baja', 'servicio' => 'Triaje', 'estado' => 'Cerrada', 'tiempo' => '27 min'],
            ['paciente' => 'Victor Poma Rios', 'documento' => '90871234', 'prioridad' => 'Critica', 'servicio' => 'Shock trauma', 'estado' => 'Abierta', 'tiempo' => '01 min'],
        ]);
    }
}
