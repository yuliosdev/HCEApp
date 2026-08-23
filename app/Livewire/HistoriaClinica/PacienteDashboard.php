<?php

namespace App\Livewire\HistoriaClinica;

use App\Enums\GravedadReaccionAdversaMedicamento;
use App\Enums\ParentescoFamiliar;
use App\Enums\TipoReaccionAdversaMedicamento;
use App\Models\Alergia;
use App\Models\AntecedenteFamiliar;
use App\Models\AntecedentePatologico;
use App\Models\CirugiaPrevia;
use App\Models\DiagnosticoCie10;
use App\Models\HospitalizacionPrevia;
use App\Models\MedicamentoHabitual;
use App\Models\Paciente;
use App\Models\PacienteAlergia;
use App\Models\ReaccionAdversaMedicamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PacienteDashboard extends Component
{
    public Paciente $paciente;

    public ?string $seccionActiva = null;

    public ?int $registroEditandoId = null;

    /**
     * Datos temporales del formulario dinamico del drawer.
     *
     * @var array<string, mixed>
     */
    public array $formulario = [];

    public function mount(Paciente $paciente): void
    {
        $this->paciente = $paciente;
    }

    public function abrirDrawer(string $seccion): void
    {
        abort_unless(array_key_exists($seccion, $this->secciones()), 404);

        $this->seccionActiva = $seccion;
        $this->registroEditandoId = null;
        $this->formulario = $this->formularioVacio($seccion);
        $this->resetValidation();
    }

    public function cerrarDrawer(): void
    {
        $this->seccionActiva = null;
        $this->registroEditandoId = null;
        $this->formulario = [];
        $this->resetValidation();
    }

    public function nuevoRegistro(): void
    {
        if ($this->seccionActiva === null) {
            return;
        }

        $this->registroEditandoId = null;
        $this->formulario = $this->formularioVacio($this->seccionActiva);
        $this->resetValidation();
    }

    public function editarRegistro(int $registroId): void
    {
        if ($this->seccionActiva === null) {
            return;
        }

        $registro = $this->buscarRegistroDeSeccion($this->seccionActiva, $registroId);

        $this->registroEditandoId = $registro->id;
        $this->formulario = $this->datosFormulario($this->seccionActiva, $registro);
        $this->resetValidation();
    }

    public function guardarRegistro(): void
    {
        if ($this->seccionActiva === null) {
            return;
        }

        $datos = $this->validate($this->reglasFormulario($this->seccionActiva))['formulario'];
        $datos = collect($datos)
            ->map(fn ($valor) => $valor === '' ? null : $valor)
            ->all();

        $modelo = $this->modeloDeSeccion($this->seccionActiva);
        $registro = $this->registroEditandoId
            ? $this->buscarRegistroDeSeccion($this->seccionActiva, $this->registroEditandoId)
            : new $modelo;

        $registro->fill(array_merge($datos, ['paciente_id' => $this->paciente->id]));
        $registro->save();

        flash()->success('La informacion clinica del paciente fue actualizada.', [], 'Dashboard del paciente');

        $this->nuevoRegistro();
    }

    public function eliminarRegistro(int $registroId): void
    {
        if ($this->seccionActiva === null) {
            return;
        }

        $this->buscarRegistroDeSeccion($this->seccionActiva, $registroId)->delete();

        flash()->success('El registro clinico fue eliminado correctamente.', [], 'Dashboard del paciente');
        $this->nuevoRegistro();
    }

    public function verHistoriaClinica(): void
    {
        $this->redirectRoute('historia-clinica.atenciones-paciente.historia', $this->paciente);
    }

    public function render()
    {
        $paciente = Paciente::query()
            ->with([
                'alergias.alergia',
                'reaccionesAdversasMedicamentos',
                'antecedentesFamiliares.diagnosticoCie10',
                'cirugiasPrevias',
                'hospitalizacionesPrevias.diagnosticoCie10',
                'medicamentosHabituales',
                'antecedentesPatologicos.diagnosticoCie10',
            ])
            ->findOrFail($this->paciente->id);

        return view('livewire.historia-clinica.paciente-dashboard', [
            'pacienteActual' => $paciente,
            'datosPaciente' => $this->datosPaciente($paciente),
            'alertas' => $this->alertasCriticas($paciente),
            'bloques' => $this->bloquesClinicos($paciente),
            'tendencias' => $this->tendenciasSignosVitales(),
            'drawer' => $this->datosDrawer(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosPaciente(Paciente $paciente): array
    {
        $ultimaAtencion = $paciente->hcEmergencias()
            ->with('signosVitalesTriaje.signoVital')
            ->latest('fecha_hora_atencion')
            ->first();

        $signos = $ultimaAtencion?->signosVitalesTriaje ?? collect();
        $peso = $this->ultimoValorPorDescripcion($signos, ['peso']);
        $talla = $this->ultimoValorPorDescripcion($signos, ['talla']);

        return [
            'edad' => $paciente->fecha_nacimiento
                ? $paciente->fecha_nacimiento->format('d/m/Y').' ('.$paciente->fecha_nacimiento->age.' anos)'
                : 'No registrada',
            'sexo' => $paciente->sexo ?: 'No definido',
            'grupo' => $this->valorEnum($paciente->grupo_sanguineo).' '.$this->valorEnum($paciente->factor_rh),
            'peso_talla' => trim(($peso ?: '-- kg').' / '.($talla ?: '-- cm')),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function alertasCriticas(Paciente $paciente): array
    {
        return [
            [
                'seccion' => 'alergias',
                'titulo' => 'ALERGIA SEVERA',
                'icono' => 'healthicons:allergies-outline',
                'clase' => 'is-critical',
                'vacio' => 'Sin alergias registradas.',
                'items' => $paciente->alergias->map(fn (PacienteAlergia $item): array => [
                    'principal' => $item->alergia?->nombre ?? 'Alergia registrada',
                    'detalle' => collect([
                        $item->agente_causal ? 'Agente: '.$item->agente_causal : null,
                        $item->alergeno ? 'Alergeno: '.$item->alergeno : null,
                        $item->periodo ? 'Periodo: '.$item->periodo : null,
                    ])->filter()->implode(' | ') ?: 'Pendiente de evaluacion',
                ]),
            ],
            [
                'seccion' => 'reacciones',
                'titulo' => 'REACCION ADVERSA',
                'icono' => 'healthicons:medicines-outline',
                'clase' => 'is-neutral',
                'vacio' => 'Sin RAM registradas.',
                'items' => $paciente->reaccionesAdversasMedicamentos->map(fn (ReaccionAdversaMedicamento $item): array => [
                    'principal' => $item->dci_medicamento ?: 'Medicamento no especificado',
                    'detalle' => collect([
                        $item->descripcion ?: null,
                        $this->valorEnum($item->tipo) ? 'Tipo: '.$this->valorEnum($item->tipo) : null,
                        $this->valorEnum($item->gravedad) ? 'Gravedad: '.$this->valorEnum($item->gravedad) : null,
                        $item->periodo ? 'Periodo: '.$item->periodo : null,
                    ])->filter()->implode(' | ') ?: 'Pendiente de evaluacion',
                ]),
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function bloquesClinicos(Paciente $paciente): array
    {
        return [
            'familiares' => [
                'titulo' => 'Antecedentes Familiares',
                'icono' => 'lucide:git-fork',
                'tipo' => 'tabla',
                'items' => $paciente->antecedentesFamiliares->take(4)->map(fn (AntecedenteFamiliar $item): array => [
                    'parentesco' => $this->valorEnum($item->parentesco) ?: 'No definido',
                    'condicion' => $item->denominacion ?: ($item->diagnosticoCie10?->nombre ?? 'No registrada'),
                    'periodo' => $item->periodo ?: '--',
                    'estado' => 'Registrado',
                ]),
            ],
            'cirugias' => [
                'titulo' => 'Cirugias',
                'icono' => 'lucide:scissors',
                'tipo' => 'lista',
                'items' => $paciente->cirugiasPrevias->take(4)->map(fn (CirugiaPrevia $item): array => [
                    'principal' => $item->denominacion ?: 'Cirugia sin denominacion',
                    'detalle' => trim(($item->periodo ?: 'Sin periodo').' '.($item->codigo_cpms ? 'CPMS '.$item->codigo_cpms : '')),
                ]),
            ],
            'hospitalizaciones' => [
                'titulo' => 'Hospitalizaciones',
                'icono' => 'lucide:hospital',
                'tipo' => 'lista',
                'items' => $paciente->hospitalizacionesPrevias->take(4)->map(fn (HospitalizacionPrevia $item): array => [
                    'principal' => $item->denominacion ?: ($item->diagnosticoCie10?->nombre ?? 'Hospitalizacion sin diagnostico'),
                    'detalle' => trim(($item->periodo ?: 'Sin periodo').' '.($item->codigo_ups ? 'UPS '.$item->codigo_ups : '')),
                ]),
            ],
            'medicamentos' => [
                'titulo' => 'Medicamentos habituales',
                'icono' => 'healthicons:medicines-outline',
                'tipo' => 'lista',
                'items' => $paciente->medicamentosHabituales->take(4)->map(fn (MedicamentoHabitual $item): array => [
                    'principal' => $item->medicacion ?: 'Medicamento sin nombre',
                    'detalle' => $item->periodo ? 'Periodo: '.$item->periodo : 'Sin periodo registrado',
                ]),
            ],
            'patologicos' => [
                'titulo' => 'Antecedentes Patologicos',
                'icono' => 'lucide:file-text',
                'tipo' => 'lista',
                'items' => $paciente->antecedentesPatologicos->take(4)->map(fn (AntecedentePatologico $item): array => [
                    'principal' => $item->diagnosticoCie10?->codigo
                        ? 'CIE10: '.$item->diagnosticoCie10->codigo
                        : ($item->denominacion ?: 'Antecedente sin denominacion'),
                    'detalle' => trim(($item->denominacion ?: $item->diagnosticoCie10?->nombre ?: 'Sin detalle').' '.($item->periodo ?: '')),
                ]),
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function tendenciasSignosVitales(): array
    {
        $episodios = $this->paciente->hcEmergencias()
            ->with('signosVitalesTriaje.signoVital')
            ->latest('fecha_hora_atencion')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $pas = collect();
        $pad = collect();
        $frecuencia = collect();
        $peso = collect();

        foreach ($episodios as $episodio) {
            $signos = $episodio->signosVitalesTriaje;
            $presionCompuesta = $this->presionCompuesta($signos);

            $pas->push($this->primerNumero($this->ultimoValorPorDescripcion($signos, ['pas', 'sistolica'])) ?? $presionCompuesta['pas']);
            $pad->push($this->primerNumero($this->ultimoValorPorDescripcion($signos, ['pad', 'diastolica'])) ?? $presionCompuesta['pad']);
            $frecuencia->push($this->primerNumero($this->ultimoValorPorDescripcion($signos, ['frecuencia cardiaca'])));
            $peso->push($this->primerNumero($this->ultimoValorPorDescripcion($signos, ['peso'])));
        }

        return [
            'presion' => [
                'valor' => ($pas->filter()->last() ?: '--').'/'.($pad->filter()->last() ?: '--'),
                'unidad' => '',
                'serie_principal' => $this->puntosSvg($pas),
                'serie_secundaria' => $this->puntosSvg($pad),
            ],
            'frecuencia' => [
                'valor' => $frecuencia->filter()->last() ?: '--',
                'unidad' => 'bpm',
                'serie_principal' => $this->puntosSvg($frecuencia),
            ],
            'peso' => [
                'valor' => $peso->filter()->last() ?: '--',
                'unidad' => 'kg',
                'serie_principal' => $this->puntosSvg($peso),
            ],
        ];
    }

    private function ultimoValorPorDescripcion(Collection $signos, array $terminos): ?string
    {
        $signo = $signos->first(function ($item) use ($terminos): bool {
            $descripcion = mb_strtolower($item->signoVital?->descripcion ?? '');

            return collect($terminos)->contains(fn (string $termino): bool => str_contains($descripcion, mb_strtolower($termino)));
        });

        return $signo?->valor;
    }

    /**
     * @return array{pas: ?float, pad: ?float}
     */
    private function presionCompuesta(Collection $signos): array
    {
        $valor = $this->ultimoValorPorDescripcion($signos, ['presion arterial']);

        if ($valor === null || ! str_contains($valor, '/')) {
            return ['pas' => null, 'pad' => null];
        }

        [$pas, $pad] = explode('/', $valor, 2);

        return [
            'pas' => $this->primerNumero($pas),
            'pad' => $this->primerNumero($pad),
        ];
    }

    private function primerNumero(?string $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        preg_match('/-?\d+(?:[\.,]\d+)?/', $valor, $coincidencias);

        return isset($coincidencias[0]) ? (float) str_replace(',', '.', $coincidencias[0]) : null;
    }

    private function puntosSvg(Collection $valores): string
    {
        $numeros = $valores->filter(fn ($valor): bool => is_numeric($valor))->values();

        if ($numeros->count() === 0) {
            return '0,46 32,44 64,45 96,43 128,44 160,42';
        }

        if ($numeros->count() === 1) {
            $numero = (float) $numeros->first();
            $numeros = collect([$numero, $numero, $numero, $numero, $numero, $numero]);
        }

        $minimo = (float) $numeros->min();
        $maximo = (float) $numeros->max();
        $rango = max($maximo - $minimo, 1);

        return $numeros
            ->map(function ($valor, int $indice) use ($numeros, $minimo, $rango): string {
                $x = $numeros->count() === 1 ? 0 : ($indice * (160 / max($numeros->count() - 1, 1)));
                $y = 52 - ((((float) $valor - $minimo) / $rango) * 36);

                return round($x, 1).','.round($y, 1);
            })
            ->implode(' ');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosDrawer(): array
    {
        if ($this->seccionActiva === null) {
            return ['abierto' => false];
        }

        $seccion = $this->secciones()[$this->seccionActiva];

        return [
            'abierto' => true,
            'clave' => $this->seccionActiva,
            'titulo' => $seccion['titulo'],
            'icono' => $seccion['icono'],
            'acento' => $seccion['acento'],
            'ancho' => in_array($this->seccionActiva, ['familiares', 'cirugias', 'hospitalizaciones', 'patologicos'], true) ? 'is-wide' : 'is-standard',
            'registros' => $this->registrosDeSeccion($this->seccionActiva),
            'opciones' => $this->opcionesFormulario(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function opcionesFormulario(): array
    {
        return [
            'alergias' => Alergia::query()->orderBy('nombre')->get(['id', 'nombre'])->mapWithKeys(fn (Alergia $alergia): array => [$alergia->id => $alergia->nombre])->all(),
            'diagnosticos' => DiagnosticoCie10::query()->orderBy('codigo')->limit(120)->get(['id', 'codigo', 'nombre'])->mapWithKeys(fn (DiagnosticoCie10 $diagnostico): array => [$diagnostico->id => trim($diagnostico->codigo.' - '.$diagnostico->nombre)])->all(),
            'tipos_ram' => collect(TipoReaccionAdversaMedicamento::cases())->mapWithKeys(fn ($caso): array => [$caso->value => $caso->value])->all(),
            'gravedades_ram' => collect(GravedadReaccionAdversaMedicamento::cases())->mapWithKeys(fn ($caso): array => [$caso->value => $caso->value])->all(),
            'parentescos' => collect(ParentescoFamiliar::cases())->mapWithKeys(fn ($caso): array => [$caso->value => $caso->value])->all(),
        ];
    }

    private function modeloDeSeccion(string $seccion): string
    {
        return $this->secciones()[$seccion]['modelo'];
    }

    /**
     * @return array<string, array{titulo: string, modelo: class-string<Model>, icono: string, acento: string}>
     */
    private function secciones(): array
    {
        return [
            'alergias' => ['titulo' => 'Alergias', 'modelo' => PacienteAlergia::class, 'icono' => 'healthicons:allergies-outline', 'acento' => '#dc2626'],
            'reacciones' => ['titulo' => 'Reacciones adversas', 'modelo' => ReaccionAdversaMedicamento::class, 'icono' => 'healthicons:medicines-outline', 'acento' => '#0077b6'],
            'familiares' => ['titulo' => 'Antecedentes familiares', 'modelo' => AntecedenteFamiliar::class, 'icono' => 'lucide:git-fork', 'acento' => '#0077b6'],
            'cirugias' => ['titulo' => 'Cirugias previas', 'modelo' => CirugiaPrevia::class, 'icono' => 'lucide:scissors', 'acento' => '#00b4d8'],
            'hospitalizaciones' => ['titulo' => 'Hospitalizaciones previas', 'modelo' => HospitalizacionPrevia::class, 'icono' => 'lucide:hospital', 'acento' => '#17a2b8'],
            'medicamentos' => ['titulo' => 'Medicamentos habituales', 'modelo' => MedicamentoHabitual::class, 'icono' => 'healthicons:medicines-outline', 'acento' => '#10669a'],
            'patologicos' => ['titulo' => 'Antecedentes patologicos', 'modelo' => AntecedentePatologico::class, 'icono' => 'lucide:file-text', 'acento' => '#415a77'],
        ];
    }

    private function buscarRegistroDeSeccion(string $seccion, int $registroId): Model
    {
        return $this->modeloDeSeccion($seccion)::query()
            ->where('paciente_id', $this->paciente->id)
            ->findOrFail($registroId);
    }

    private function registrosDeSeccion(string $seccion): Collection
    {
        $consulta = $this->modeloDeSeccion($seccion)::query()
            ->where('paciente_id', $this->paciente->id)
            ->latest();

        if (in_array($seccion, ['familiares', 'hospitalizaciones', 'patologicos'], true)) {
            $consulta->with('diagnosticoCie10');
        }

        if ($seccion === 'alergias') {
            $consulta->with('alergia');
        }

        return $consulta->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function formularioVacio(string $seccion): array
    {
        return match ($seccion) {
            'alergias' => ['alergia_id' => '', 'agente_causal' => '', 'alergeno' => '', 'periodo' => ''],
            'reacciones' => ['descripcion' => '', 'tipo' => '', 'gravedad' => '', 'dci_medicamento' => '', 'periodo' => ''],
            'familiares' => ['parentesco' => '', 'diagnostico_cie10_id' => '', 'denominacion' => '', 'periodo' => ''],
            'cirugias' => ['codigo_cpms' => '', 'denominacion' => '', 'periodo' => ''],
            'hospitalizaciones' => ['codigo_ups' => '', 'diagnostico_cie10_id' => '', 'denominacion' => '', 'periodo' => ''],
            'medicamentos' => ['medicacion' => '', 'periodo' => ''],
            'patologicos' => ['diagnostico_cie10_id' => '', 'denominacion' => '', 'origen' => '', 'periodo' => ''],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(string $seccion, Model $registro): array
    {
        return match ($seccion) {
            'alergias' => [
                'alergia_id' => (string) $registro->alergia_id,
                'agente_causal' => $registro->agente_causal,
                'alergeno' => $registro->alergeno,
                'periodo' => $registro->periodo,
            ],
            'reacciones' => [
                'descripcion' => $registro->descripcion,
                'tipo' => $this->valorEnum($registro->tipo),
                'gravedad' => $this->valorEnum($registro->gravedad),
                'dci_medicamento' => $registro->dci_medicamento,
                'periodo' => $registro->periodo,
            ],
            'familiares' => [
                'parentesco' => $this->valorEnum($registro->parentesco),
                'diagnostico_cie10_id' => (string) $registro->diagnostico_cie10_id,
                'denominacion' => $registro->denominacion,
                'periodo' => $registro->periodo,
            ],
            'cirugias' => [
                'codigo_cpms' => $registro->codigo_cpms,
                'denominacion' => $registro->denominacion,
                'periodo' => $registro->periodo,
            ],
            'hospitalizaciones' => [
                'codigo_ups' => $registro->codigo_ups,
                'diagnostico_cie10_id' => (string) $registro->diagnostico_cie10_id,
                'denominacion' => $registro->denominacion,
                'periodo' => $registro->periodo,
            ],
            'medicamentos' => [
                'medicacion' => $registro->medicacion,
                'periodo' => $registro->periodo,
            ],
            'patologicos' => [
                'diagnostico_cie10_id' => (string) $registro->diagnostico_cie10_id,
                'denominacion' => $registro->denominacion,
                'origen' => $registro->origen,
                'periodo' => $registro->periodo,
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasFormulario(string $seccion): array
    {
        return match ($seccion) {
            'alergias' => [
                'formulario.alergia_id' => ['required', Rule::exists('alergias', 'id')],
                'formulario.agente_causal' => ['nullable', 'string', 'max:255'],
                'formulario.alergeno' => ['nullable', 'string', 'max:255'],
                'formulario.periodo' => ['nullable', 'string', 'max:20'],
            ],
            'reacciones' => [
                'formulario.descripcion' => ['required', 'string', 'max:1000'],
                'formulario.tipo' => ['nullable', Rule::enum(TipoReaccionAdversaMedicamento::class)],
                'formulario.gravedad' => ['nullable', Rule::enum(GravedadReaccionAdversaMedicamento::class)],
                'formulario.dci_medicamento' => ['nullable', 'string', 'max:255'],
                'formulario.periodo' => ['nullable', 'string', 'max:20'],
            ],
            'familiares' => [
                'formulario.parentesco' => ['nullable', Rule::enum(ParentescoFamiliar::class)],
                'formulario.diagnostico_cie10_id' => ['nullable', Rule::exists('diagnosticos_cie10', 'id')],
                'formulario.denominacion' => ['nullable', 'string', 'max:255'],
                'formulario.periodo' => ['nullable', 'string', 'max:20'],
            ],
            'cirugias' => [
                'formulario.codigo_cpms' => ['nullable', 'string', 'max:30'],
                'formulario.denominacion' => ['required', 'string', 'max:255'],
                'formulario.periodo' => ['nullable', 'string', 'max:20'],
            ],
            'hospitalizaciones' => [
                'formulario.codigo_ups' => ['nullable', 'string', 'max:30'],
                'formulario.diagnostico_cie10_id' => ['nullable', Rule::exists('diagnosticos_cie10', 'id')],
                'formulario.denominacion' => ['nullable', 'string', 'max:255'],
                'formulario.periodo' => ['nullable', 'string', 'max:20'],
            ],
            'medicamentos' => [
                'formulario.medicacion' => ['required', 'string', 'max:255'],
                'formulario.periodo' => ['nullable', 'string', 'max:20'],
            ],
            'patologicos' => [
                'formulario.diagnostico_cie10_id' => ['nullable', Rule::exists('diagnosticos_cie10', 'id')],
                'formulario.denominacion' => ['nullable', 'string', 'max:255'],
                'formulario.origen' => ['nullable', 'string', 'max:50'],
                'formulario.periodo' => ['nullable', 'string', 'max:20'],
            ],
            default => [],
        };
    }

    private function valorEnum(mixed $valor): string
    {
        return $valor instanceof \BackedEnum ? $valor->value : (string) ($valor ?? '');
    }
}
