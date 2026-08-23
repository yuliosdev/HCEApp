<?php

namespace App\Livewire\HistoriaClinica;

use App\Enums\EstadoAtencion;
use App\Enums\FuenteAtencion;
use App\Enums\SiNo;
use App\Enums\TopicoAtencion;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use App\Models\Prioridad;
use App\Models\ProfesionalSalud;
use App\Models\TriajeSignoVital;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class HcEmergenciaPanel extends Component
{
    public Paciente $paciente;

    public ?HcEmergencia $atencion = null;

    public string $modo = 'nuevo';

    public string $tabActiva = 'atencion';

    /** @var array<string, mixed> */
    public array $form = [
        'numero_atencion' => null,
        'fecha_hora_inicio_episodio' => null,
        'fecha_hora_atencion' => null,
        'topico' => null,
        'ambiente' => null,
        'acompanante' => null,
        'parentesco' => null,
        'telefono' => null,
        'fuente' => null,
        'lugar_evento' => null,
        'tipo_lesion_evento' => null,
        'fecha_hora_recepcion' => null,
        'signos_vitales_transmitidos' => null,
        'prioridad_id' => null,
        'estado_atencion' => 'Abierta',
        'profesional_salud_id' => null,
    ];

    public function mount(Paciente $paciente, ?HcEmergencia $atencion = null, string $modo = 'nuevo'): void
    {
        abort_unless(in_array($modo, ['nuevo', 'ver', 'editar'], true), 404);

        $this->paciente = $paciente;
        $this->atencion = $atencion;
        $this->modo = $modo;

        if ($modo === 'nuevo') {
            $this->prepararNuevoRegistro();

            return;
        }

        abort_if($atencion === null || $atencion->paciente_id !== $paciente->id, 404);

        $this->cargarAtencion($atencion);
    }

    public function cambiarTab(string $tab): void
    {
        abort_unless(collect($this->tabs())->pluck('clave')->contains($tab), 404);

        $this->tabActiva = $tab;
    }

    public function guardar(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->modo === 'nuevo') {
            $this->validarUsuarioMedico();
        }

        $datos = $this->validate($this->reglas())['form'];
        $usuarioId = Auth::id();

        $atencion = $this->atencion ?: new HcEmergencia([
            'paciente_id' => $this->paciente->id,
            'registrado_por' => $usuarioId,
        ]);

        $atencion->forceFill([
            'numero_atencion' => $datos['numero_atencion'] ?: $this->generarNumeroAtencion(),
            'fecha_hora_inicio_episodio' => $datos['fecha_hora_inicio_episodio'] ?: now(),
            'fecha_hora_atencion' => $datos['fecha_hora_atencion'] ?: now(),
            'topico' => $datos['topico'] ?: TopicoAtencion::Topico->value,
            'ambiente' => $datos['ambiente'],
            'acompanante' => $datos['acompanante'],
            'parentesco' => $datos['parentesco'],
            'telefono' => $datos['telefono'],
            'fuente' => $datos['fuente'],
            'lugar_evento' => $datos['lugar_evento'],
            'tipo_lesion_evento' => $datos['tipo_lesion_evento'],
            'fecha_hora_recepcion' => $datos['fecha_hora_recepcion'],
            'signos_vitales_transmitidos' => $datos['signos_vitales_transmitidos'],
            'paciente_id' => $this->paciente->id,
            'sin_identificacion' => SiNo::No->value,
            'prioridad_id' => $datos['prioridad_id'],
            'profesional_salud_id' => $datos['profesional_salud_id'],
            'estado_atencion' => $datos['estado_atencion'] ?: EstadoAtencion::Abierta->value,
            'actualizado_por' => $usuarioId,
        ])->save();

        $this->atencion = $atencion->fresh(['paciente', 'profesionalResponsable.especialidad', 'prioridad']);
        $this->modo = 'editar';
        $this->cargarAtencion($this->atencion);

        flash()->success('Historia clinica de emergencia guardada correctamente.', [], 'Historia clinica');

        $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.editar', [
            'paciente' => $this->paciente,
            'atencion' => $this->atencion,
        ]);
    }

    public function cerrarAtencion(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            $this->form['estado_atencion'] = EstadoAtencion::Cerrada->value;
            $this->guardar();

            return;
        }

        $this->atencion->forceFill([
            'estado_atencion' => EstadoAtencion::Cerrada->value,
            'fecha_hora_egreso' => now(),
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->cargarAtencion($this->atencion->fresh(['paciente', 'profesionalResponsable.especialidad', 'prioridad']));

        flash()->success('Atencion cerrada correctamente.', [], 'Historia clinica');
    }

    public function render()
    {
        return view('livewire.historia-clinica.hc-emergencia-panel', [
            'datosPaciente' => $this->datosPaciente(),
            'datosMedico' => $this->datosMedico(),
            'prioridades' => Prioridad::query()->orderBy('orden')->get(['id', 'prioridad', 'color', 'orden']),
            'signos' => $this->signosResumen(),
            'tabs' => $this->tabs(),
            'topicos' => TopicoAtencion::cases(),
            'fuentesAtencion' => FuenteAtencion::cases(),
            'eventosEpisodio' => $this->eventosEpisodio(),
            'soloLectura' => $this->modo === 'ver',
            'esAtencionAbierta' => $this->form['estado_atencion'] === EstadoAtencion::Abierta->value,
        ]);
    }

    /**
     * @return array<int, array{clave: string, titulo: string, icono: string}>
     */
    public function tabs(): array
    {
        return [
            ['clave' => 'atencion', 'titulo' => 'Atencion', 'icono' => 'lucide:stethoscope'],
            ['clave' => 'triaje', 'titulo' => 'Triaje / Anamnesis', 'icono' => 'lucide:clipboard-list'],
            ['clave' => 'examenes', 'titulo' => 'Examenes Clinicos', 'icono' => 'lucide:microscope'],
            ['clave' => 'diagnosticos', 'titulo' => 'Diagnosticos y Evoluciones', 'icono' => 'lucide:activity'],
            ['clave' => 'ordenes', 'titulo' => 'Ordenes y Laboratorio', 'icono' => 'lucide:test-tube'],
            ['clave' => 'medicamentos', 'titulo' => 'Fármacos y Procedimientos', 'icono' => 'healthicons:medicines-outline'],
            ['clave' => 'enfermeria', 'titulo' => 'Enfermeria y Balance Hidrico', 'icono' => 'lucide:droplets'],
            ['clave' => 'interconsultas', 'titulo' => 'Interconsultas', 'icono' => 'lucide:messages-square'],
            ['clave' => 'movimientos', 'titulo' => 'Movimientos y Transferencias', 'icono' => 'lucide:repeat-2'],
            ['clave' => 'egresos', 'titulo' => 'Egresos', 'icono' => 'lucide:door-open'],
            ['clave' => 'documentacion', 'titulo' => 'Documentacion', 'icono' => 'lucide:folder-open'],
        ];
    }

    private function prepararNuevoRegistro(): void
    {
        $this->validarUsuarioMedico();

        $profesionalId = Auth::user()?->profesional_salud_id;

        $this->form = [
            'numero_atencion' => $this->generarNumeroAtencion(),
            'fecha_hora_inicio_episodio' => now()->format('Y-m-d\TH:i'),
            'fecha_hora_atencion' => now()->format('Y-m-d\TH:i'),
            'topico' => TopicoAtencion::Topico->value,
            'ambiente' => null,
            'acompanante' => null,
            'parentesco' => null,
            'telefono' => null,
            'fuente' => null,
            'lugar_evento' => null,
            'tipo_lesion_evento' => null,
            'fecha_hora_recepcion' => null,
            'signos_vitales_transmitidos' => $this->plantillaSignosVitalesPrehospitalarios(),
            'prioridad_id' => Prioridad::query()->orderByRaw('orden = 2 desc')->orderBy('orden')->value('id'),
            'estado_atencion' => EstadoAtencion::Abierta->value,
            'profesional_salud_id' => $profesionalId,
        ];
    }

    private function cargarAtencion(HcEmergencia $atencion): void
    {
        $this->atencion = $atencion->loadMissing(['paciente', 'profesionalResponsable.especialidad', 'prioridad']);

        $this->form = [
            'numero_atencion' => $atencion->numero_atencion,
            'fecha_hora_inicio_episodio' => $atencion->fecha_hora_inicio_episodio?->format('Y-m-d\TH:i'),
            'fecha_hora_atencion' => $atencion->fecha_hora_atencion?->format('Y-m-d\TH:i'),
            'topico' => $this->valorEnum($atencion->topico),
            'ambiente' => $atencion->ambiente,
            'acompanante' => $atencion->acompanante,
            'parentesco' => $atencion->parentesco,
            'telefono' => $atencion->telefono,
            'fuente' => $this->valorEnum($atencion->fuente),
            'lugar_evento' => $atencion->lugar_evento,
            'tipo_lesion_evento' => $atencion->tipo_lesion_evento,
            'fecha_hora_recepcion' => $atencion->fecha_hora_recepcion?->format('Y-m-d\TH:i'),
            'signos_vitales_transmitidos' => $atencion->signos_vitales_transmitidos ?: $this->plantillaSignosVitalesPrehospitalarios(),
            'prioridad_id' => $atencion->prioridad_id,
            'estado_atencion' => $this->valorEnum($atencion->estado_atencion) ?: EstadoAtencion::Abierta->value,
            'profesional_salud_id' => $atencion->profesional_salud_id,
        ];
    }

    private function validarUsuarioMedico(): void
    {
        $usuario = Auth::user();

        abort_if($usuario?->tipo_usuario !== 'Médico' || $usuario?->profesional_salud_id === null, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function reglas(): array
    {
        return [
            'form.numero_atencion' => ['required', 'integer'],
            'form.fecha_hora_inicio_episodio' => ['nullable', 'date'],
            'form.fecha_hora_atencion' => ['nullable', 'date'],
            'form.topico' => ['nullable', Rule::in(array_map(fn (BackedEnum $enum): string => $enum->value, TopicoAtencion::cases()))],
            'form.ambiente' => ['nullable', 'string', 'max:150'],
            'form.acompanante' => ['nullable', 'string', 'max:255'],
            'form.parentesco' => ['nullable', 'string', 'max:100'],
            'form.telefono' => ['nullable', 'string', 'max:50'],
            'form.fuente' => ['nullable', Rule::in(array_map(fn (BackedEnum $enum): string => $enum->value, FuenteAtencion::cases()))],
            'form.lugar_evento' => ['nullable', 'string'],
            'form.tipo_lesion_evento' => ['nullable', 'string'],
            'form.fecha_hora_recepcion' => ['nullable', 'date'],
            'form.signos_vitales_transmitidos' => ['nullable', 'string'],
            'form.prioridad_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')->whereNull('deleted_at')],
            'form.estado_atencion' => ['required', Rule::in(array_map(fn (BackedEnum $enum): string => $enum->value, EstadoAtencion::cases()))],
            'form.profesional_salud_id' => ['required', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function datosPaciente(): array
    {
        return [
            'nombre' => $this->paciente->nombre,
            'sexo' => $this->paciente->sexo ?: 'No definido',
            'edad' => $this->paciente->fecha_nacimiento ? $this->paciente->fecha_nacimiento->age.' anos' : '--',
            'historia' => $this->paciente->numero_historia ?: '--',
            'documento' => $this->paciente->numero_documento ?: '--',
            'telefono' => $this->paciente->telefono ?: '--',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function datosMedico(): array
    {
        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->find($this->form['profesional_salud_id']);

        return [
            'nombre' => $profesional?->nombres ?? 'Sin profesional asignado',
            'especialidad' => $profesional?->especialidad?->nombre ?? 'Sin especialidad',
            'cmp' => $profesional?->cmp ?: '--',
            'rne' => $profesional?->rne ?: '--',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function signosResumen(): array
    {
        $signos = $this->signosDeReferencia();

        return [
            'fc' => $this->ultimoValorPorDescripcion($signos, ['frecuencia cardiaca', 'fc']) ?: '--',
            'pa' => $this->presionArterial($signos),
            'spo2' => $this->ultimoValorPorDescripcion($signos, ['spo2', 'saturacion', 'saturación']) ?: '--',
        ];
    }

    private function signosDeReferencia(): Collection
    {
        if ($this->atencion !== null) {
            return $this->atencion->signosVitalesTriaje()->with('signoVital')->latest()->get();
        }

        $ultimoSigno = TriajeSignoVital::query()
            ->with('hcEmergencia.signosVitalesTriaje.signoVital')
            ->whereHas('hcEmergencia', fn ($consulta) => $consulta->where('paciente_id', $this->paciente->id))
            ->latest('created_at')
            ->first();

        return $ultimoSigno?->hcEmergencia?->signosVitalesTriaje ?? collect();
    }

    private function presionArterial(Collection $signos): string
    {
        $compuesta = $this->ultimoValorPorDescripcion($signos, ['presion arterial', 'presión arterial']);

        if ($compuesta !== null && str_contains($compuesta, '/')) {
            return $compuesta;
        }

        $pas = $this->ultimoValorPorDescripcion($signos, ['pas', 'sistolica', 'sistólica']);
        $pad = $this->ultimoValorPorDescripcion($signos, ['pad', 'diastolica', 'diastólica']);

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
     * Ordena cronologicamente los eventos disponibles del episodio.
     *
     * @return array<int, array{fecha: string, titulo: string, detalle: string}>
     */
    private function eventosEpisodio(): array
    {
        $eventos = collect([
            [
                'fecha' => $this->form['fecha_hora_inicio_episodio'],
                'titulo' => 'Inicio del episodio',
                'detalle' => $this->form['lugar_evento'] ?: 'Registro inicial de la emergencia',
            ],
            [
                'fecha' => $this->form['fecha_hora_atencion'],
                'titulo' => 'Atencion medica iniciada',
                'detalle' => $this->datosMedico()['nombre'],
            ],
            [
                'fecha' => $this->form['fecha_hora_recepcion'],
                'titulo' => 'Recepcion pre hospitalaria',
                'detalle' => $this->form['fuente'] ?: 'Fuente no registrada',
            ],
            [
                'fecha' => $this->atencion?->fecha_hora_triaje,
                'titulo' => 'Triaje registrado',
                'detalle' => $this->atencion?->prioridad?->prioridad ?? 'Prioridad pendiente',
            ],
            [
                'fecha' => $this->atencion?->fecha_hora_anamnesis,
                'titulo' => 'Anamnesis registrada',
                'detalle' => 'Datos clinicos iniciales actualizados',
            ],
            [
                'fecha' => $this->atencion?->fecha_hora_firma,
                'titulo' => 'Firma clinica',
                'detalle' => $this->atencion?->estado_firma?->value ?? 'Firma pendiente',
            ],
            [
                'fecha' => $this->atencion?->fecha_hora_egreso,
                'titulo' => 'Egreso registrado',
                'detalle' => $this->atencion?->condicion_egreso?->value ?? 'Condicion pendiente',
            ],
        ]);

        return $eventos
            ->filter(fn (array $evento): bool => filled($evento['fecha']))
            ->map(function (array $evento): array {
                $fecha = $evento['fecha'] instanceof \DateTimeInterface
                    ? \Carbon\Carbon::instance($evento['fecha'])
                    : \Carbon\Carbon::parse($evento['fecha']);

                return [
                    'fecha' => $fecha->isToday() ? 'Hoy, '.$fecha->format('H:i') : $fecha->format('d/m/Y H:i'),
                    'titulo' => $evento['titulo'],
                    'detalle' => $evento['detalle'],
                    'orden' => $fecha->timestamp,
                ];
            })
            ->sortByDesc('orden')
            ->map(fn (array $evento): array => collect($evento)->except('orden')->all())
            ->values()
            ->all();
    }

    private function plantillaSignosVitalesPrehospitalarios(): string
    {
        return "PA:\nFC:\nT°:\nSoP2:\nGlasgow:";
    }

    private function generarNumeroAtencion(): int
    {
        $ultimoNumero = (int) HcEmergencia::query()->max('numero_atencion');

        return $ultimoNumero > 0 ? $ultimoNumero + 1 : 100001;
    }

    private function valorEnum(mixed $valor): ?string
    {
        return $valor instanceof BackedEnum ? $valor->value : ($valor !== null ? (string) $valor : null);
    }
}
