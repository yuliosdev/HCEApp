<?php

namespace App\Livewire\HistoriaClinica;

use App\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class AtencionesPacientePanel extends Component
{
    public string $busqueda = '';

    public string $busquedaAplicada = '';

    public function buscar(): void
    {
        $this->busquedaAplicada = trim($this->busqueda);
    }

    public function limpiar(): void
    {
        $this->busqueda = '';
        $this->busquedaAplicada = '';
    }

    public function prepararHistorial(int $pacienteId): void
    {
        $paciente = Paciente::query()->findOrFail($pacienteId);

        $this->redirectRoute('historia-clinica.atenciones-paciente.dashboard', $paciente);
    }

    public function render()
    {
        return view('livewire.historia-clinica.atenciones-paciente-panel', [
            'pacientes' => $this->pacientes(),
        ]);
    }

    /**
     * Busca pacientes por nombre, documento o numero de historia.
     *
     * @return Collection<int, Paciente>
     */
    private function pacientes(): Collection
    {
        if ($this->busquedaAplicada === '') {
            return collect();
        }

        return Paciente::query()
            ->withMax('hcEmergencias as ultima_atencion_fecha', 'fecha_hora_atencion')
            ->where(function (Builder $consulta): void {
                $consulta
                    ->where('nombre', 'like', "%{$this->busquedaAplicada}%")
                    ->orWhere('numero_documento', 'like', "%{$this->busquedaAplicada}%")
                    ->orWhere('numero_historia', 'like', "%{$this->busquedaAplicada}%");
            })
            ->orderBy('nombre')
            ->limit(12)
            ->get();
    }
}
