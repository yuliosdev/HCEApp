<?php

namespace App\Livewire\HistoriaClinica;

use App\Enums\ColorTriaje;
use App\Enums\EstadoAtencion;
use App\Enums\SiNo;
use App\Enums\TipoDiagnostico;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use App\Models\Paciente;
use App\Models\ProfesionalSalud;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class HistoriasAbiertasPanel extends Component
{
    use WithPagination;

    #[Url(as: 'desde')]
    public string $fechaDesde = '';

    #[Url(as: 'hasta')]
    public string $fechaHasta = '';

    #[Url(except: EstadoAtencion::Abierta->value)]
    public string $estado = EstadoAtencion::Abierta->value;

    #[Url(except: 5)]
    public int $porPagina = 5;

    public bool $modalNuevaHistoriaAbierto = false;

    /** @var array<string, mixed> */
    public array $nuevaHistoriaForm = [
        'profesional_salud_id' => null,
        'paciente_id' => null,
        'alias' => '',
    ];

    public string $profesionalBusqueda = '';

    public string $pacienteBusqueda = '';

    public function mount(): void
    {
        $hoy = now()->toDateString();

        $this->fechaDesde = $this->fechaDesde ?: $hoy;
        $this->fechaHasta = $this->fechaHasta ?: $hoy;
        $this->estado = $this->estado ?: EstadoAtencion::Abierta->value;
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function updatingPorPagina(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $hoy = now()->toDateString();

        $this->fechaDesde = $hoy;
        $this->fechaHasta = $hoy;
        $this->estado = EstadoAtencion::Abierta->value;
        $this->porPagina = 5;
        $this->resetPage();
    }

    public function crearNuevaHistoria(): void
    {
        $this->resetValidation();
        $this->resetearFormularioNuevaHistoria();
        $this->modalNuevaHistoriaAbierto = true;
    }

    public function cerrarModalNuevaHistoria(): void
    {
        $this->modalNuevaHistoriaAbierto = false;
        $this->resetValidation();
    }

    public function updatedProfesionalBusqueda(): void
    {
        $this->nuevaHistoriaForm['profesional_salud_id'] = null;
    }

    public function updatedPacienteBusqueda(): void
    {
        $this->nuevaHistoriaForm['paciente_id'] = null;
    }

    public function seleccionarProfesional(int $profesionalId): void
    {
        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->findOrFail($profesionalId);

        $this->nuevaHistoriaForm['profesional_salud_id'] = $profesional->id;
        $this->profesionalBusqueda = $this->etiquetaProfesional($profesional);
        $this->resetValidation('nuevaHistoriaForm.profesional_salud_id');
    }

    public function seleccionarPaciente(int $pacienteId): void
    {
        $paciente = Paciente::query()->findOrFail($pacienteId);

        $this->nuevaHistoriaForm['paciente_id'] = $paciente->id;
        $this->pacienteBusqueda = $this->etiquetaPaciente($paciente);
        $this->resetValidation('nuevaHistoriaForm.paciente_id');
        $this->resetValidation('nuevaHistoriaForm.alias');
    }

    public function crearHistoriaDesdeModal(): void
    {
        $this->nuevaHistoriaForm['alias'] = Str::of((string) $this->nuevaHistoriaForm['alias'])->trim()->toString();

        $datos = $this->validate(
            [
                'nuevaHistoriaForm.profesional_salud_id' => ['required', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
                'nuevaHistoriaForm.paciente_id' => ['nullable', 'integer', Rule::exists('pacientes', 'id')->whereNull('deleted_at')],
                'nuevaHistoriaForm.alias' => ['nullable', 'string', 'max:100', 'required_without:nuevaHistoriaForm.paciente_id'],
            ],
            [
                'nuevaHistoriaForm.alias.required_without' => 'Debe seleccionar un paciente o ingresar un alias.',
            ],
            [
                'nuevaHistoriaForm.profesional_salud_id' => 'profesional',
                'nuevaHistoriaForm.paciente_id' => 'paciente',
                'nuevaHistoriaForm.alias' => 'alias',
            ]
        )['nuevaHistoriaForm'];

        if (blank($datos['paciente_id']) && blank($datos['alias'])) {
            $this->addError('nuevaHistoriaForm.alias', 'Debe seleccionar un paciente o ingresar un alias.');

            return;
        }

        $atencion = HcEmergencia::query()->create([
            'paciente_id' => $datos['paciente_id'] ?: null,
            'alias' => $datos['alias'] ?: null,
            'fecha_hora_inicio_episodio' => now(),
            'fecha_hora_atencion' => now(),
            'profesional_salud_id' => $datos['profesional_salud_id'],
            'estado_atencion' => EstadoAtencion::Abierta->value,
            'sin_identificacion' => blank($datos['paciente_id']) ? SiNo::Si->value : SiNo::No->value,
            'registrado_por' => Auth::id(),
            'actualizado_por' => Auth::id(),
        ]);

        $this->modalNuevaHistoriaAbierto = false;

        if ($atencion->paciente_id !== null) {
            $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.editar', [
                'paciente' => $atencion->paciente_id,
                'atencion' => $atencion,
            ]);

            return;
        }

        $this->redirectRoute('historia-clinica.emergencia.editar', $atencion);
    }

    public function verAtencion(int $atencionId): void
    {
        $atencion = $this->buscarAtencion($atencionId);

        if ($atencion->paciente === null) {
            $this->redirectRoute('historia-clinica.emergencia.ver', $atencion);

            return;
        }

        $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.ver', [
            'paciente' => $atencion->paciente,
            'atencion' => $atencion,
        ]);
    }

    public function editarAtencion(int $atencionId): void
    {
        $atencion = $this->buscarAtencion($atencionId);

        if (! $this->estadoAbierto($atencion)) {
            $this->verAtencion($atencionId);

            return;
        }

        if ($atencion->paciente === null) {
            $this->redirectRoute('historia-clinica.emergencia.editar', $atencion);

            return;
        }

        $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.editar', [
            'paciente' => $atencion->paciente,
            'atencion' => $atencion,
        ]);
    }

    public function render()
    {
        $atenciones = $this->consultaAtenciones()
            ->with([
                'paciente',
                'profesionalResponsable',
                'prioridad',
                'diagnosticosIngreso.diagnosticoCie10',
            ])
            ->orderByDesc('fecha_hora_atencion')
            ->orderByDesc('id')
            ->paginate($this->porPagina)
            ->withPath(route('historia-clinica.historias-abiertas.index'));

        return view('livewire.historia-clinica.historias-abiertas-panel', [
            'atenciones' => $atenciones,
            'estadisticas' => $this->estadisticas(),
            'opcionesEstado' => $this->opcionesEstado(),
            'opcionesPorPagina' => [5, 10, 15, 20, 25, 50],
            'profesionalesNuevaHistoria' => $this->profesionalesNuevaHistoria(),
            'pacientesNuevaHistoria' => $this->pacientesNuevaHistoria(),
        ]);
    }

    public function diagnosticoPrincipal(HcEmergencia $atencion): ?IngresoDiagnostico
    {
        $diagnosticos = $atencion->diagnosticosIngreso;

        return $diagnosticos
            ->filter(fn (IngresoDiagnostico $diagnostico): bool => $diagnostico->tipo_diagnostico === TipoDiagnostico::Definitivo)
            ->sortByDesc(fn (IngresoDiagnostico $diagnostico): int => $diagnostico->fecha_hora?->timestamp ?? 0)
            ->first()
            ?: $diagnosticos
                ->sortByDesc(fn (IngresoDiagnostico $diagnostico): int => $diagnostico->fecha_hora?->timestamp ?? 0)
                ->first();
    }

    public function textoDiagnostico(?IngresoDiagnostico $diagnostico): string
    {
        if ($diagnostico === null || $diagnostico->diagnosticoCie10 === null) {
            return 'En evaluacion...';
        }

        return trim($diagnostico->diagnosticoCie10->nombre.' ('.$diagnostico->diagnosticoCie10->codigo.')');
    }

    public function datosTriaje(HcEmergencia $atencion): array
    {
        $color = $this->valorEnum($atencion->color_triaje ?: $atencion->prioridad?->color);

        $colores = [
            ColorTriaje::Rojo->value => ['fondo' => '#ef4444', 'color' => '#ffffff'],
            ColorTriaje::Naranja->value => ['fondo' => '#fb923c', 'color' => '#111827'],
            ColorTriaje::Amarillo->value => ['fondo' => '#facc15', 'color' => '#111827'],
            ColorTriaje::Verde->value => ['fondo' => '#4ade80', 'color' => '#052e16'],
            ColorTriaje::Azul->value => ['fondo' => '#60a5fa', 'color' => '#001340'],
        ];

        $visual = $colores[$color] ?? ['fondo' => '#98c1d9', 'color' => '#001340'];

        return [
            'texto' => $atencion->prioridad?->orden ? 'Nivel '.$atencion->prioridad->orden : 'Nivel --',
            'fondo' => $visual['fondo'],
            'color' => $visual['color'],
        ];
    }

    public function estadoAbierto(HcEmergencia $atencion): bool
    {
        return $this->valorEnum($atencion->estado_atencion) === EstadoAtencion::Abierta->value;
    }

    public function parametrosExportacion(): array
    {
        return [
            'desde' => $this->fechaDesde,
            'hasta' => $this->fechaHasta,
            'estado' => $this->estado,
        ];
    }

    private function consultaAtenciones(): Builder
    {
        return HcEmergencia::query()
            ->when($this->fechaDesde !== '', fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '<=', $this->fechaHasta))
            ->when($this->estado !== '' && $this->estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado_atencion', $this->estado));
    }

    private function consultaEstadisticas(): Builder
    {
        return HcEmergencia::query()
            ->when($this->fechaDesde !== '', fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '<=', $this->fechaHasta));
    }

    private function estadisticas(): array
    {
        $base = $this->consultaEstadisticas();

        return [
            'total' => (clone $base)->count(),
            'abiertas' => (clone $base)->where('estado_atencion', EstadoAtencion::Abierta->value)->count(),
            'cerradas' => (clone $base)->where('estado_atencion', EstadoAtencion::Cerrada->value)->count(),
            'triaje' => (clone $base)->where('triaje', true)->count(),
        ];
    }

    private function opcionesEstado(): array
    {
        return [
            'Todos' => 'Todos',
            EstadoAtencion::Abierta->value => 'Abiertas',
            EstadoAtencion::Cerrada->value => 'Cerradas',
        ];
    }

    private function buscarAtencion(int $atencionId): HcEmergencia
    {
        return HcEmergencia::query()
            ->with('paciente')
            ->findOrFail($atencionId);
    }

    private function resetearFormularioNuevaHistoria(): void
    {
        $this->nuevaHistoriaForm = [
            'profesional_salud_id' => null,
            'paciente_id' => null,
            'alias' => '',
        ];

        $this->profesionalBusqueda = '';
        $this->pacienteBusqueda = '';
    }

    /**
     * @return Collection<int, ProfesionalSalud>
     */
    private function profesionalesNuevaHistoria(): Collection
    {
        if (blank($this->profesionalBusqueda) || filled($this->nuevaHistoriaForm['profesional_salud_id'])) {
            return collect();
        }

        $busqueda = $this->profesionalBusqueda;

        return ProfesionalSalud::query()
            ->with('especialidad')
            ->where(function (Builder $consulta) use ($busqueda): void {
                $consulta
                    ->where('nombres', 'like', "%{$busqueda}%")
                    ->orWhere('cmp', 'like', "%{$busqueda}%")
                    ->orWhere('rne', 'like', "%{$busqueda}%")
                    ->orWhereHas('especialidad', fn ($especialidad) => $especialidad->where('nombre', 'like', "%{$busqueda}%"));
            })
            ->orderBy('nombres')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Paciente>
     */
    private function pacientesNuevaHistoria(): Collection
    {
        if (blank($this->pacienteBusqueda) || filled($this->nuevaHistoriaForm['paciente_id'])) {
            return collect();
        }

        $busqueda = $this->pacienteBusqueda;

        return Paciente::query()
            ->where(function (Builder $consulta) use ($busqueda): void {
                $consulta
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('numero_documento', 'like', "%{$busqueda}%")
                    ->orWhere('numero_historia', 'like', "%{$busqueda}%");
            })
            ->orderBy('nombre')
            ->limit(8)
            ->get();
    }

    private function etiquetaProfesional(ProfesionalSalud $profesional): string
    {
        return collect([
            $profesional->nombres,
            $profesional->especialidad?->nombre,
            $profesional->cmp ? 'CMP '.$profesional->cmp : null,
        ])->filter()->implode(' - ');
    }

    private function etiquetaPaciente(Paciente $paciente): string
    {
        return collect([
            $paciente->nombre,
            $paciente->numero_historia ? 'HC '.$paciente->numero_historia : null,
            $paciente->numero_documento,
        ])->filter()->implode(' - ');
    }

    private function valorEnum(mixed $valor): string
    {
        return $valor instanceof BackedEnum ? $valor->value : (string) ($valor ?? '');
    }
}
