<?php

namespace App\Livewire\HistoriaClinica;

use App\Enums\EstadoAtencion;
use App\Enums\TipoDiagnostico;
use App\Models\AntecedentePatologico;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use App\Models\Paciente;
use App\Models\PacienteAlergia;
use App\Models\ReaccionAdversaMedicamento;
use App\Models\TriajeSignoVital;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class HistoriaClinicaPaciente extends Component
{
    use WithPagination;

    public Paciente $paciente;

    #[Url(except: 5)]
    public int $porPagina = 5;

    public ?string $modalResumen = null;

    public function mount(Paciente $paciente): void
    {
        $this->paciente = $paciente;
    }

    public function updatingPorPagina(): void
    {
        $this->resetPage();
    }

    public function abrirModalResumen(string $tipo): void
    {
        abort_unless(in_array($tipo, ['alergias', 'reacciones', 'antecedentes'], true), 404);

        $this->modalResumen = $tipo;
    }

    public function cerrarModalResumen(): void
    {
        $this->modalResumen = null;
    }

    public function nuevoRegistro(): void
    {
        $usuario = auth()->user();

        if ($usuario?->tipo_usuario !== 'Médico' || $usuario?->profesional_salud_id === null) {
            flash()->warning('Solo un usuario medico vinculado a un profesional de salud puede crear una nueva atencion.', [], 'Historia clinica');

            return;
        }

        $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.nuevo', $this->paciente);
    }

    public function verAtencion(int $atencionId): void
    {
        $atencion = $this->buscarAtencionDelPaciente($atencionId);

        $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.ver', [
            'paciente' => $this->paciente,
            'atencion' => $atencion,
        ]);
    }

    public function editarAtencion(int $atencionId): void
    {
        $atencion = $this->buscarAtencionDelPaciente($atencionId);

        $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.editar', [
            'paciente' => $this->paciente,
            'atencion' => $atencion,
        ]);
    }

    public function cambiarEstadoAtencion(int $atencionId): void
    {
        abort_unless($this->esAdministrador(), 403);

        $atencion = $this->buscarAtencionDelPaciente($atencionId);
        $nuevoEstado = $this->estadoAbierto($atencion)
            ? EstadoAtencion::Cerrada
            : EstadoAtencion::Abierta;

        $atencion->forceFill([
            'estado_atencion' => $nuevoEstado,
            'actualizado_por' => Auth::id(),
        ])->save();

        flash()->success('Estado de la historia clinica actualizado correctamente.', [], 'Historia clinica');
    }

    public function render()
    {
        $paciente = Paciente::query()
            ->with([
                'alergias.alergia',
                'reaccionesAdversasMedicamentos',
                'antecedentesPatologicos.diagnosticoCie10',
            ])
            ->findOrFail($this->paciente->id);

        $atenciones = HcEmergencia::query()
            ->where('paciente_id', $paciente->id)
            ->with([
                'profesionalResponsable',
                'prioridad',
                'diagnosticosIngreso.diagnosticoCie10',
            ])
            ->orderByDesc('fecha_hora_atencion')
            ->paginate($this->porPagina)
            ->withPath(route('historia-clinica.atenciones-paciente.historia', $paciente));

        return view('livewire.historia-clinica.historia-clinica-paciente', [
            'pacienteActual' => $paciente,
            'datosPaciente' => $this->datosPaciente($paciente),
            'resumenClinico' => $this->resumenClinico($paciente),
            'ultimosSignos' => $this->ultimosSignosVitales(),
            'atenciones' => $atenciones,
            'modal' => $this->datosModalResumen(),
            'esAdministrador' => $this->esAdministrador(),
        ]);
    }

    /**
     * Prepara datos compactos para el encabezado del paciente.
     *
     * @return array<string, string>
     */
    private function datosPaciente(Paciente $paciente): array
    {
        return [
            'sexo' => $paciente->sexo ?: 'No definido',
            'sexo_clase' => $this->claseSexo($paciente->sexo),
            'sexo_icono' => $this->iconoSexo($paciente->sexo),
            'edad' => $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age.' anos' : '--',
            'grupo' => $this->datoClinico($this->valorEnum($paciente->grupo_sanguineo)),
            'factor' => $this->datoClinico($this->valorEnum($paciente->factor_rh)),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resumenClinico(Paciente $paciente): array
    {
        $alergias = $paciente->alergias
            ->sortByDesc(fn (PacienteAlergia $item): int => $item->created_at?->timestamp ?? 0)
            ->values();
        $reacciones = $paciente->reaccionesAdversasMedicamentos
            ->sortByDesc(fn (ReaccionAdversaMedicamento $item): int => $item->created_at?->timestamp ?? 0)
            ->values();
        $antecedentes = $paciente->antecedentesPatologicos
            ->sortByDesc(fn (AntecedentePatologico $item): int => $item->created_at?->timestamp ?? 0)
            ->values();

        return [
            'alergias' => [
                'titulo' => 'Alergias',
                'icono' => 'healthicons:allergies-outline',
                'clase' => 'is-allergy',
                'vacio' => 'Sin alergias registradas',
                'items' => $alergias->take(2)->map(fn (PacienteAlergia $item): string => $item->alergia?->nombre ?? 'Alergia registrada'),
                'total' => $alergias->count(),
            ],
            'reacciones' => [
                'titulo' => 'Reacciones Adversas',
                'icono' => 'healthicons:medicines-outline',
                'clase' => 'is-reaction',
                'vacio' => 'Sin reacciones adversas registradas',
                'items' => $reacciones->take(2)->map(fn (ReaccionAdversaMedicamento $item): string => $item->dci_medicamento ?: $item->descripcion),
                'total' => $reacciones->count(),
            ],
            'antecedentes' => [
                'titulo' => 'Antecedentes',
                'icono' => 'lucide:file-clock',
                'clase' => 'is-history',
                'vacio' => 'Sin antecedentes registrados',
                'items' => $antecedentes->take(2)->map(fn (AntecedentePatologico $item): string => $item->denominacion ?: ($item->diagnosticoCie10?->nombre ?? 'Antecedente registrado')),
                'total' => $antecedentes->count(),
            ],
        ];
    }

    /**
     * @return array{pa: string, fc: string, tiene_datos: bool}
     */
    private function ultimosSignosVitales(): array
    {
        $ultimoSigno = TriajeSignoVital::query()
            ->with('hcEmergencia.signosVitalesTriaje.signoVital')
            ->whereHas('hcEmergencia', fn (Builder $consulta): Builder => $consulta->where('paciente_id', $this->paciente->id))
            ->latest('created_at')
            ->first();

        $signos = $ultimoSigno?->hcEmergencia?->signosVitalesTriaje ?? collect();

        if ($signos->isEmpty()) {
            return ['pa' => '--', 'fc' => '--', 'tiene_datos' => false];
        }

        $presion = $this->presionArterial($signos);
        $frecuencia = $this->ultimoValorPorDescripcion($signos, ['frecuencia cardiaca', 'fc']);

        return [
            'pa' => $presion !== '--' ? $presion.' mmHg' : '--',
            'fc' => $frecuencia ? $frecuencia.' lpm' : '--',
            'tiene_datos' => $presion !== '--' || $frecuencia !== null,
        ];
    }

    private function presionArterial(Collection $signos): string
    {
        $presionCompuesta = $this->ultimoValorPorDescripcion($signos, ['presion arterial']);

        if ($presionCompuesta !== null && str_contains($presionCompuesta, '/')) {
            return $presionCompuesta;
        }

        $pas = $this->ultimoValorPorDescripcion($signos, ['pas', 'sistolica']);
        $pad = $this->ultimoValorPorDescripcion($signos, ['pad', 'diastolica']);

        return ($pas && $pad) ? $pas.'/'.$pad : '--';
    }

    private function ultimoValorPorDescripcion(Collection $signos, array $terminos): ?string
    {
        $signo = $signos->first(function (TriajeSignoVital $item) use ($terminos): bool {
            $descripcion = mb_strtolower($item->signoVital?->descripcion ?? '');

            return collect($terminos)->contains(fn (string $termino): bool => str_contains($descripcion, mb_strtolower($termino)));
        });

        return $signo?->valor;
    }

    /**
     * @return array<string, mixed>
     */
    private function datosModalResumen(): array
    {
        if ($this->modalResumen === null) {
            return ['abierto' => false];
        }

        return match ($this->modalResumen) {
            'alergias' => [
                'abierto' => true,
                'titulo' => 'Alergias del paciente',
                'icono' => 'healthicons:allergies-outline',
                'tipo' => 'alergias',
                'registros' => PacienteAlergia::query()
                    ->with('alergia')
                    ->where('paciente_id', $this->paciente->id)
                    ->latest()
                    ->get(),
            ],
            'reacciones' => [
                'abierto' => true,
                'titulo' => 'Reacciones adversas',
                'icono' => 'healthicons:medicines-outline',
                'tipo' => 'reacciones',
                'registros' => ReaccionAdversaMedicamento::query()
                    ->where('paciente_id', $this->paciente->id)
                    ->latest()
                    ->get(),
            ],
            'antecedentes' => [
                'abierto' => true,
                'titulo' => 'Antecedentes patologicos',
                'icono' => 'lucide:file-clock',
                'tipo' => 'antecedentes',
                'registros' => AntecedentePatologico::query()
                    ->with('diagnosticoCie10')
                    ->where('paciente_id', $this->paciente->id)
                    ->latest()
                    ->get(),
            ],
            default => ['abierto' => false],
        };
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
        if ($diagnostico === null) {
            return 'En evaluacion...';
        }

        $cie10 = $diagnostico->diagnosticoCie10;

        if ($cie10 === null) {
            return 'En evaluacion...';
        }

        return trim($cie10->nombre.' ('.$cie10->codigo.')');
    }

    /**
     * @return array{texto: string, fondo: string, color: string}
     */
    public function datosTriaje(HcEmergencia $atencion): array
    {
        $color = $this->valorEnum($atencion->color_triaje);

        $colores = [
            'Rojo' => ['fondo' => '#ef4444', 'color' => '#ffffff'],
            'Naranja' => ['fondo' => '#fb923c', 'color' => '#111827'],
            'Amarillo' => ['fondo' => '#facc15', 'color' => '#111827'],
            'Verde' => ['fondo' => '#4ade80', 'color' => '#052e16'],
            'Azul' => ['fondo' => '#60a5fa', 'color' => '#001340'],
        ];

        $visual = $colores[$color] ?? ['fondo' => '#98c1d9', 'color' => '#001340'];
        $nivel = $atencion->prioridad?->orden ? 'Nivel '.$atencion->prioridad->orden : 'Nivel --';

        return [
            'texto' => $nivel,
            'fondo' => $visual['fondo'],
            'color' => $visual['color'],
        ];
    }

    public function estadoAbierto(HcEmergencia $atencion): bool
    {
        return $this->valorEnum($atencion->estado_atencion) === EstadoAtencion::Abierta->value;
    }

    private function buscarAtencionDelPaciente(int $atencionId): HcEmergencia
    {
        return HcEmergencia::query()
            ->where('paciente_id', $this->paciente->id)
            ->findOrFail($atencionId);
    }

    private function esAdministrador(): bool
    {
        return Auth::user()?->hasRole('Administrador') ?? false;
    }

    private function claseSexo(?string $sexo): string
    {
        $valor = mb_strtolower($sexo ?? '');

        return match (true) {
            str_contains($valor, 'femenino') => 'is-female',
            str_contains($valor, 'masculino') => 'is-male',
            default => 'is-unknown',
        };
    }

    private function iconoSexo(?string $sexo): string
    {
        $valor = mb_strtolower($sexo ?? '');

        return match (true) {
            str_contains($valor, 'femenino') => 'lucide:venus',
            str_contains($valor, 'masculino') => 'lucide:mars',
            default => 'lucide:circle-help',
        };
    }

    private function datoClinico(?string $valor): string
    {
        return $valor && $valor !== 'No conocido' ? $valor : '--';
    }

    private function valorEnum(mixed $valor): string
    {
        return $valor instanceof BackedEnum ? $valor->value : (string) ($valor ?? '');
    }
}
