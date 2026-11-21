<?php

namespace App\Livewire\HistoriaClinica;

use App\Enums\ColorTriaje;
use App\Enums\EstadoAtencion;
use App\Enums\SiNo;
use App\Enums\TipoDiagnostico;
use App\Models\Auditoria;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use App\Models\Paciente;
use App\Models\ProfesionalSalud;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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

    public bool $modalAuditoriaAbierto = false;

    public ?int $auditoriaEditandoId = null;

    public ?int $auditoriaAtencionId = null;

    public string $auditoriaBusqueda = '';

    /** @var array<string, mixed> */
    public array $auditoriaForm = [
        'fecha_hora_inicio' => '',
        'datos_incompletos' => [],
        'observaciones' => '',
        'usuario_id' => null,
        'estado' => 'Pendiente',
        'fecha_hora_fin' => '',
    ];

    /** @var array<string, mixed> */
    public array $nuevaHistoriaForm = [
        'profesional_salud_id' => null,
        'paciente_id' => null,
        'alias' => '',
        'tipo' => 'Accidental',
    ];

    public string $profesionalBusqueda = '';

    public string $pacienteBusqueda = '';

    public function mount(): void
    {
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
        $this->fechaDesde = '';
        $this->fechaHasta = '';
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

    public function abrirAuditoria(int $atencionId): void
    {
        abort_unless(Auth::user()?->can(\App\Support\Seguridad\Permisos::OBSERVACIONES_HCE), 403);

        $atencion = $this->buscarAtencion($atencionId);
        $auditoria = $atencion->auditoriaActual;

        $this->auditoriaAtencionId = $atencion->id;
        $this->auditoriaEditandoId = $auditoria?->id;
        $this->auditoriaForm = [
            'fecha_hora_inicio' => $auditoria?->fecha_hora_inicio?->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\TH:i'),
            'datos_incompletos' => $auditoria?->datos_incompletos ?: [],
            'observaciones' => $auditoria?->observaciones ?: '',
            'usuario_id' => $auditoria?->usuario_id ?: Auth::id(),
            'estado' => $auditoria?->estado ?: 'Pendiente',
            'fecha_hora_fin' => $auditoria?->fecha_hora_fin?->format('Y-m-d\TH:i') ?: '',
        ];
        $this->auditoriaBusqueda = '';
        $this->resetValidation();
        $this->modalAuditoriaAbierto = true;
    }

    public function cerrarModalAuditoria(): void
    {
        $this->modalAuditoriaAbierto = false;
        $this->auditoriaEditandoId = null;
        $this->auditoriaAtencionId = null;
        $this->auditoriaBusqueda = '';
        $this->resetValidation();
    }

    public function quitarDatoIncompleto(string $item): void
    {
        $this->auditoriaForm['datos_incompletos'] = array_values(array_filter(
            $this->auditoriaForm['datos_incompletos'] ?? [],
            fn (string $seleccionado): bool => $seleccionado !== $item
        ));
    }

    public function alternarDatoIncompleto(string $item): void
    {
        $seleccionados = $this->auditoriaForm['datos_incompletos'] ?? [];

        if (in_array($item, $seleccionados, true)) {
            $this->quitarDatoIncompleto($item);

            return;
        }

        $this->auditoriaForm['datos_incompletos'] = [...$seleccionados, $item];
    }

    public function agregarFraseAuditoria(string $frase): void
    {
        $observaciones = trim((string) ($this->auditoriaForm['observaciones'] ?? ''));
        $this->auditoriaForm['observaciones'] = trim($observaciones.' '.rtrim($frase, '.').'.');
    }

    public function guardarAuditoria(): void
    {
        abort_unless(Auth::user()?->can(\App\Support\Seguridad\Permisos::OBSERVACIONES_HCE), 403);

        $estados = ['Pendiente', 'Observada', 'Completada'];
        $usuariosPermitidos = $this->usuariosAuditoria()->pluck('id')->all();
        $datos = $this->validate([
            'auditoriaForm.fecha_hora_inicio' => ['required', 'date'],
            'auditoriaForm.datos_incompletos' => ['nullable', 'array'],
            'auditoriaForm.datos_incompletos.*' => ['string', Rule::in($this->itemsAuditoria())],
            'auditoriaForm.observaciones' => ['nullable', 'string'],
            'auditoriaForm.usuario_id' => ['required', 'integer', Rule::in($usuariosPermitidos)],
            'auditoriaForm.estado' => ['required', Rule::in($estados)],
            'auditoriaForm.fecha_hora_fin' => ['nullable', 'date', 'after_or_equal:auditoriaForm.fecha_hora_inicio'],
        ], [], [
            'auditoriaForm.fecha_hora_inicio' => 'fecha/hora de inicio de auditoria',
            'auditoriaForm.datos_incompletos' => 'datos incompletos',
            'auditoriaForm.observaciones' => 'observaciones',
            'auditoriaForm.usuario_id' => 'usuario auditor',
            'auditoriaForm.estado' => 'estado de auditoria',
            'auditoriaForm.fecha_hora_fin' => 'fecha/hora de fin de auditoria',
        ])['auditoriaForm'];

        $atencion = HcEmergencia::query()->findOrFail($this->auditoriaAtencionId ?? 0);
        $registro = $this->auditoriaEditandoId
            ? Auditoria::query()->where('hc_emergencia_id', $atencion->id)->findOrFail($this->auditoriaEditandoId)
            : new Auditoria(['hc_emergencia_id' => $atencion->id, 'registrado_por' => Auth::id()]);

        $registro->forceFill([
            'fecha_hora_inicio' => $datos['fecha_hora_inicio'],
            'datos_incompletos' => $datos['datos_incompletos'] ?: [],
            'observaciones' => $datos['observaciones'] ?: null,
            'usuario_id' => $datos['usuario_id'],
            'estado' => $datos['estado'],
            'fecha_hora_fin' => $datos['fecha_hora_fin'] ?: null,
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->cerrarModalAuditoria();
        flash()->success('Auditoria HCE guardada correctamente.', [], 'Auditoria HCE');
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
                'nuevaHistoriaForm.tipo' => ['required', Rule::in($this->tiposEmergencia())],
            ],
            [
                'nuevaHistoriaForm.alias.required_without' => 'Debe seleccionar un paciente o ingresar un alias.',
            ],
            [
                'nuevaHistoriaForm.profesional_salud_id' => 'profesional',
                'nuevaHistoriaForm.paciente_id' => 'paciente',
                'nuevaHistoriaForm.alias' => 'alias',
                'nuevaHistoriaForm.tipo' => 'tipo de emergencia',
            ]
        )['nuevaHistoriaForm'];

        if (blank($datos['paciente_id']) && blank($datos['alias'])) {
            $this->addError('nuevaHistoriaForm.alias', 'Debe seleccionar un paciente o ingresar un alias.');

            return;
        }

        $datosAtencion = [
            'paciente_id' => $datos['paciente_id'] ?: null,
            'alias' => $datos['alias'] ?: null,
            'fecha_hora_inicio_episodio' => now(),
            'fecha_hora_atencion' => now(),
            'profesional_salud_id' => $datos['profesional_salud_id'],
            'estado_atencion' => EstadoAtencion::Abierta->value,
            'sin_identificacion' => blank($datos['paciente_id']) ? SiNo::Si->value : SiNo::No->value,
            'registrado_por' => Auth::id(),
            'actualizado_por' => Auth::id(),
        ];

        if (Schema::hasColumn('hc_emergencia', 'tipo')) {
            $datosAtencion['tipo'] = $datos['tipo'];
        }

        HcEmergencia::query()->create($datosAtencion);

        $this->modalNuevaHistoriaAbierto = false;
        $this->resetearFormularioNuevaHistoria();
        $this->resetPage();
        $this->resetValidation();

        flash()->success('Historia clínica creada correctamente.', [], 'Historia clínica');
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
        abort_unless($this->usuarioPuedeEditarHistoriaClinica(), 403);

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
                'ambiente',
                'paciente',
                'profesionalResponsable',
                'prioridad',
                'diagnosticosIngreso.diagnosticoCie10',
                'auditoriaActual.usuario',
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
            'tiposEmergencia' => $this->tiposEmergencia(),
            'usuariosAuditoria' => $this->usuariosAuditoria(),
            'itemsAuditoria' => $this->itemsAuditoriaVisibles(),
            'estadosAuditoria' => ['Pendiente', 'Observada', 'Completada'],
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

    public function puedeEditarAtencion(HcEmergencia $atencion): bool
    {
        return $this->estadoAbierto($atencion) && $this->usuarioPuedeEditarHistoriaClinica();
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

    private function usuariosAuditoria(): Collection
    {
        return \App\Models\User::query()
            ->where('activo', true)
            ->whereHas('roles', fn ($consulta) => $consulta->where('name', 'Historias Clínicas'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /** @return array<int, string> */
    private function itemsAuditoria(): array
    {
        return [
            'Datos de la atención',
            'Triaje',
            'Anamnesis',
            'Exámenes Clínicos',
            'Dx y Evoluciones',
            'Órdenes y Laboratorio',
            'Fármacos y Procedimientos',
            'Notas enfermería',
            'Interconsultas',
            'Movimientos y Referencias',
            'Egresos',
        ];
    }

    private function itemsAuditoriaVisibles(): array
    {
        $busqueda = Str::lower(trim($this->auditoriaBusqueda));

        if ($busqueda === '') {
            return $this->itemsAuditoria();
        }

        return array_values(array_filter(
            $this->itemsAuditoria(),
            fn (string $item): bool => Str::contains(Str::lower($item), $busqueda)
        ));
    }

    private function buscarAtencion(int $atencionId): HcEmergencia
    {
        return HcEmergencia::query()
            ->with('paciente')
            ->findOrFail($atencionId);
    }

    private function usuarioPuedeEditarHistoriaClinica(): bool
    {
        $tipoUsuario = Str::lower(Str::ascii(trim((string) Auth::user()?->tipo_usuario)));

        return in_array($tipoUsuario, ['supervisor', 'profesional de salud', 'medico'], true);
    }

    private function resetearFormularioNuevaHistoria(): void
    {
        $this->nuevaHistoriaForm = [
            'profesional_salud_id' => null,
            'paciente_id' => null,
            'alias' => '',
            'tipo' => 'Accidental',
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

    /**
     * @return array<int, string>
     */
    private function tiposEmergencia(): array
    {
        return ['Accidental', 'Médica'];
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
