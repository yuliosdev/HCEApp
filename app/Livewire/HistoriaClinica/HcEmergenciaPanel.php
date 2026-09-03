<?php

namespace App\Livewire\HistoriaClinica;

use App\Enums\EstadoAtencion;
use App\Enums\EstadoSolicitudExamen;
use App\Enums\FuenteAtencion;
use App\Enums\PrioridadOrden;
use App\Enums\PrioridadSolicitudExamen;
use App\Enums\SiNo;
use App\Enums\TopicoAtencion;
use App\Enums\ClasificacionDiagnosticoEgreso;
use App\Enums\TipoDiagnostico;
use App\Enums\UnidadTiempoEnfermedad;
use App\Models\DiagnosticoCie10;
use App\Models\EgresoDiagnostico;
use App\Models\EnfermeriaNota;
use App\Models\Examen;
use App\Models\Evolucion;
use App\Models\Glasgow;
use App\Models\HcEmergencia;
use App\Models\HcReferencia;
use App\Models\IngresoDiagnostico;
use App\Models\Interconsulta;
use App\Models\Medicamento;
use App\Models\Orden;
use App\Models\Paciente;
use App\Models\Prescripcion;
use App\Models\Prioridad;
use App\Models\Procedimiento;
use App\Models\ProcedimientoRealizado;
use App\Models\ProfesionalSalud;
use App\Models\SignoVital;
use App\Models\SolicitudExamen;
use App\Models\TipoOrden;
use App\Models\Transferencia;
use App\Models\TriajeSignoVital;
use App\Models\Ups;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
        'fecha_hora_triaje' => null,
        'profesional_triaje_id' => null,
        'prioridad_id' => null,
        'destino_topico' => null,
        'tiempo_espera_calculado' => null,
        'reevaluacion_programada' => null,
        'triaje' => false,
        'fecha_hora_anamnesis' => null,
        'tiempo_enfermedad' => null,
        'unidad_tiempo_enfermedad' => null,
        'signos_sintomas' => null,
        'desarrollo_cronologico' => null,
        'eg_ocular' => null,
        'eg_verbal' => null,
        'eg_motora' => null,
        'eg_total' => null,
        'estado_general' => null,
        'hidratacion' => null,
        'nutricion' => null,
        'estado_conciencia' => null,
        'piel_anexos' => null,
        'cabeza_cuello' => null,
        'torax_respiratorio' => null,
        'cardiovascular' => null,
        'abdomen' => null,
        'neurologico' => null,
        'musculoesqueletico_extremidades' => null,
        'genitourinario_ginecoobstetrico' => null,
        'otros' => null,
        'ingresos' => null,
        'egresos' => null,
        'balance' => null,
        'en_shock_trauma' => 'No',
        'shock_trauma_fecha_hora' => null,
        'shock_trauma_responsable' => null,
        'shock_trauma_resultado' => null,
        'shock_trauma_destino' => null,
        'registro_especializado' => null,
        'estado_atencion' => 'Abierta',
        'profesional_salud_id' => null,
    ];

    public string $profesionalTriajeBusqueda = '';

    public string $profesionalEvolucionBusqueda = '';

    public string $profesionalEnfermeriaBusqueda = '';

    public string $profesionalInterconsultaBusqueda = '';

    public string $shockTraumaResponsableBusqueda = '';

    public string $transferenciaResponsableBusqueda = '';

    public bool $formularioSignoVitalAbierto = false;

    public ?int $signoVitalEditandoId = null;

    /** @var array<string, mixed> */
    public array $signoVitalForm = [
        'signo_vital_id' => '',
        'valor' => '',
    ];

    public bool $formularioDiagnosticoIngresoAbierto = false;

    public ?int $diagnosticoIngresoEditandoId = null;

    public string $diagnosticoIngresoCie10Busqueda = '';

    /** @var array<string, mixed> */
    public array $diagnosticoIngresoForm = [
        'diagnostico_cie10_id' => '',
        'tipo_diagnostico' => '',
        'observacion' => '',
    ];

    public bool $formularioEvolucionAbierto = false;

    public ?int $evolucionEditandoId = null;

    /** @var array<string, mixed> */
    public array $evolucionForm = [
        'fecha_hora' => '',
        'profesional_salud_id' => '',
        'detalle_clinico' => '',
    ];

    public bool $formularioDiagnosticoEgresoAbierto = false;

    public ?int $diagnosticoEgresoEditandoId = null;

    public string $diagnosticoEgresoCie10Busqueda = '';

    /** @var array<string, mixed> */
    public array $diagnosticoEgresoForm = [
        'diagnostico_cie10_id' => '',
        'clasificacion' => '',
        'tipo_diagnostico' => '',
        'observaciones' => '',
    ];

    public bool $formularioOrdenAbierto = false;

    public ?int $ordenEditandoId = null;

    /** @var array<string, mixed> */
    public array $ordenForm = [
        'tipo_orden_id' => '',
        'detalle' => '',
        'prioridad' => '',
        'fecha_hora_solicitud' => '',
        'resultado' => '',
        'profesional_salud_id' => '',
    ];

    public bool $formularioSolicitudExamenAbierto = false;

    public ?int $solicitudExamenEditandoId = null;

    public string $solicitudExamenBusqueda = '';

    /** @var array<string, mixed> */
    public array $solicitudExamenForm = [
        'examen_id' => '',
        'prioridad' => '',
        'fecha_hora_solicitud' => '',
        'resultado' => '',
        'estado' => '',
        'profesional_salud_id' => '',
    ];

    public bool $formularioPrescripcionAbierto = false;

    public ?int $prescripcionEditandoId = null;

    public string $prescripcionMedicamentoBusqueda = '';

    /** @var array<string, mixed> */
    public array $prescripcionForm = [
        'medicamento_id' => '',
        'medicamento' => '',
        'presentacion' => '',
        'dosis' => '',
        'via' => '',
        'duracion' => '',
        'cantidad' => '',
        'fecha_hora' => '',
        'profesional_salud_id' => '',
    ];

    public bool $formularioProcedimientoAbierto = false;

    public ?int $procedimientoEditandoId = null;

    public string $procedimientoBusqueda = '';

    /** @var array<string, mixed> */
    public array $procedimientoForm = [
        'procedimiento_id' => '',
        'denominacion' => '',
        'sesiones' => '',
        'fecha_hora' => '',
    ];

    public bool $formularioNotaEnfermeriaAbierto = false;

    public ?int $notaEnfermeriaEditandoId = null;

    public bool $notaEnfermeriaSoloLectura = false;

    /** @var array<string, mixed> */
    public array $notaEnfermeriaForm = [
        'fecha_hora' => '',
        'profesional_salud_id' => '',
        'nota' => '',
        'firma' => '',
    ];

    public bool $formularioInterconsultaAbierto = false;

    public ?int $interconsultaEditandoId = null;

    public bool $interconsultaSoloLectura = false;

    /** @var array<string, mixed> */
    public array $interconsultaForm = [
        'fecha_hora' => '',
        'profesional_salud_id' => '',
        'nota' => '',
    ];

    public bool $formularioTransferenciaAbierto = false;

    public ?int $transferenciaEditandoId = null;

    public bool $transferenciaSoloLectura = false;

    /** @var array<string, mixed> */
    public array $transferenciaForm = [
        'fecha_hora' => '',
        'origen' => '',
        'destino' => '',
        'motivo' => '',
        'responsable' => '',
    ];

    public bool $formularioReferenciaAbierto = false;

    public ?int $referenciaEditandoId = null;

    public bool $referenciaSoloLectura = false;

    /** @var array<string, mixed> */
    public array $referenciaForm = [
        'ipress_origen' => '',
        'ups_origen_id' => '',
        'ipress_referencia' => '',
        'ups_destino_id' => '',
        'motivo_referencia' => '',
        'fecha_hora_solicitud' => '',
        'respuesta_solicitud' => '',
        'fecha_hora_respuesta' => '',
    ];

    public bool $panelErroresAbierto = false;

    public bool $panelConfirmacionGuardadoAbierto = false;

    /** @var array<int, string> */
    public array $erroresGuardado = [];

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

        $this->normalizarFormularioAntesDeValidar();
        $this->recalcularGlasgow();

        $datos = $this->validarFormularioParaGuardado();

        if ($datos === null) {
            return;
        }

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
            'fecha_hora_triaje' => $datos['fecha_hora_triaje'],
            'profesional_triaje_id' => $datos['profesional_triaje_id'],
            'destino_topico' => $datos['destino_topico'],
            'tiempo_espera_calculado' => $datos['tiempo_espera_calculado'],
            'reevaluacion_programada' => $datos['reevaluacion_programada'],
            'triaje' => (bool) $datos['triaje'],
            'fecha_hora_anamnesis' => $datos['fecha_hora_anamnesis'],
            'tiempo_enfermedad' => $datos['tiempo_enfermedad'],
            'unidad_tiempo_enfermedad' => $datos['unidad_tiempo_enfermedad'],
            'signos_sintomas' => $datos['signos_sintomas'],
            'desarrollo_cronologico' => $datos['desarrollo_cronologico'],
            'eg_ocular' => $datos['eg_ocular'],
            'eg_verbal' => $datos['eg_verbal'],
            'eg_motora' => $datos['eg_motora'],
            'eg_total' => $datos['eg_total'],
            'estado_general' => $datos['estado_general'],
            'hidratacion' => $datos['hidratacion'],
            'nutricion' => $datos['nutricion'],
            'estado_conciencia' => $datos['estado_conciencia'],
            'piel_anexos' => $datos['piel_anexos'],
            'cabeza_cuello' => $datos['cabeza_cuello'],
            'torax_respiratorio' => $datos['torax_respiratorio'],
            'cardiovascular' => $datos['cardiovascular'],
            'abdomen' => $datos['abdomen'],
            'neurologico' => $datos['neurologico'],
            'musculoesqueletico_extremidades' => $datos['musculoesqueletico_extremidades'],
            'genitourinario_ginecoobstetrico' => $datos['genitourinario_ginecoobstetrico'],
            'otros' => $datos['otros'],
            'ingresos' => $datos['ingresos'],
            'egresos' => $datos['egresos'],
            'balance' => $datos['balance'],
            'en_shock_trauma' => $datos['en_shock_trauma'] ?: SiNo::No->value,
            'shock_trauma_fecha_hora' => $datos['shock_trauma_fecha_hora'],
            'shock_trauma_responsable' => $datos['shock_trauma_responsable'],
            'shock_trauma_resultado' => $datos['shock_trauma_resultado'],
            'shock_trauma_destino' => $datos['shock_trauma_destino'],
            'registro_especializado' => $datos['registro_especializado'],
            'paciente_id' => $this->paciente->id,
            'sin_identificacion' => SiNo::No->value,
            'prioridad_id' => $datos['prioridad_id'],
            'color_triaje' => $this->colorPrioridad($datos['prioridad_id']),
            'profesional_salud_id' => $datos['profesional_salud_id'],
            'estado_atencion' => $datos['estado_atencion'] ?: EstadoAtencion::Abierta->value,
            'actualizado_por' => $usuarioId,
        ])->save();

        $this->atencion = $atencion->fresh(['paciente', 'profesionalResponsable.especialidad', 'prioridad']);
        $this->modo = 'editar';
        $this->cargarAtencion($this->atencion);

        $this->panelErroresAbierto = false;
        $this->panelConfirmacionGuardadoAbierto = true;
        $this->erroresGuardado = [];

        flash()->success('Historia clinica de emergencia guardada correctamente.', [], 'Historia clinica');
    }

    public function seguirEditandoHistoria(): void
    {
        $this->panelConfirmacionGuardadoAbierto = false;

        if ($this->atencion === null) {
            return;
        }

        $this->redirectRoute('historia-clinica.atenciones-paciente.emergencia.editar', [
            'paciente' => $this->paciente,
            'atencion' => $this->atencion,
        ]);
    }

    public function volverAlHistorialPaciente(): void
    {
        $this->panelConfirmacionGuardadoAbierto = false;

        $this->redirectRoute('historia-clinica.atenciones-paciente.historia', $this->paciente);
    }

    public function cerrarPanelErrores(): void
    {
        $this->panelErroresAbierto = false;
    }

    public function limpiarErrorDeCampo(string $campo): void
    {
        $this->resetValidation("form.{$campo}");
        $this->panelErroresAbierto = false;
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
            'prioridades' => Prioridad::query()->orderBy('orden')->get(['id', 'prioridad', 'color', 'orden', 'tiempo_atencion']),
            'signos' => $this->signosResumen(),
            'tabs' => $this->tabs(),
            'topicos' => TopicoAtencion::cases(),
            'fuentesAtencion' => FuenteAtencion::cases(),
            'unidadesTiempoEnfermedad' => UnidadTiempoEnfermedad::cases(),
            'eventosEpisodio' => $this->eventosEpisodio(),
            'profesionalesTriaje' => $this->profesionalesTriaje(),
            'catalogoSignosVitales' => SignoVital::query()->orderBy('descripcion')->get(['id', 'descripcion', 'unidad', 'rango_inicial', 'rango_final']),
            'signosVitalesTriaje' => $this->signosVitalesTriaje(),
            'glasgowOpciones' => $this->glasgowOpciones(),
            'glasgowResumen' => $this->glasgowResumen(),
            'glasgowClasificacion' => $this->clasificacionGlasgow(),
            'diagnosticosCie10Ingreso' => $this->diagnosticosCie10Ingreso(),
            'diagnosticosCie10Egreso' => $this->diagnosticosCie10Egreso(),
            'tiposDiagnostico' => TipoDiagnostico::cases(),
            'clasificacionesDiagnosticoEgreso' => ClasificacionDiagnosticoEgreso::cases(),
            'profesionalesEvolucion' => $this->profesionalesEvolucion(),
            'diagnosticosIngreso' => $this->diagnosticosIngreso(),
            'evolucionesClinicas' => $this->evolucionesClinicas(),
            'diagnosticosEgreso' => $this->diagnosticosEgreso(),
            'catalogoTiposOrdenes' => TipoOrden::query()->orderBy('descripcion')->get(['id', 'descripcion']),
            'examenesLaboratorio' => $this->examenesLaboratorio(),
            'prioridadesOrden' => PrioridadOrden::cases(),
            'prioridadesSolicitudExamen' => PrioridadSolicitudExamen::cases(),
            'estadosSolicitudExamen' => EstadoSolicitudExamen::cases(),
            'profesionalesCatalogo' => ProfesionalSalud::query()->orderBy('nombres')->limit(250)->get(['id', 'nombres', 'cmp']),
            'profesionalesEnfermeria' => $this->profesionalesEnfermeria(),
            'profesionalesInterconsulta' => $this->profesionalesInterconsulta(),
            'profesionalesShockTrauma' => $this->profesionalesPorBusqueda($this->shockTraumaResponsableBusqueda),
            'profesionalesTransferencia' => $this->profesionalesPorBusqueda($this->transferenciaResponsableBusqueda),
            'catalogoUps' => Ups::query()->where('estado', true)->orderBy('denominacion')->get(['id', 'codigo', 'denominacion']),
            'resultadosShockTrauma' => $this->resultadosShockTrauma(),
            'destinosShockTrauma' => $this->destinosShockTrauma(),
            'estadosRespuestaReferencia' => $this->estadosRespuestaReferencia(),
            'ordenesClinicas' => $this->ordenesClinicas(),
            'solicitudesExamenes' => $this->solicitudesExamenes(),
            'medicamentosPrescripcion' => $this->medicamentosPrescripcion(),
            'prescripcionesMedicamentos' => $this->prescripcionesMedicamentos(),
            'procedimientosCatalogo' => $this->procedimientosCatalogo(),
            'procedimientosRealizados' => $this->procedimientosRealizados(),
            'notasEnfermeria' => $this->notasEnfermeria(),
            'balanceHidrico' => $this->balanceHidrico(),
            'interconsultasClinicas' => $this->interconsultasClinicas(),
            'transferenciasClinicas' => $this->transferenciasClinicas(),
            'referenciasClinicas' => $this->referenciasClinicas(),
            'soloLectura' => $this->modo === 'ver',
            'esAtencionAbierta' => $this->form['estado_atencion'] === EstadoAtencion::Abierta->value,
        ]);
    }

    public function updatedFormPrioridadId(): void
    {
        $this->normalizarCampoPrioridad();
        $this->resetValidation('form.prioridad_id');
        $this->panelErroresAbierto = false;
        $this->recalcularTiemposTriaje();
    }

    public function updatedFormFechaHoraTriaje(): void
    {
        $this->recalcularTiemposTriaje();
    }

    public function updatedFormIngresos(): void
    {
        $this->recalcularBalanceHidrico();
    }

    public function updatedFormEgresos(): void
    {
        $this->recalcularBalanceHidrico();
    }

    public function updatedFormEgOcular(): void
    {
        $this->normalizarCampoId('eg_ocular');
        $this->recalcularGlasgow();
    }

    public function updatedFormEgVerbal(): void
    {
        $this->normalizarCampoId('eg_verbal');
        $this->recalcularGlasgow();
    }

    public function updatedFormEgMotora(): void
    {
        $this->normalizarCampoId('eg_motora');
        $this->recalcularGlasgow();
    }

    public function updatedProfesionalTriajeBusqueda(): void
    {
        if ($this->profesionalTriajeBusqueda !== $this->etiquetaProfesionalId($this->form['profesional_triaje_id'])) {
            $this->form['profesional_triaje_id'] = null;
        }
    }

    public function updatedProfesionalEvolucionBusqueda(): void
    {
        if ($this->profesionalEvolucionBusqueda !== $this->etiquetaProfesionalEvolucionId($this->evolucionForm['profesional_salud_id'] ?: null)) {
            $this->evolucionForm['profesional_salud_id'] = null;
        }
    }

    public function updatedProfesionalEnfermeriaBusqueda(): void
    {
        if ($this->profesionalEnfermeriaBusqueda !== $this->etiquetaProfesionalEvolucionId($this->notaEnfermeriaForm['profesional_salud_id'] ?: null)) {
            $this->notaEnfermeriaForm['profesional_salud_id'] = null;
        }
    }

    public function updatedProfesionalInterconsultaBusqueda(): void
    {
        if ($this->profesionalInterconsultaBusqueda !== $this->etiquetaProfesionalEvolucionId($this->interconsultaForm['profesional_salud_id'] ?: null)) {
            $this->interconsultaForm['profesional_salud_id'] = null;
        }
    }

    public function updatedShockTraumaResponsableBusqueda(): void
    {
        $this->form['shock_trauma_responsable'] = $this->shockTraumaResponsableBusqueda;
    }

    public function updatedTransferenciaResponsableBusqueda(): void
    {
        $this->transferenciaForm['responsable'] = $this->transferenciaResponsableBusqueda;
    }

    public function updatedDiagnosticoIngresoCie10Busqueda(): void
    {
        if ($this->diagnosticoIngresoCie10Busqueda !== $this->etiquetaDiagnosticoCie10Id($this->diagnosticoIngresoForm['diagnostico_cie10_id'] ?: null)) {
            $this->diagnosticoIngresoForm['diagnostico_cie10_id'] = null;
        }
    }

    public function updatedDiagnosticoEgresoCie10Busqueda(): void
    {
        if ($this->diagnosticoEgresoCie10Busqueda !== $this->etiquetaDiagnosticoCie10Id($this->diagnosticoEgresoForm['diagnostico_cie10_id'] ?: null)) {
            $this->diagnosticoEgresoForm['diagnostico_cie10_id'] = null;
        }
    }

    public function updatedSolicitudExamenBusqueda(): void
    {
        if ($this->solicitudExamenBusqueda !== $this->etiquetaExamenId($this->solicitudExamenForm['examen_id'] ?: null)) {
            $this->solicitudExamenForm['examen_id'] = null;
        }
    }

    public function updatedPrescripcionMedicamentoBusqueda(): void
    {
        $this->prescripcionForm['medicamento'] = $this->prescripcionMedicamentoBusqueda;

        if ($this->prescripcionMedicamentoBusqueda !== $this->etiquetaMedicamentoId($this->prescripcionForm['medicamento_id'] ?: null)) {
            $this->prescripcionForm['medicamento_id'] = null;
        }
    }

    public function updatedProcedimientoBusqueda(): void
    {
        $this->procedimientoForm['denominacion'] = $this->procedimientoBusqueda;

        if ($this->procedimientoBusqueda !== $this->etiquetaProcedimientoId($this->procedimientoForm['procedimiento_id'] ?: null)) {
            $this->procedimientoForm['procedimiento_id'] = null;
        }
    }

    public function seleccionarProfesionalTriaje(int $profesionalId): void
    {
        abort_if($this->modo === 'ver', 403);

        $profesional = ProfesionalSalud::query()->findOrFail($profesionalId);

        $this->form['profesional_triaje_id'] = $profesional->id;
        $this->profesionalTriajeBusqueda = $this->etiquetaProfesional($profesional);
    }

    public function seleccionarProfesionalEvolucion(int $profesionalId): void
    {
        abort_if($this->modo === 'ver', 403);

        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->findOrFail($profesionalId);

        $this->evolucionForm['profesional_salud_id'] = $profesional->id;
        $this->profesionalEvolucionBusqueda = $this->etiquetaProfesionalEvolucion($profesional);
        $this->resetValidation('evolucionForm.profesional_salud_id');
    }

    public function seleccionarProfesionalEnfermeria(int $profesionalId): void
    {
        abort_if($this->modo === 'ver' || $this->notaEnfermeriaSoloLectura, 403);

        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->findOrFail($profesionalId);

        $this->notaEnfermeriaForm['profesional_salud_id'] = $profesional->id;
        $this->profesionalEnfermeriaBusqueda = $this->etiquetaProfesionalEvolucion($profesional);
        $this->resetValidation('notaEnfermeriaForm.profesional_salud_id');
    }

    public function seleccionarProfesionalInterconsulta(int $profesionalId): void
    {
        abort_if($this->modo === 'ver' || $this->interconsultaSoloLectura, 403);

        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->findOrFail($profesionalId);

        $this->interconsultaForm['profesional_salud_id'] = $profesional->id;
        $this->profesionalInterconsultaBusqueda = $this->etiquetaProfesionalEvolucion($profesional);
        $this->resetValidation('interconsultaForm.profesional_salud_id');
    }

    public function seleccionarResponsableShockTrauma(int $profesionalId): void
    {
        abort_if($this->modo === 'ver', 403);

        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->findOrFail($profesionalId);

        $this->shockTraumaResponsableBusqueda = $this->etiquetaProfesionalEvolucion($profesional);
        $this->form['shock_trauma_responsable'] = $this->shockTraumaResponsableBusqueda;
        $this->resetValidation('form.shock_trauma_responsable');
    }

    public function seleccionarResponsableTransferencia(int $profesionalId): void
    {
        abort_if($this->modo === 'ver' || $this->transferenciaSoloLectura, 403);

        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->findOrFail($profesionalId);

        $this->transferenciaResponsableBusqueda = $this->etiquetaProfesionalEvolucion($profesional);
        $this->transferenciaForm['responsable'] = $this->transferenciaResponsableBusqueda;
        $this->resetValidation('transferenciaForm.responsable');
    }

    public function seleccionarDiagnosticoIngresoCie10(int $diagnosticoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $diagnostico = DiagnosticoCie10::query()->findOrFail($diagnosticoId);

        $this->diagnosticoIngresoForm['diagnostico_cie10_id'] = $diagnostico->id;
        $this->diagnosticoIngresoCie10Busqueda = $this->etiquetaDiagnosticoCie10($diagnostico);
        $this->resetValidation('diagnosticoIngresoForm.diagnostico_cie10_id');
    }

    public function seleccionarDiagnosticoEgresoCie10(int $diagnosticoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $diagnostico = DiagnosticoCie10::query()->findOrFail($diagnosticoId);

        $this->diagnosticoEgresoForm['diagnostico_cie10_id'] = $diagnostico->id;
        $this->diagnosticoEgresoCie10Busqueda = $this->etiquetaDiagnosticoCie10($diagnostico);
        $this->resetValidation('diagnosticoEgresoForm.diagnostico_cie10_id');
    }

    public function seleccionarExamenLaboratorio(int $examenId): void
    {
        abort_if($this->modo === 'ver', 403);

        $examen = Examen::query()->findOrFail($examenId);

        $this->solicitudExamenForm['examen_id'] = $examen->id;
        $this->solicitudExamenBusqueda = $this->etiquetaExamen($examen);
        $this->resetValidation('solicitudExamenForm.examen_id');
    }

    public function seleccionarMedicamentoPrescripcion(int $medicamentoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $medicamento = Medicamento::query()->findOrFail($medicamentoId);

        $this->prescripcionForm['medicamento_id'] = $medicamento->id;
        $this->prescripcionForm['medicamento'] = $medicamento->descripcion;
        $this->prescripcionForm['presentacion'] = $medicamento->presentacion;
        $this->prescripcionMedicamentoBusqueda = $medicamento->descripcion;
        $this->resetValidation(['prescripcionForm.medicamento_id', 'prescripcionForm.medicamento', 'prescripcionForm.presentacion']);
    }

    public function seleccionarProcedimientoRealizado(int $procedimientoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $procedimiento = Procedimiento::query()->findOrFail($procedimientoId);

        $this->procedimientoForm['procedimiento_id'] = $procedimiento->id;
        $this->procedimientoForm['denominacion'] = $procedimiento->denominacion;
        $this->procedimientoBusqueda = $procedimiento->denominacion;
        $this->resetValidation(['procedimientoForm.procedimiento_id', 'procedimientoForm.denominacion']);
    }

    public function abrirNuevoSignoVital(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->signoVitalEditandoId = null;
        $this->signoVitalForm = [
            'signo_vital_id' => '',
            'valor' => '',
        ];
        $this->formularioSignoVitalAbierto = true;
        $this->resetValidation(['signoVitalForm.signo_vital_id', 'signoVitalForm.valor']);
    }

    public function editarSignoVital(int $signoVitalTriajeId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaSignoVitalTriaje($signoVitalTriajeId);

        $this->signoVitalEditandoId = $registro->id;
        $this->signoVitalForm = [
            'signo_vital_id' => $registro->signo_vital_id,
            'valor' => $registro->valor,
        ];
        $this->formularioSignoVitalAbierto = true;
        $this->resetValidation(['signoVitalForm.signo_vital_id', 'signoVitalForm.valor']);
    }

    public function guardarSignoVital(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar signos vitales.', [], 'Triaje');

            return;
        }

        $datos = $this->validate([
            'signoVitalForm.signo_vital_id' => ['required', 'integer', Rule::exists('signos_vitales', 'id')->whereNull('deleted_at')],
            'signoVitalForm.valor' => ['required', 'string', 'max:100'],
        ], [], [
            'signoVitalForm.signo_vital_id' => 'funcion vital',
            'signoVitalForm.valor' => 'valor',
        ])['signoVitalForm'];

        $registro = $this->signoVitalEditandoId
            ? $this->consultaSignoVitalTriaje($this->signoVitalEditandoId)
            : new TriajeSignoVital([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'signo_vital_id' => $datos['signo_vital_id'],
            'valor' => $datos['valor'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioSignoVitalAbierto = false;
        $this->signoVitalEditandoId = null;
        $this->signoVitalForm = ['signo_vital_id' => '', 'valor' => ''];
        $this->atencion = $this->atencion->fresh(['paciente', 'profesionalResponsable.especialidad', 'profesionalTriaje', 'prioridad']);

        flash()->success('Signo vital registrado correctamente.', [], 'Triaje');
    }

    public function eliminarSignoVital(int $signoVitalTriajeId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaSignoVitalTriaje($signoVitalTriajeId)->delete();

        $this->formularioSignoVitalAbierto = false;
        $this->signoVitalEditandoId = null;

        flash()->success('Signo vital eliminado correctamente.', [], 'Triaje');
    }

    public function abrirNuevoDiagnosticoIngreso(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->diagnosticoIngresoEditandoId = null;
        $this->diagnosticoIngresoForm = [
            'diagnostico_cie10_id' => '',
            'tipo_diagnostico' => TipoDiagnostico::Presuntivo->value,
            'observacion' => '',
        ];
        $this->diagnosticoIngresoCie10Busqueda = '';
        $this->formularioDiagnosticoIngresoAbierto = true;
        $this->resetValidation(['diagnosticoIngresoForm.diagnostico_cie10_id', 'diagnosticoIngresoForm.tipo_diagnostico', 'diagnosticoIngresoForm.observacion']);
    }

    public function editarDiagnosticoIngreso(int $diagnosticoIngresoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaDiagnosticoIngreso($diagnosticoIngresoId);

        $this->diagnosticoIngresoEditandoId = $registro->id;
        $this->diagnosticoIngresoForm = [
            'diagnostico_cie10_id' => $registro->diagnostico_cie10_id,
            'tipo_diagnostico' => $this->valorEnum($registro->tipo_diagnostico),
            'observacion' => $registro->observacion,
        ];
        $this->diagnosticoIngresoCie10Busqueda = $this->etiquetaDiagnosticoCie10($registro->diagnosticoCie10);
        $this->formularioDiagnosticoIngresoAbierto = true;
        $this->resetValidation(['diagnosticoIngresoForm.diagnostico_cie10_id', 'diagnosticoIngresoForm.tipo_diagnostico', 'diagnosticoIngresoForm.observacion']);
    }

    public function guardarDiagnosticoIngreso(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar diagnosticos de ingreso.', [], 'Diagnosticos');

            return;
        }

        $datos = $this->validate([
            'diagnosticoIngresoForm.diagnostico_cie10_id' => ['required', 'integer', Rule::exists('diagnosticos_cie10', 'id')->whereNull('deleted_at')],
            'diagnosticoIngresoForm.tipo_diagnostico' => ['required', Rule::in(array_map(fn (TipoDiagnostico $tipo): string => $tipo->value, TipoDiagnostico::cases()))],
            'diagnosticoIngresoForm.observacion' => ['nullable', 'string'],
        ], [], [
            'diagnosticoIngresoForm.diagnostico_cie10_id' => 'diagnostico CIE10',
            'diagnosticoIngresoForm.tipo_diagnostico' => 'tipo de diagnostico',
            'diagnosticoIngresoForm.observacion' => 'observacion',
        ])['diagnosticoIngresoForm'];

        $registro = $this->diagnosticoIngresoEditandoId
            ? $this->consultaDiagnosticoIngreso($this->diagnosticoIngresoEditandoId)
            : new IngresoDiagnostico([
                'hc_emergencia_id' => $this->atencion->id,
                'fecha_hora' => now(),
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'diagnostico_cie10_id' => $datos['diagnostico_cie10_id'],
            'tipo_diagnostico' => $datos['tipo_diagnostico'],
            'observacion' => $datos['observacion'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioDiagnosticoIngresoAbierto = false;
        $this->diagnosticoIngresoEditandoId = null;
        $this->diagnosticoIngresoForm = ['diagnostico_cie10_id' => '', 'tipo_diagnostico' => '', 'observacion' => ''];
        $this->diagnosticoIngresoCie10Busqueda = '';

        flash()->success('Diagnostico de ingreso registrado correctamente.', [], 'Diagnosticos');
    }

    public function eliminarDiagnosticoIngreso(int $diagnosticoIngresoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaDiagnosticoIngreso($diagnosticoIngresoId)->delete();

        $this->formularioDiagnosticoIngresoAbierto = false;
        $this->diagnosticoIngresoEditandoId = null;

        flash()->success('Diagnostico de ingreso eliminado correctamente.', [], 'Diagnosticos');
    }

    public function abrirNuevaEvolucion(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->evolucionEditandoId = null;
        $this->evolucionForm = [
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $this->form['profesional_salud_id'] ?: '',
            'detalle_clinico' => '',
        ];
        $this->profesionalEvolucionBusqueda = $this->etiquetaProfesionalEvolucionId($this->evolucionForm['profesional_salud_id'] ?: null);
        $this->formularioEvolucionAbierto = true;
        $this->resetValidation(['evolucionForm.fecha_hora', 'evolucionForm.profesional_salud_id', 'evolucionForm.detalle_clinico']);
    }

    public function editarEvolucion(int $evolucionId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaEvolucion($evolucionId);

        $this->evolucionEditandoId = $registro->id;
        $this->evolucionForm = [
            'fecha_hora' => $registro->fecha_hora?->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $registro->profesional_salud_id,
            'detalle_clinico' => $registro->detalle_clinico,
        ];
        $this->profesionalEvolucionBusqueda = $this->etiquetaProfesionalEvolucionId($registro->profesional_salud_id);
        $this->formularioEvolucionAbierto = true;
        $this->resetValidation(['evolucionForm.fecha_hora', 'evolucionForm.profesional_salud_id', 'evolucionForm.detalle_clinico']);
    }

    public function guardarEvolucion(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar evoluciones clinicas.', [], 'Evoluciones');

            return;
        }

        $datos = $this->validate([
            'evolucionForm.fecha_hora' => ['required', 'date'],
            'evolucionForm.profesional_salud_id' => ['nullable', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
            'evolucionForm.detalle_clinico' => ['required', 'string'],
        ], [], [
            'evolucionForm.fecha_hora' => 'fecha y hora',
            'evolucionForm.profesional_salud_id' => 'profesional de la salud',
            'evolucionForm.detalle_clinico' => 'detalle clinico',
        ])['evolucionForm'];

        $registro = $this->evolucionEditandoId
            ? $this->consultaEvolucion($this->evolucionEditandoId)
            : new Evolucion([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'fecha_hora' => $datos['fecha_hora'],
            'profesional_salud_id' => $datos['profesional_salud_id'] ?: null,
            'detalle_clinico' => $datos['detalle_clinico'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioEvolucionAbierto = false;
        $this->evolucionEditandoId = null;
        $this->evolucionForm = ['fecha_hora' => '', 'profesional_salud_id' => '', 'detalle_clinico' => ''];
        $this->profesionalEvolucionBusqueda = '';

        flash()->success('Evolucion clinica registrada correctamente.', [], 'Evoluciones');
    }

    public function eliminarEvolucion(int $evolucionId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaEvolucion($evolucionId)->delete();

        $this->formularioEvolucionAbierto = false;
        $this->evolucionEditandoId = null;

        flash()->success('Evolucion clinica eliminada correctamente.', [], 'Evoluciones');
    }

    public function abrirNuevoDiagnosticoEgreso(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->diagnosticoEgresoEditandoId = null;
        $this->diagnosticoEgresoForm = [
            'diagnostico_cie10_id' => '',
            'clasificacion' => ClasificacionDiagnosticoEgreso::Principal->value,
            'tipo_diagnostico' => TipoDiagnostico::Definitivo->value,
            'observaciones' => '',
        ];
        $this->diagnosticoEgresoCie10Busqueda = '';
        $this->formularioDiagnosticoEgresoAbierto = true;
        $this->resetValidation(['diagnosticoEgresoForm.diagnostico_cie10_id', 'diagnosticoEgresoForm.clasificacion', 'diagnosticoEgresoForm.tipo_diagnostico', 'diagnosticoEgresoForm.observaciones']);
    }

    public function editarDiagnosticoEgreso(int $diagnosticoEgresoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaDiagnosticoEgreso($diagnosticoEgresoId);

        $this->diagnosticoEgresoEditandoId = $registro->id;
        $this->diagnosticoEgresoForm = [
            'diagnostico_cie10_id' => $registro->diagnostico_cie10_id,
            'clasificacion' => $this->valorEnum($registro->clasificacion),
            'tipo_diagnostico' => $this->valorEnum($registro->tipo_diagnostico),
            'observaciones' => $registro->observaciones,
        ];
        $this->diagnosticoEgresoCie10Busqueda = $this->etiquetaDiagnosticoCie10($registro->diagnosticoCie10);
        $this->formularioDiagnosticoEgresoAbierto = true;
        $this->resetValidation(['diagnosticoEgresoForm.diagnostico_cie10_id', 'diagnosticoEgresoForm.clasificacion', 'diagnosticoEgresoForm.tipo_diagnostico', 'diagnosticoEgresoForm.observaciones']);
    }

    public function guardarDiagnosticoEgreso(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar diagnosticos de egreso.', [], 'Diagnosticos');

            return;
        }

        $datos = $this->validate([
            'diagnosticoEgresoForm.diagnostico_cie10_id' => ['required', 'integer', Rule::exists('diagnosticos_cie10', 'id')->whereNull('deleted_at')],
            'diagnosticoEgresoForm.clasificacion' => ['nullable', Rule::in(array_map(fn (ClasificacionDiagnosticoEgreso $clasificacion): string => $clasificacion->value, ClasificacionDiagnosticoEgreso::cases()))],
            'diagnosticoEgresoForm.tipo_diagnostico' => ['nullable', Rule::in(array_map(fn (TipoDiagnostico $tipo): string => $tipo->value, TipoDiagnostico::cases()))],
            'diagnosticoEgresoForm.observaciones' => ['nullable', 'string'],
        ], [], [
            'diagnosticoEgresoForm.diagnostico_cie10_id' => 'diagnostico CIE10',
            'diagnosticoEgresoForm.clasificacion' => 'clasificacion',
            'diagnosticoEgresoForm.tipo_diagnostico' => 'tipo de diagnostico',
            'diagnosticoEgresoForm.observaciones' => 'observacion',
        ])['diagnosticoEgresoForm'];

        $diagnostico = DiagnosticoCie10::query()->findOrFail($datos['diagnostico_cie10_id']);
        $registro = $this->diagnosticoEgresoEditandoId
            ? $this->consultaDiagnosticoEgreso($this->diagnosticoEgresoEditandoId)
            : new EgresoDiagnostico([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'diagnostico_cie10_id' => $diagnostico->id,
            'codigo_cie10' => $diagnostico->codigo,
            'denominacion' => $diagnostico->nombre,
            'clasificacion' => $datos['clasificacion'] ?: null,
            'tipo_diagnostico' => $datos['tipo_diagnostico'] ?: null,
            'observaciones' => $datos['observaciones'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioDiagnosticoEgresoAbierto = false;
        $this->diagnosticoEgresoEditandoId = null;
        $this->diagnosticoEgresoForm = ['diagnostico_cie10_id' => '', 'clasificacion' => '', 'tipo_diagnostico' => '', 'observaciones' => ''];
        $this->diagnosticoEgresoCie10Busqueda = '';

        flash()->success('Diagnostico de egreso registrado correctamente.', [], 'Diagnosticos');
    }

    public function eliminarDiagnosticoEgreso(int $diagnosticoEgresoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaDiagnosticoEgreso($diagnosticoEgresoId)->delete();

        $this->formularioDiagnosticoEgresoAbierto = false;
        $this->diagnosticoEgresoEditandoId = null;

        flash()->success('Diagnostico de egreso eliminado correctamente.', [], 'Diagnosticos');
    }

    public function abrirNuevaOrden(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->ordenEditandoId = null;
        $this->ordenForm = [
            'tipo_orden_id' => '',
            'detalle' => '',
            'prioridad' => PrioridadOrden::Urgente->value,
            'fecha_hora_solicitud' => now()->format('Y-m-d\TH:i'),
            'resultado' => '',
            'profesional_salud_id' => $this->form['profesional_salud_id'] ?: '',
        ];
        $this->formularioOrdenAbierto = true;
        $this->resetValidation(['ordenForm.tipo_orden_id', 'ordenForm.detalle', 'ordenForm.prioridad', 'ordenForm.fecha_hora_solicitud', 'ordenForm.resultado', 'ordenForm.profesional_salud_id']);
    }

    public function editarOrden(int $ordenId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaOrden($ordenId);

        $this->ordenEditandoId = $registro->id;
        $this->ordenForm = [
            'tipo_orden_id' => $registro->tipo_orden_id,
            'detalle' => $registro->detalle,
            'prioridad' => $this->valorEnum($registro->prioridad),
            'fecha_hora_solicitud' => $registro->fecha_hora_solicitud?->format('Y-m-d\TH:i'),
            'resultado' => $registro->resultado,
            'profesional_salud_id' => $registro->profesional_salud_id,
        ];
        $this->formularioOrdenAbierto = true;
        $this->resetValidation(['ordenForm.tipo_orden_id', 'ordenForm.detalle', 'ordenForm.prioridad', 'ordenForm.fecha_hora_solicitud', 'ordenForm.resultado', 'ordenForm.profesional_salud_id']);
    }

    public function guardarOrden(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar ordenes.', [], 'Ordenes');

            return;
        }

        $datos = $this->validate([
            'ordenForm.tipo_orden_id' => ['required', 'integer', Rule::exists('tipos_ordenes', 'id')->whereNull('deleted_at')],
            'ordenForm.detalle' => ['required', 'string'],
            'ordenForm.prioridad' => ['nullable', Rule::in(array_map(fn (PrioridadOrden $prioridad): string => $prioridad->value, PrioridadOrden::cases()))],
            'ordenForm.fecha_hora_solicitud' => ['nullable', 'date'],
            'ordenForm.resultado' => ['nullable', 'string'],
            'ordenForm.profesional_salud_id' => ['nullable', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
        ], [], [
            'ordenForm.tipo_orden_id' => 'tipo de orden',
            'ordenForm.detalle' => 'detalle',
            'ordenForm.prioridad' => 'prioridad',
            'ordenForm.fecha_hora_solicitud' => 'fecha y hora',
            'ordenForm.resultado' => 'resultado',
            'ordenForm.profesional_salud_id' => 'profesional',
        ])['ordenForm'];

        $registro = $this->ordenEditandoId
            ? $this->consultaOrden($this->ordenEditandoId)
            : new Orden([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'tipo_orden_id' => $datos['tipo_orden_id'],
            'detalle' => $datos['detalle'],
            'prioridad' => $datos['prioridad'] ?: null,
            'fecha_hora_solicitud' => $datos['fecha_hora_solicitud'],
            'resultado' => $datos['resultado'],
            'profesional_salud_id' => $datos['profesional_salud_id'] ?: null,
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioOrdenAbierto = false;
        $this->ordenEditandoId = null;
        $this->ordenForm = ['tipo_orden_id' => '', 'detalle' => '', 'prioridad' => '', 'fecha_hora_solicitud' => '', 'resultado' => '', 'profesional_salud_id' => ''];

        flash()->success('Orden registrada correctamente.', [], 'Ordenes');
    }

    public function eliminarOrden(int $ordenId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaOrden($ordenId)->delete();

        $this->formularioOrdenAbierto = false;
        $this->ordenEditandoId = null;

        flash()->success('Orden eliminada correctamente.', [], 'Ordenes');
    }

    public function abrirNuevaSolicitudExamen(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->solicitudExamenEditandoId = null;
        $this->solicitudExamenForm = [
            'examen_id' => '',
            'prioridad' => PrioridadSolicitudExamen::Urgente->value,
            'fecha_hora_solicitud' => now()->format('Y-m-d\TH:i'),
            'resultado' => '',
            'estado' => EstadoSolicitudExamen::Solicitado->value,
            'profesional_salud_id' => $this->form['profesional_salud_id'] ?: '',
        ];
        $this->solicitudExamenBusqueda = '';
        $this->formularioSolicitudExamenAbierto = true;
        $this->resetValidation(['solicitudExamenForm.examen_id', 'solicitudExamenForm.prioridad', 'solicitudExamenForm.fecha_hora_solicitud', 'solicitudExamenForm.resultado', 'solicitudExamenForm.estado', 'solicitudExamenForm.profesional_salud_id']);
    }

    public function editarSolicitudExamen(int $solicitudExamenId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaSolicitudExamen($solicitudExamenId);

        $this->solicitudExamenEditandoId = $registro->id;
        $this->solicitudExamenForm = [
            'examen_id' => $registro->examen_id,
            'prioridad' => $this->valorEnum($registro->prioridad),
            'fecha_hora_solicitud' => $registro->fecha_hora_solicitud?->format('Y-m-d\TH:i'),
            'resultado' => $registro->resultado,
            'estado' => $this->valorEnum($registro->estado),
            'profesional_salud_id' => $registro->profesional_salud_id,
        ];
        $this->solicitudExamenBusqueda = $this->etiquetaExamen($registro->examen);
        $this->formularioSolicitudExamenAbierto = true;
        $this->resetValidation(['solicitudExamenForm.examen_id', 'solicitudExamenForm.prioridad', 'solicitudExamenForm.fecha_hora_solicitud', 'solicitudExamenForm.resultado', 'solicitudExamenForm.estado', 'solicitudExamenForm.profesional_salud_id']);
    }

    public function guardarSolicitudExamen(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar examenes de laboratorio.', [], 'Laboratorio');

            return;
        }

        $datos = $this->validate([
            'solicitudExamenForm.examen_id' => ['required', 'integer', Rule::exists('examenes', 'id')->whereNull('deleted_at')],
            'solicitudExamenForm.prioridad' => ['nullable', Rule::in(array_map(fn (PrioridadSolicitudExamen $prioridad): string => $prioridad->value, PrioridadSolicitudExamen::cases()))],
            'solicitudExamenForm.fecha_hora_solicitud' => ['nullable', 'date'],
            'solicitudExamenForm.resultado' => ['nullable', 'string'],
            'solicitudExamenForm.estado' => ['required', Rule::in(array_map(fn (EstadoSolicitudExamen $estado): string => $estado->value, EstadoSolicitudExamen::cases()))],
            'solicitudExamenForm.profesional_salud_id' => ['nullable', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
        ], [], [
            'solicitudExamenForm.examen_id' => 'examen',
            'solicitudExamenForm.prioridad' => 'prioridad',
            'solicitudExamenForm.fecha_hora_solicitud' => 'fecha y hora',
            'solicitudExamenForm.resultado' => 'resultado',
            'solicitudExamenForm.estado' => 'estado',
            'solicitudExamenForm.profesional_salud_id' => 'profesional',
        ])['solicitudExamenForm'];

        $registro = $this->solicitudExamenEditandoId
            ? $this->consultaSolicitudExamen($this->solicitudExamenEditandoId)
            : new SolicitudExamen([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'examen_id' => $datos['examen_id'],
            'prioridad' => $datos['prioridad'] ?: null,
            'fecha_hora_solicitud' => $datos['fecha_hora_solicitud'],
            'resultado' => $datos['resultado'],
            'estado' => $datos['estado'],
            'profesional_salud_id' => $datos['profesional_salud_id'] ?: null,
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioSolicitudExamenAbierto = false;
        $this->solicitudExamenEditandoId = null;
        $this->solicitudExamenForm = ['examen_id' => '', 'prioridad' => '', 'fecha_hora_solicitud' => '', 'resultado' => '', 'estado' => '', 'profesional_salud_id' => ''];
        $this->solicitudExamenBusqueda = '';

        flash()->success('Examen de laboratorio registrado correctamente.', [], 'Laboratorio');
    }

    public function eliminarSolicitudExamen(int $solicitudExamenId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaSolicitudExamen($solicitudExamenId)->delete();

        $this->formularioSolicitudExamenAbierto = false;
        $this->solicitudExamenEditandoId = null;

        flash()->success('Examen de laboratorio eliminado correctamente.', [], 'Laboratorio');
    }

    public function abrirNuevaPrescripcion(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->prescripcionEditandoId = null;
        $this->prescripcionForm = [
            'medicamento_id' => '',
            'medicamento' => '',
            'presentacion' => '',
            'dosis' => '',
            'via' => '',
            'duracion' => '',
            'cantidad' => '',
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $this->form['profesional_salud_id'] ?: '',
        ];
        $this->prescripcionMedicamentoBusqueda = '';
        $this->formularioPrescripcionAbierto = true;
        $this->resetValidation(['prescripcionForm.medicamento_id', 'prescripcionForm.medicamento', 'prescripcionForm.presentacion', 'prescripcionForm.dosis', 'prescripcionForm.via', 'prescripcionForm.duracion', 'prescripcionForm.cantidad', 'prescripcionForm.fecha_hora', 'prescripcionForm.profesional_salud_id']);
    }

    public function editarPrescripcion(int $prescripcionId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaPrescripcion($prescripcionId);

        $this->prescripcionEditandoId = $registro->id;
        $this->prescripcionForm = [
            'medicamento_id' => $registro->medicamento_id,
            'medicamento' => $registro->medicamento,
            'presentacion' => $registro->presentacion,
            'dosis' => $registro->dosis,
            'via' => $registro->via,
            'duracion' => $registro->duracion,
            'cantidad' => $registro->cantidad,
            'fecha_hora' => $registro->fecha_hora?->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $registro->profesional_salud_id,
        ];
        $this->prescripcionMedicamentoBusqueda = $registro->medicamento;
        $this->formularioPrescripcionAbierto = true;
        $this->resetValidation(['prescripcionForm.medicamento_id', 'prescripcionForm.medicamento', 'prescripcionForm.presentacion', 'prescripcionForm.dosis', 'prescripcionForm.via', 'prescripcionForm.duracion', 'prescripcionForm.cantidad', 'prescripcionForm.fecha_hora', 'prescripcionForm.profesional_salud_id']);
    }

    public function guardarPrescripcion(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar prescripciones.', [], 'Farmacos');

            return;
        }

        $this->prescripcionForm['medicamento'] = filled($this->prescripcionForm['medicamento'])
            ? $this->prescripcionForm['medicamento']
            : $this->prescripcionMedicamentoBusqueda;

        $datos = $this->validate([
            'prescripcionForm.medicamento_id' => ['nullable', 'integer', Rule::exists('medicamentos', 'id')->whereNull('deleted_at')],
            'prescripcionForm.medicamento' => ['required', 'string', 'max:255'],
            'prescripcionForm.presentacion' => ['nullable', 'string', 'max:150'],
            'prescripcionForm.dosis' => ['nullable', 'string', 'max:100'],
            'prescripcionForm.via' => ['nullable', 'string', 'max:100'],
            'prescripcionForm.duracion' => ['nullable', 'string', 'max:100'],
            'prescripcionForm.cantidad' => ['nullable', 'integer', 'min:1'],
            'prescripcionForm.fecha_hora' => ['nullable', 'date'],
            'prescripcionForm.profesional_salud_id' => ['nullable', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
        ], [], [
            'prescripcionForm.medicamento_id' => 'medicamento catalogado',
            'prescripcionForm.medicamento' => 'medicamento',
            'prescripcionForm.presentacion' => 'presentacion',
            'prescripcionForm.dosis' => 'dosis',
            'prescripcionForm.via' => 'via',
            'prescripcionForm.duracion' => 'duracion',
            'prescripcionForm.cantidad' => 'cantidad',
            'prescripcionForm.fecha_hora' => 'fecha y hora',
            'prescripcionForm.profesional_salud_id' => 'profesional',
        ])['prescripcionForm'];

        $registro = $this->prescripcionEditandoId
            ? $this->consultaPrescripcion($this->prescripcionEditandoId)
            : new Prescripcion([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'medicamento_id' => $datos['medicamento_id'] ?: null,
            'medicamento' => $datos['medicamento'],
            'presentacion' => $datos['presentacion'],
            'dosis' => $datos['dosis'],
            'via' => $datos['via'],
            'duracion' => $datos['duracion'],
            'cantidad' => $datos['cantidad'] ?: null,
            'fecha_hora' => $datos['fecha_hora'],
            'profesional_salud_id' => $datos['profesional_salud_id'] ?: null,
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioPrescripcionAbierto = false;
        $this->prescripcionEditandoId = null;
        $this->prescripcionForm = ['medicamento_id' => '', 'medicamento' => '', 'presentacion' => '', 'dosis' => '', 'via' => '', 'duracion' => '', 'cantidad' => '', 'fecha_hora' => '', 'profesional_salud_id' => ''];
        $this->prescripcionMedicamentoBusqueda = '';

        flash()->success('Prescripcion registrada correctamente.', [], 'Farmacos');
    }

    public function eliminarPrescripcion(int $prescripcionId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaPrescripcion($prescripcionId)->delete();

        $this->formularioPrescripcionAbierto = false;
        $this->prescripcionEditandoId = null;

        flash()->success('Prescripcion eliminada correctamente.', [], 'Farmacos');
    }

    public function abrirNuevoProcedimiento(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->procedimientoEditandoId = null;
        $this->procedimientoForm = [
            'procedimiento_id' => '',
            'denominacion' => '',
            'sesiones' => '',
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
        ];
        $this->procedimientoBusqueda = '';
        $this->formularioProcedimientoAbierto = true;
        $this->resetValidation(['procedimientoForm.procedimiento_id', 'procedimientoForm.denominacion', 'procedimientoForm.sesiones', 'procedimientoForm.fecha_hora']);
    }

    public function editarProcedimiento(int $procedimientoRealizadoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $registro = $this->consultaProcedimientoRealizado($procedimientoRealizadoId);

        $this->procedimientoEditandoId = $registro->id;
        $this->procedimientoForm = [
            'procedimiento_id' => $registro->procedimiento_id,
            'denominacion' => $registro->denominacion,
            'sesiones' => $registro->sesiones,
            'fecha_hora' => $registro->fecha_hora?->format('Y-m-d\TH:i'),
        ];
        $this->procedimientoBusqueda = $registro->denominacion ?: '';
        $this->formularioProcedimientoAbierto = true;
        $this->resetValidation(['procedimientoForm.procedimiento_id', 'procedimientoForm.denominacion', 'procedimientoForm.sesiones', 'procedimientoForm.fecha_hora']);
    }

    public function guardarProcedimiento(): void
    {
        abort_if($this->modo === 'ver', 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar procedimientos.', [], 'Procedimientos');

            return;
        }

        $this->procedimientoForm['denominacion'] = filled($this->procedimientoForm['denominacion'])
            ? $this->procedimientoForm['denominacion']
            : $this->procedimientoBusqueda;

        $datos = $this->validate([
            'procedimientoForm.procedimiento_id' => ['nullable', 'integer', Rule::exists('procedimientos', 'id')->whereNull('deleted_at')],
            'procedimientoForm.denominacion' => ['required', 'string', 'max:255'],
            'procedimientoForm.sesiones' => ['nullable', 'integer', 'min:1'],
            'procedimientoForm.fecha_hora' => ['nullable', 'date'],
        ], [], [
            'procedimientoForm.procedimiento_id' => 'procedimiento catalogado',
            'procedimientoForm.denominacion' => 'procedimiento',
            'procedimientoForm.sesiones' => 'sesiones',
            'procedimientoForm.fecha_hora' => 'fecha y hora',
        ])['procedimientoForm'];

        $registro = $this->procedimientoEditandoId
            ? $this->consultaProcedimientoRealizado($this->procedimientoEditandoId)
            : new ProcedimientoRealizado([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'procedimiento_id' => $datos['procedimiento_id'] ?: null,
            'denominacion' => $datos['denominacion'],
            'sesiones' => $datos['sesiones'] ?: null,
            'fecha_hora' => $datos['fecha_hora'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioProcedimientoAbierto = false;
        $this->procedimientoEditandoId = null;
        $this->procedimientoForm = ['procedimiento_id' => '', 'denominacion' => '', 'sesiones' => '', 'fecha_hora' => ''];
        $this->procedimientoBusqueda = '';

        flash()->success('Procedimiento registrado correctamente.', [], 'Procedimientos');
    }

    public function eliminarProcedimiento(int $procedimientoRealizadoId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaProcedimientoRealizado($procedimientoRealizadoId)->delete();

        $this->formularioProcedimientoAbierto = false;
        $this->procedimientoEditandoId = null;

        flash()->success('Procedimiento eliminado correctamente.', [], 'Procedimientos');
    }

    public function abrirNuevaNotaEnfermeria(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->notaEnfermeriaEditandoId = null;
        $this->notaEnfermeriaSoloLectura = false;
        $this->notaEnfermeriaForm = [
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $this->form['profesional_salud_id'] ?: '',
            'nota' => '',
            'firma' => '',
        ];
        $this->profesionalEnfermeriaBusqueda = $this->etiquetaProfesionalEvolucionId($this->notaEnfermeriaForm['profesional_salud_id'] ?: null);
        $this->formularioNotaEnfermeriaAbierto = true;
        $this->resetValidation(['notaEnfermeriaForm.fecha_hora', 'notaEnfermeriaForm.profesional_salud_id', 'notaEnfermeriaForm.nota', 'notaEnfermeriaForm.firma']);
    }

    public function verNotaEnfermeria(int $notaEnfermeriaId): void
    {
        $this->cargarNotaEnfermeriaFormulario($notaEnfermeriaId, true);
    }

    public function editarNotaEnfermeria(int $notaEnfermeriaId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->cargarNotaEnfermeriaFormulario($notaEnfermeriaId, false);
    }

    public function guardarNotaEnfermeria(): void
    {
        abort_if($this->modo === 'ver' || $this->notaEnfermeriaSoloLectura, 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar notas de enfermeria.', [], 'Enfermeria');

            return;
        }

        $datos = $this->validate([
            'notaEnfermeriaForm.fecha_hora' => ['nullable', 'date'],
            'notaEnfermeriaForm.profesional_salud_id' => ['nullable', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
            'notaEnfermeriaForm.nota' => ['required', 'string'],
            'notaEnfermeriaForm.firma' => ['nullable', 'string', 'max:255'],
        ], [], [
            'notaEnfermeriaForm.fecha_hora' => 'fecha y hora',
            'notaEnfermeriaForm.profesional_salud_id' => 'profesional',
            'notaEnfermeriaForm.nota' => 'nota de enfermeria',
            'notaEnfermeriaForm.firma' => 'firma',
        ])['notaEnfermeriaForm'];

        $registro = $this->notaEnfermeriaEditandoId
            ? $this->consultaNotaEnfermeria($this->notaEnfermeriaEditandoId)
            : new EnfermeriaNota([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'fecha_hora' => $datos['fecha_hora'],
            'profesional_salud_id' => $datos['profesional_salud_id'] ?: null,
            'nota' => $datos['nota'],
            'firma' => $datos['firma'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioNotaEnfermeriaAbierto = false;
        $this->notaEnfermeriaEditandoId = null;
        $this->notaEnfermeriaSoloLectura = false;
        $this->notaEnfermeriaForm = ['fecha_hora' => '', 'profesional_salud_id' => '', 'nota' => '', 'firma' => ''];
        $this->profesionalEnfermeriaBusqueda = '';

        flash()->success('Nota de enfermeria registrada correctamente.', [], 'Enfermeria');
    }

    public function eliminarNotaEnfermeria(int $notaEnfermeriaId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaNotaEnfermeria($notaEnfermeriaId)->delete();

        $this->formularioNotaEnfermeriaAbierto = false;
        $this->notaEnfermeriaEditandoId = null;
        $this->notaEnfermeriaSoloLectura = false;
        $this->profesionalEnfermeriaBusqueda = '';

        flash()->success('Nota de enfermeria eliminada correctamente.', [], 'Enfermeria');
    }

    public function abrirNuevaInterconsulta(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->interconsultaEditandoId = null;
        $this->interconsultaSoloLectura = false;
        $this->interconsultaForm = [
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $this->form['profesional_salud_id'] ?: '',
            'nota' => '',
        ];
        $this->profesionalInterconsultaBusqueda = $this->etiquetaProfesionalEvolucionId($this->interconsultaForm['profesional_salud_id'] ?: null);
        $this->formularioInterconsultaAbierto = true;
        $this->resetValidation(['interconsultaForm.fecha_hora', 'interconsultaForm.profesional_salud_id', 'interconsultaForm.nota']);
    }

    public function verInterconsulta(int $interconsultaId): void
    {
        $this->cargarInterconsultaFormulario($interconsultaId, true);
    }

    public function editarInterconsulta(int $interconsultaId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->cargarInterconsultaFormulario($interconsultaId, false);
    }

    public function guardarInterconsulta(): void
    {
        abort_if($this->modo === 'ver' || $this->interconsultaSoloLectura, 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar interconsultas.', [], 'Interconsultas');

            return;
        }

        $datos = $this->validate([
            'interconsultaForm.fecha_hora' => ['nullable', 'date'],
            'interconsultaForm.profesional_salud_id' => ['nullable', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
            'interconsultaForm.nota' => ['required', 'string'],
        ], [], [
            'interconsultaForm.fecha_hora' => 'fecha y hora',
            'interconsultaForm.profesional_salud_id' => 'profesional',
            'interconsultaForm.nota' => 'nota de interconsulta',
        ])['interconsultaForm'];

        $registro = $this->interconsultaEditandoId
            ? $this->consultaInterconsulta($this->interconsultaEditandoId)
            : new Interconsulta([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'fecha_hora' => $datos['fecha_hora'],
            'profesional_salud_id' => $datos['profesional_salud_id'] ?: null,
            'nota' => $datos['nota'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioInterconsultaAbierto = false;
        $this->interconsultaEditandoId = null;
        $this->interconsultaSoloLectura = false;
        $this->interconsultaForm = ['fecha_hora' => '', 'profesional_salud_id' => '', 'nota' => ''];
        $this->profesionalInterconsultaBusqueda = '';

        flash()->success('Interconsulta registrada correctamente.', [], 'Interconsultas');
    }

    public function abrirNuevaTransferencia(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->transferenciaEditandoId = null;
        $this->transferenciaSoloLectura = false;
        $this->transferenciaForm = [
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
            'origen' => 'Emergencia',
            'destino' => '',
            'motivo' => '',
            'responsable' => '',
        ];
        $this->transferenciaResponsableBusqueda = '';
        $this->formularioTransferenciaAbierto = true;
        $this->resetValidation(['transferenciaForm.fecha_hora', 'transferenciaForm.origen', 'transferenciaForm.destino', 'transferenciaForm.motivo', 'transferenciaForm.responsable']);
    }

    public function verTransferencia(int $transferenciaId): void
    {
        $this->cargarTransferenciaFormulario($transferenciaId, true);
    }

    public function editarTransferencia(int $transferenciaId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->cargarTransferenciaFormulario($transferenciaId, false);
    }

    public function guardarTransferencia(): void
    {
        abort_if($this->modo === 'ver' || $this->transferenciaSoloLectura, 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar transferencias.', [], 'Movimientos');

            return;
        }

        $this->transferenciaForm['responsable'] = filled($this->transferenciaForm['responsable'])
            ? $this->transferenciaForm['responsable']
            : $this->transferenciaResponsableBusqueda;

        $datos = $this->validate([
            'transferenciaForm.fecha_hora' => ['nullable', 'date'],
            'transferenciaForm.origen' => ['nullable', 'string', 'max:150'],
            'transferenciaForm.destino' => ['nullable', 'string', 'max:150'],
            'transferenciaForm.motivo' => ['nullable', 'string'],
            'transferenciaForm.responsable' => ['nullable', 'string', 'max:255'],
        ], [], [
            'transferenciaForm.fecha_hora' => 'fecha y hora',
            'transferenciaForm.origen' => 'origen',
            'transferenciaForm.destino' => 'destino',
            'transferenciaForm.motivo' => 'motivo',
            'transferenciaForm.responsable' => 'responsable',
        ])['transferenciaForm'];

        $registro = $this->transferenciaEditandoId
            ? $this->consultaTransferencia($this->transferenciaEditandoId)
            : new Transferencia([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'fecha_hora' => $datos['fecha_hora'],
            'origen' => $datos['origen'],
            'destino' => $datos['destino'],
            'motivo' => $datos['motivo'],
            'responsable' => $datos['responsable'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioTransferenciaAbierto = false;
        $this->transferenciaEditandoId = null;
        $this->transferenciaSoloLectura = false;
        $this->transferenciaForm = ['fecha_hora' => '', 'origen' => '', 'destino' => '', 'motivo' => '', 'responsable' => ''];
        $this->transferenciaResponsableBusqueda = '';

        flash()->success('Transferencia registrada correctamente.', [], 'Movimientos');
    }

    public function eliminarTransferencia(int $transferenciaId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaTransferencia($transferenciaId)->delete();

        $this->formularioTransferenciaAbierto = false;
        $this->transferenciaEditandoId = null;
        $this->transferenciaSoloLectura = false;
        $this->transferenciaResponsableBusqueda = '';

        flash()->success('Transferencia eliminada correctamente.', [], 'Movimientos');
    }

    public function abrirNuevaReferencia(): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->referenciaEditandoId = null;
        $this->referenciaSoloLectura = false;
        $this->referenciaForm = [
            'ipress_origen' => 'Hospital Regional HCEApp',
            'ups_origen_id' => '',
            'ipress_referencia' => '',
            'ups_destino_id' => '',
            'motivo_referencia' => '',
            'fecha_hora_solicitud' => now()->format('Y-m-d\TH:i'),
            'respuesta_solicitud' => 'Pendiente',
            'fecha_hora_respuesta' => '',
        ];
        $this->formularioReferenciaAbierto = true;
        $this->resetValidation(['referenciaForm.ipress_origen', 'referenciaForm.ups_origen_id', 'referenciaForm.ipress_referencia', 'referenciaForm.ups_destino_id', 'referenciaForm.motivo_referencia', 'referenciaForm.fecha_hora_solicitud', 'referenciaForm.respuesta_solicitud', 'referenciaForm.fecha_hora_respuesta']);
    }

    public function verReferencia(int $referenciaId): void
    {
        $this->cargarReferenciaFormulario($referenciaId, true);
    }

    public function editarReferencia(int $referenciaId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->cargarReferenciaFormulario($referenciaId, false);
    }

    public function guardarReferencia(): void
    {
        abort_if($this->modo === 'ver' || $this->referenciaSoloLectura, 403);

        if ($this->atencion === null) {
            flash()->warning('Guarda primero la historia clinica para registrar referencias.', [], 'Referencias');

            return;
        }

        $datos = $this->validate([
            'referenciaForm.ipress_origen' => ['nullable', 'string', 'max:255'],
            'referenciaForm.ups_origen_id' => ['nullable', 'integer', Rule::exists('ups', 'id')],
            'referenciaForm.ipress_referencia' => ['nullable', 'string', 'max:255'],
            'referenciaForm.ups_destino_id' => ['nullable', 'integer', Rule::exists('ups', 'id')],
            'referenciaForm.motivo_referencia' => ['nullable', 'string'],
            'referenciaForm.fecha_hora_solicitud' => ['nullable', 'date'],
            'referenciaForm.respuesta_solicitud' => ['nullable', 'string'],
            'referenciaForm.fecha_hora_respuesta' => ['nullable', 'date'],
        ], [], [
            'referenciaForm.ipress_origen' => 'IPRESS origen',
            'referenciaForm.ups_origen_id' => 'UPS origen',
            'referenciaForm.ipress_referencia' => 'IPRESS destino',
            'referenciaForm.ups_destino_id' => 'UPS destino',
            'referenciaForm.motivo_referencia' => 'motivo de referencia',
            'referenciaForm.fecha_hora_solicitud' => 'fecha y hora de solicitud',
            'referenciaForm.respuesta_solicitud' => 'estado de respuesta',
            'referenciaForm.fecha_hora_respuesta' => 'fecha y hora de respuesta',
        ])['referenciaForm'];

        $registro = $this->referenciaEditandoId
            ? $this->consultaReferencia($this->referenciaEditandoId)
            : new HcReferencia([
                'hc_emergencia_id' => $this->atencion->id,
                'registrado_por' => Auth::id(),
            ]);

        $registro->forceFill([
            'ipress_origen' => $datos['ipress_origen'],
            'ups_origen_id' => $datos['ups_origen_id'] ?: null,
            'ipress_referencia' => $datos['ipress_referencia'],
            'ups_destino_id' => $datos['ups_destino_id'] ?: null,
            'motivo_referencia' => $datos['motivo_referencia'],
            'fecha_hora_solicitud' => $datos['fecha_hora_solicitud'],
            'respuesta_solicitud' => $datos['respuesta_solicitud'],
            'fecha_hora_respuesta' => $datos['fecha_hora_respuesta'],
            'actualizado_por' => Auth::id(),
        ])->save();

        $this->formularioReferenciaAbierto = false;
        $this->referenciaEditandoId = null;
        $this->referenciaSoloLectura = false;
        $this->referenciaForm = ['ipress_origen' => '', 'ups_origen_id' => '', 'ipress_referencia' => '', 'ups_destino_id' => '', 'motivo_referencia' => '', 'fecha_hora_solicitud' => '', 'respuesta_solicitud' => '', 'fecha_hora_respuesta' => ''];

        flash()->success('Referencia registrada correctamente.', [], 'Referencias');
    }

    public function eliminarReferencia(int $referenciaId): void
    {
        abort_if($this->modo === 'ver', 403);

        $this->consultaReferencia($referenciaId)->delete();

        $this->formularioReferenciaAbierto = false;
        $this->referenciaEditandoId = null;
        $this->referenciaSoloLectura = false;

        flash()->success('Referencia eliminada correctamente.', [], 'Referencias');
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
            'fecha_hora_triaje' => now()->format('Y-m-d\TH:i'),
            'profesional_triaje_id' => $profesionalId,
            'prioridad_id' => Prioridad::query()->orderByRaw('orden = 2 desc')->orderBy('orden')->value('id'),
            'destino_topico' => null,
            'tiempo_espera_calculado' => null,
            'reevaluacion_programada' => null,
            'triaje' => false,
            'fecha_hora_anamnesis' => now()->format('Y-m-d\TH:i'),
            'tiempo_enfermedad' => null,
            'unidad_tiempo_enfermedad' => UnidadTiempoEnfermedad::Hrs->value,
            'signos_sintomas' => null,
            'desarrollo_cronologico' => null,
            'eg_ocular' => null,
            'eg_verbal' => null,
            'eg_motora' => null,
            'eg_total' => null,
            'estado_general' => null,
            'hidratacion' => null,
            'nutricion' => null,
            'estado_conciencia' => null,
            'piel_anexos' => null,
            'cabeza_cuello' => null,
            'torax_respiratorio' => null,
            'cardiovascular' => null,
            'abdomen' => null,
            'neurologico' => null,
            'musculoesqueletico_extremidades' => null,
            'genitourinario_ginecoobstetrico' => null,
            'otros' => null,
            'ingresos' => null,
            'egresos' => null,
            'balance' => null,
            'en_shock_trauma' => SiNo::No->value,
            'shock_trauma_fecha_hora' => null,
            'shock_trauma_responsable' => null,
            'shock_trauma_resultado' => null,
            'shock_trauma_destino' => null,
            'registro_especializado' => null,
            'estado_atencion' => EstadoAtencion::Abierta->value,
            'profesional_salud_id' => $profesionalId,
        ];

        $this->profesionalTriajeBusqueda = $this->etiquetaProfesionalId($profesionalId);
        $this->shockTraumaResponsableBusqueda = '';
        $this->recalcularTiemposTriaje();
    }

    private function cargarAtencion(HcEmergencia $atencion): void
    {
        $this->atencion = $atencion->loadMissing(['paciente', 'profesionalResponsable.especialidad', 'profesionalTriaje.especialidad', 'prioridad']);

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
            'fecha_hora_triaje' => $atencion->fecha_hora_triaje?->format('Y-m-d\TH:i'),
            'profesional_triaje_id' => $atencion->profesional_triaje_id,
            'prioridad_id' => $atencion->prioridad_id,
            'destino_topico' => $atencion->destino_topico,
            'tiempo_espera_calculado' => $atencion->tiempo_espera_calculado,
            'reevaluacion_programada' => $atencion->reevaluacion_programada?->format('Y-m-d\TH:i'),
            'triaje' => (bool) $atencion->triaje,
            'fecha_hora_anamnesis' => $atencion->fecha_hora_anamnesis?->format('Y-m-d\TH:i'),
            'tiempo_enfermedad' => $atencion->tiempo_enfermedad,
            'unidad_tiempo_enfermedad' => $this->valorEnum($atencion->unidad_tiempo_enfermedad),
            'signos_sintomas' => $atencion->signos_sintomas,
            'desarrollo_cronologico' => $atencion->desarrollo_cronologico,
            'eg_ocular' => $atencion->eg_ocular,
            'eg_verbal' => $atencion->eg_verbal,
            'eg_motora' => $atencion->eg_motora,
            'eg_total' => $atencion->eg_total,
            'estado_general' => $atencion->estado_general,
            'hidratacion' => $atencion->hidratacion,
            'nutricion' => $atencion->nutricion,
            'estado_conciencia' => $atencion->estado_conciencia,
            'piel_anexos' => $atencion->piel_anexos,
            'cabeza_cuello' => $atencion->cabeza_cuello,
            'torax_respiratorio' => $atencion->torax_respiratorio,
            'cardiovascular' => $atencion->cardiovascular,
            'abdomen' => $atencion->abdomen,
            'neurologico' => $atencion->neurologico,
            'musculoesqueletico_extremidades' => $atencion->musculoesqueletico_extremidades,
            'genitourinario_ginecoobstetrico' => $atencion->genitourinario_ginecoobstetrico,
            'otros' => $atencion->otros,
            'ingresos' => $atencion->ingresos,
            'egresos' => $atencion->egresos,
            'balance' => $atencion->balance,
            'en_shock_trauma' => $this->valorEnum($atencion->en_shock_trauma) ?: SiNo::No->value,
            'shock_trauma_fecha_hora' => $atencion->shock_trauma_fecha_hora?->format('Y-m-d\TH:i'),
            'shock_trauma_responsable' => $atencion->shock_trauma_responsable,
            'shock_trauma_resultado' => $atencion->shock_trauma_resultado,
            'shock_trauma_destino' => $atencion->shock_trauma_destino,
            'registro_especializado' => $atencion->registro_especializado,
            'estado_atencion' => $this->valorEnum($atencion->estado_atencion) ?: EstadoAtencion::Abierta->value,
            'profesional_salud_id' => $atencion->profesional_salud_id,
        ];

        $this->profesionalTriajeBusqueda = $this->etiquetaProfesional($atencion->profesionalTriaje);
        $this->shockTraumaResponsableBusqueda = $atencion->shock_trauma_responsable ?: '';
    }

    private function validarUsuarioMedico(): void
    {
        $usuario = Auth::user();

        abort_if($usuario?->tipo_usuario !== 'Médico' || $usuario?->profesional_salud_id === null, 403);
    }

    private function normalizarFormularioAntesDeValidar(): void
    {
        $this->recalcularBalanceHidrico();
        $this->form['shock_trauma_responsable'] = filled($this->form['shock_trauma_responsable'])
            ? $this->form['shock_trauma_responsable']
            : $this->shockTraumaResponsableBusqueda;
        $this->normalizarCampoEnum('topico', TopicoAtencion::class, TopicoAtencion::Topico->value);
        $this->normalizarCampoEnum('fuente', FuenteAtencion::class);
        $this->normalizarCampoEnum('en_shock_trauma', SiNo::class, SiNo::No->value);
        $this->normalizarCampoEnum('unidad_tiempo_enfermedad', UnidadTiempoEnfermedad::class);
        $this->normalizarCampoEnum('estado_atencion', EstadoAtencion::class, EstadoAtencion::Abierta->value);
        $this->normalizarCampoPrioridad();

        foreach (['profesional_salud_id', 'profesional_triaje_id', 'tiempo_enfermedad', 'eg_ocular', 'eg_verbal', 'eg_motora', 'eg_total'] as $campo) {
            $this->normalizarCampoId($campo);
        }

        $this->form['triaje'] = (bool) ($this->form['triaje'] ?? false);
    }

    private function normalizarCampoId(string $campo): void
    {
        $valor = $this->form[$campo] ?? null;

        if ($valor === '' || $valor === null) {
            $this->form[$campo] = null;

            return;
        }

        if (is_numeric($valor)) {
            $this->form[$campo] = (int) $valor;
        }
    }

    private function normalizarCampoPrioridad(): void
    {
        $valor = $this->form['prioridad_id'] ?? null;

        if ($valor === '' || $valor === null) {
            $this->form['prioridad_id'] = null;

            return;
        }

        if (is_numeric($valor)) {
            $this->form['prioridad_id'] = (int) $valor;

            return;
        }

        $valorNormalizado = Str::of((string) $valor)->ascii()->lower()->trim()->toString();

        if (preg_match('/nivel\s*(\d+)/', $valorNormalizado, $coincidencia) === 1) {
            $prioridadId = Prioridad::query()->where('orden', (int) $coincidencia[1])->value('id');

            $this->form['prioridad_id'] = $prioridadId !== null ? (int) $prioridadId : null;

            return;
        }

        $prioridadId = Prioridad::query()
            ->get(['id', 'prioridad'])
            ->first(function (Prioridad $prioridad) use ($valorNormalizado): bool {
                return Str::of($prioridad->prioridad)->ascii()->lower()->trim()->toString() === $valorNormalizado;
            })?->id;

        $this->form['prioridad_id'] = $prioridadId !== null ? (int) $prioridadId : null;
    }

    /**
     * Convierte valores de controles Livewire a los valores exactos del enum antes de validar.
     *
     * @param  class-string<BackedEnum>  $enum
     */
    private function normalizarCampoEnum(string $campo, string $enum, ?string $valorPorDefecto = null): void
    {
        $valor = $this->form[$campo] ?? null;

        if ($valor instanceof BackedEnum) {
            $this->form[$campo] = $valor->value;

            return;
        }

        if (blank($valor)) {
            $this->form[$campo] = $valorPorDefecto;

            return;
        }

        $valorNormalizado = Str::of((string) $valor)->ascii()->lower()->trim()->toString();

        foreach ($enum::cases() as $case) {
            $caseNormalizado = Str::of($case->value)->ascii()->lower()->trim()->toString();

            if ($valorNormalizado === $caseNormalizado) {
                $this->form[$campo] = $case->value;

                return;
            }
        }
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
            'form.fecha_hora_triaje' => ['nullable', 'date'],
            'form.profesional_triaje_id' => ['nullable', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
            'form.prioridad_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')->whereNull('deleted_at')],
            'form.destino_topico' => ['nullable', 'string', 'max:150'],
            'form.tiempo_espera_calculado' => ['nullable', 'string', 'max:50'],
            'form.reevaluacion_programada' => ['nullable', 'date'],
            'form.triaje' => ['boolean'],
            'form.fecha_hora_anamnesis' => ['nullable', 'date'],
            'form.tiempo_enfermedad' => ['nullable', 'integer', 'min:0'],
            'form.unidad_tiempo_enfermedad' => ['nullable', Rule::in(array_map(fn (BackedEnum $enum): string => $enum->value, UnidadTiempoEnfermedad::cases()))],
            'form.signos_sintomas' => ['nullable', 'string'],
            'form.desarrollo_cronologico' => ['nullable', 'string'],
            'form.eg_ocular' => ['nullable', 'integer', Rule::in($this->puntajesGlasgow('Ocular'))],
            'form.eg_verbal' => ['nullable', 'integer', Rule::in($this->puntajesGlasgow('Verbal'))],
            'form.eg_motora' => ['nullable', 'integer', Rule::in($this->puntajesGlasgow('Motora'))],
            'form.eg_total' => ['nullable', 'integer', 'between:3,15'],
            'form.estado_general' => ['nullable', 'string'],
            'form.hidratacion' => ['nullable', 'string'],
            'form.nutricion' => ['nullable', 'string'],
            'form.estado_conciencia' => ['nullable', 'string'],
            'form.piel_anexos' => ['nullable', 'string'],
            'form.cabeza_cuello' => ['nullable', 'string'],
            'form.torax_respiratorio' => ['nullable', 'string'],
            'form.cardiovascular' => ['nullable', 'string'],
            'form.abdomen' => ['nullable', 'string'],
            'form.neurologico' => ['nullable', 'string'],
            'form.musculoesqueletico_extremidades' => ['nullable', 'string'],
            'form.genitourinario_ginecoobstetrico' => ['nullable', 'string'],
            'form.otros' => ['nullable', 'string'],
            'form.ingresos' => ['nullable', 'string', 'max:100'],
            'form.egresos' => ['nullable', 'string', 'max:100'],
            'form.balance' => ['nullable', 'string', 'max:100'],
            'form.en_shock_trauma' => ['required', Rule::in(array_map(fn (BackedEnum $enum): string => $enum->value, SiNo::cases()))],
            'form.shock_trauma_fecha_hora' => ['nullable', 'date'],
            'form.shock_trauma_responsable' => ['nullable', 'string', 'max:255'],
            'form.shock_trauma_resultado' => ['nullable', 'string'],
            'form.shock_trauma_destino' => ['nullable', 'string', 'max:150'],
            'form.registro_especializado' => ['nullable', 'string'],
            'form.estado_atencion' => ['required', Rule::in(array_map(fn (BackedEnum $enum): string => $enum->value, EstadoAtencion::cases()))],
            'form.profesional_salud_id' => ['required', 'integer', Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * Ejecuta la validacion del formulario principal y prepara un resumen amigable para el usuario.
     *
     * @return array<string, mixed>|null
     */
    private function validarFormularioParaGuardado(): ?array
    {
        $validador = Validator::make(
            ['form' => $this->form],
            $this->reglas(),
            [],
            $this->atributosFormulario()
        );

        if ($validador->fails()) {
            $this->resetErrorBag();

            foreach ($validador->errors()->messages() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $this->addError($campo, $mensaje);
                }
            }

            $this->erroresGuardado = collect($validador->errors()->all())->values()->all();
            $this->panelErroresAbierto = true;
            $this->panelConfirmacionGuardadoAbierto = false;

            return null;
        }

        $this->resetErrorBag();
        $this->erroresGuardado = [];
        $this->panelErroresAbierto = false;

        return $validador->validated()['form'];
    }

    /**
     * Nombres visibles usados por Laravel al construir los mensajes de validacion.
     *
     * @return array<string, string>
     */
    private function atributosFormulario(): array
    {
        return [
            'form.numero_atencion' => 'numero de atencion',
            'form.fecha_hora_inicio_episodio' => 'fecha y hora del episodio',
            'form.fecha_hora_atencion' => 'fecha y hora de ingreso',
            'form.topico' => 'topico',
            'form.ambiente' => 'ambiente',
            'form.acompanante' => 'acompanante',
            'form.parentesco' => 'parentesco',
            'form.telefono' => 'telefono',
            'form.fuente' => 'fuente',
            'form.lugar_evento' => 'lugar del evento',
            'form.tipo_lesion_evento' => 'tipo de lesion',
            'form.fecha_hora_recepcion' => 'fecha y hora de recepcion',
            'form.signos_vitales_transmitidos' => 'signos vitales prehospitalarios',
            'form.fecha_hora_triaje' => 'fecha y hora de triaje',
            'form.profesional_triaje_id' => 'profesional de triaje',
            'form.prioridad_id' => 'nivel de prioridad',
            'form.destino_topico' => 'destino',
            'form.tiempo_espera_calculado' => 'tiempo de espera',
            'form.reevaluacion_programada' => 'reevaluacion programada',
            'form.triaje' => 'triaje realizado',
            'form.fecha_hora_anamnesis' => 'fecha y hora de anamnesis',
            'form.tiempo_enfermedad' => 'tiempo de enfermedad',
            'form.unidad_tiempo_enfermedad' => 'unidad del tiempo de enfermedad',
            'form.signos_sintomas' => 'signos y sintomas principales',
            'form.desarrollo_cronologico' => 'desarrollo cronologico',
            'form.eg_ocular' => 'apertura ocular',
            'form.eg_verbal' => 'respuesta verbal',
            'form.eg_motora' => 'respuesta motora',
            'form.eg_total' => 'puntuacion total de Glasgow',
            'form.estado_general' => 'estado general',
            'form.hidratacion' => 'hidratacion',
            'form.nutricion' => 'nutricion',
            'form.estado_conciencia' => 'estado de conciencia',
            'form.piel_anexos' => 'piel y anexos',
            'form.cabeza_cuello' => 'cabeza y cuello',
            'form.torax_respiratorio' => 'torax respiratorio',
            'form.cardiovascular' => 'cardiovascular',
            'form.abdomen' => 'abdomen',
            'form.neurologico' => 'neurologico',
            'form.musculoesqueletico_extremidades' => 'musculoesqueletico y extremidades',
            'form.genitourinario_ginecoobstetrico' => 'genitourinario ginecoobstetrico',
            'form.otros' => 'otros hallazgos',
            'form.ingresos' => 'ingresos totales',
            'form.egresos' => 'egresos totales',
            'form.balance' => 'balance hidrico',
            'form.en_shock_trauma' => 'ingreso a Trauma Shock',
            'form.shock_trauma_fecha_hora' => 'fecha y hora de ingreso a Trauma Shock',
            'form.shock_trauma_responsable' => 'responsable de Trauma Shock',
            'form.shock_trauma_resultado' => 'resultado de la atencion en Trauma Shock',
            'form.shock_trauma_destino' => 'destino post Trauma Shock',
            'form.registro_especializado' => 'registro especializado',
            'form.estado_atencion' => 'estado de la atencion',
            'form.profesional_salud_id' => 'profesional responsable',
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

    private function recalcularTiemposTriaje(): void
    {
        $prioridad = Prioridad::query()->find($this->form['prioridad_id']);

        if ($prioridad === null || $prioridad->tiempo_atencion === null) {
            $this->form['tiempo_espera_calculado'] = null;
            $this->form['reevaluacion_programada'] = null;

            return;
        }

        $minutos = (int) $prioridad->tiempo_atencion;
        $this->form['tiempo_espera_calculado'] = $minutos === 0 ? 'Inmediato' : $minutos.' min';

        if (blank($this->form['fecha_hora_triaje'])) {
            $this->form['reevaluacion_programada'] = null;

            return;
        }

        $this->form['reevaluacion_programada'] = Carbon::parse($this->form['fecha_hora_triaje'])
            ->addMinutes($minutos)
            ->format('Y-m-d\TH:i');
    }

    private function recalcularGlasgow(): void
    {
        $puntajes = collect([
            $this->form['eg_ocular'] ?? null,
            $this->form['eg_verbal'] ?? null,
            $this->form['eg_motora'] ?? null,
        ])->filter(fn ($valor): bool => is_numeric($valor));

        $this->form['eg_total'] = $puntajes->count() === 3
            ? $puntajes->sum(fn ($valor): int => (int) $valor)
            : null;
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, Glasgow>>
     */
    private function glasgowOpciones(): array
    {
        return Glasgow::query()
            ->orderBy('tipo')
            ->orderBy('puntaje')
            ->get(['tipo', 'puntaje', 'descripcion'])
            ->groupBy('tipo')
            ->all();
    }

    /**
     * @return array<string, array{titulo: string, campo: string, tipo: string, valor: int|null, descripcion: string}>
     */
    private function glasgowResumen(): array
    {
        return [
            'ocular' => $this->resumenGlasgow('Apertura Ocular', 'eg_ocular', 'Ocular'),
            'verbal' => $this->resumenGlasgow('Respuesta Verbal', 'eg_verbal', 'Verbal'),
            'motora' => $this->resumenGlasgow('Respuesta Motora', 'eg_motora', 'Motora'),
        ];
    }

    /**
     * @return array{titulo: string, campo: string, tipo: string, valor: int|null, descripcion: string}
     */
    private function resumenGlasgow(string $titulo, string $campo, string $tipo): array
    {
        $valor = is_numeric($this->form[$campo] ?? null) ? (int) $this->form[$campo] : null;
        $descripcion = $valor !== null
            ? Glasgow::query()->where('tipo', $tipo)->where('puntaje', $valor)->value('descripcion')
            : null;

        return [
            'titulo' => $titulo,
            'campo' => $campo,
            'tipo' => $tipo,
            'valor' => $valor,
            'descripcion' => $descripcion ?: 'Seleccione una respuesta',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function puntajesGlasgow(string $tipo): array
    {
        return Glasgow::query()
            ->where('tipo', $tipo)
            ->orderBy('puntaje')
            ->pluck('puntaje')
            ->map(fn ($puntaje): int => (int) $puntaje)
            ->all();
    }

    /**
     * @return array{texto: string, clase: string}
     */
    private function clasificacionGlasgow(): array
    {
        $total = is_numeric($this->form['eg_total'] ?? null) ? (int) $this->form['eg_total'] : null;

        if ($total === null) {
            return ['texto' => 'Pendiente', 'clase' => 'is-pending'];
        }

        if ($total >= 13) {
            return ['texto' => 'Normal / Leve', 'clase' => 'is-mild'];
        }

        if ($total >= 9) {
            return ['texto' => 'Moderado', 'clase' => 'is-moderate'];
        }

        return ['texto' => 'Grave / Coma', 'clase' => 'is-severe'];
    }

    private function colorPrioridad(mixed $prioridadId): ?string
    {
        $color = Prioridad::query()->find($prioridadId)?->color;

        return $color instanceof BackedEnum ? $color->value : $color;
    }

    private function profesionalesTriaje(): Collection
    {
        return ProfesionalSalud::query()
            ->with('especialidad')
            ->when($this->profesionalTriajeBusqueda !== '', function ($consulta): void {
                $busqueda = $this->profesionalTriajeBusqueda;

                $consulta->where(function ($grupo) use ($busqueda): void {
                    $grupo
                        ->where('nombres', 'like', "%{$busqueda}%")
                        ->orWhere('cmp', 'like', "%{$busqueda}%")
                        ->orWhere('rne', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('nombres')
            ->limit(8)
            ->get();
    }

    private function etiquetaProfesionalId(?int $profesionalId): string
    {
        return $this->etiquetaProfesional(ProfesionalSalud::query()->find($profesionalId));
    }

    private function etiquetaProfesional(?ProfesionalSalud $profesional): string
    {
        if ($profesional === null) {
            return '';
        }

        return collect([
            $profesional->nombres,
            $profesional->cmp ? 'CMP '.$profesional->cmp : null,
            $profesional->especialidad?->nombre,
        ])->filter()->implode(' · ');
    }

    private function profesionalesEvolucion(): Collection
    {
        return ProfesionalSalud::query()
            ->with('especialidad')
            ->when($this->profesionalEvolucionBusqueda !== '', function ($consulta): void {
                $busqueda = $this->profesionalEvolucionBusqueda;

                $consulta->where(function ($grupo) use ($busqueda): void {
                    $grupo
                        ->where('nombres', 'like', "%{$busqueda}%")
                        ->orWhere('cmp', 'like', "%{$busqueda}%")
                        ->orWhereHas('especialidad', fn ($especialidad) => $especialidad->where('nombre', 'like', "%{$busqueda}%"));
                });
            })
            ->orderBy('nombres')
            ->limit(10)
            ->get();
    }

    private function profesionalesEnfermeria(): Collection
    {
        return ProfesionalSalud::query()
            ->with('especialidad')
            ->when($this->profesionalEnfermeriaBusqueda !== '', function ($consulta): void {
                $busqueda = $this->profesionalEnfermeriaBusqueda;

                $consulta->where(function ($grupo) use ($busqueda): void {
                    $grupo
                        ->where('nombres', 'like', "%{$busqueda}%")
                        ->orWhere('cmp', 'like', "%{$busqueda}%")
                        ->orWhereHas('especialidad', fn ($especialidad) => $especialidad->where('nombre', 'like', "%{$busqueda}%"));
                });
            })
            ->orderBy('nombres')
            ->limit(10)
            ->get();
    }

    private function profesionalesInterconsulta(): Collection
    {
        return ProfesionalSalud::query()
            ->with('especialidad')
            ->when($this->profesionalInterconsultaBusqueda !== '', function ($consulta): void {
                $busqueda = $this->profesionalInterconsultaBusqueda;

                $consulta->where(function ($grupo) use ($busqueda): void {
                    $grupo
                        ->where('nombres', 'like', "%{$busqueda}%")
                        ->orWhere('cmp', 'like', "%{$busqueda}%")
                        ->orWhereHas('especialidad', fn ($especialidad) => $especialidad->where('nombre', 'like', "%{$busqueda}%"));
                });
            })
            ->orderBy('nombres')
            ->limit(10)
            ->get();
    }

    private function profesionalesPorBusqueda(string $busqueda): Collection
    {
        if (blank($busqueda)) {
            return collect();
        }

        return ProfesionalSalud::query()
            ->with('especialidad')
            ->where(function ($consulta) use ($busqueda): void {
                $consulta
                    ->where('nombres', 'like', "%{$busqueda}%")
                    ->orWhere('cmp', 'like', "%{$busqueda}%")
                    ->orWhereHas('especialidad', fn ($especialidad) => $especialidad->where('nombre', 'like', "%{$busqueda}%"));
            })
            ->orderBy('nombres')
            ->limit(8)
            ->get();
    }

    private function etiquetaProfesionalEvolucionId(?int $profesionalId): string
    {
        return $this->etiquetaProfesionalEvolucion(
            ProfesionalSalud::query()->with('especialidad')->find($profesionalId)
        );
    }

    private function etiquetaProfesionalEvolucion(?ProfesionalSalud $profesional): string
    {
        if ($profesional === null) {
            return '';
        }

        return collect([
            $profesional->nombres,
            $profesional->especialidad?->nombre ?: 'Sin especialidad',
            $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP',
        ])->filter()->implode(' - ');
    }

    private function diagnosticosCie10Ingreso(): Collection
    {
        return $this->buscarDiagnosticosCie10($this->diagnosticoIngresoCie10Busqueda);
    }

    private function diagnosticosCie10Egreso(): Collection
    {
        return $this->buscarDiagnosticosCie10($this->diagnosticoEgresoCie10Busqueda);
    }

    private function buscarDiagnosticosCie10(string $busqueda): Collection
    {
        return DiagnosticoCie10::query()
            ->when($busqueda !== '', function ($consulta) use ($busqueda): void {
                $consulta->where(function ($grupo) use ($busqueda): void {
                    $grupo
                        ->where('codigo', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('codigo')
            ->limit(12)
            ->get(['id', 'codigo', 'nombre']);
    }

    private function etiquetaDiagnosticoCie10Id(?int $diagnosticoId): string
    {
        return $this->etiquetaDiagnosticoCie10(DiagnosticoCie10::query()->find($diagnosticoId));
    }

    private function etiquetaDiagnosticoCie10(?DiagnosticoCie10 $diagnostico): string
    {
        if ($diagnostico === null) {
            return '';
        }

        return trim($diagnostico->codigo.' - '.$diagnostico->nombre);
    }

    private function examenesLaboratorio(): Collection
    {
        return Examen::query()
            ->when($this->solicitudExamenBusqueda !== '', function ($consulta): void {
                $busqueda = $this->solicitudExamenBusqueda;

                $consulta->where('descripcion', 'like', "%{$busqueda}%");
            })
            ->orderBy('descripcion')
            ->limit(12)
            ->get(['id', 'descripcion']);
    }

    private function etiquetaExamenId(?int $examenId): string
    {
        return $this->etiquetaExamen(Examen::query()->find($examenId));
    }

    private function etiquetaExamen(?Examen $examen): string
    {
        return $examen?->descripcion ?? '';
    }

    private function medicamentosPrescripcion(): Collection
    {
        return Medicamento::query()
            ->when($this->prescripcionMedicamentoBusqueda !== '', function ($consulta): void {
                $busqueda = $this->prescripcionMedicamentoBusqueda;

                $consulta->where(function ($grupo) use ($busqueda): void {
                    $grupo
                        ->where('descripcion', 'like', "%{$busqueda}%")
                        ->orWhere('presentacion', 'like', "%{$busqueda}%")
                        ->orWhere('generico', 'like', "%{$busqueda}%");
                });
            })
            ->where('estado', 'Activo')
            ->orderBy('descripcion')
            ->limit(12)
            ->get(['id', 'descripcion', 'presentacion', 'generico', 'stock']);
    }

    private function etiquetaMedicamentoId(?int $medicamentoId): string
    {
        return $this->etiquetaMedicamento(Medicamento::query()->find($medicamentoId));
    }

    private function etiquetaMedicamento(?Medicamento $medicamento): string
    {
        return $medicamento?->descripcion ?? '';
    }

    private function procedimientosCatalogo(): Collection
    {
        return Procedimiento::query()
            ->when($this->procedimientoBusqueda !== '', function ($consulta): void {
                $busqueda = $this->procedimientoBusqueda;

                $consulta->where(function ($grupo) use ($busqueda): void {
                    $grupo
                        ->where('codigo', 'like', "%{$busqueda}%")
                        ->orWhere('denominacion', 'like', "%{$busqueda}%");
                });
            })
            ->where('estado', 'Activo')
            ->orderBy('denominacion')
            ->limit(12)
            ->get(['id', 'codigo', 'denominacion']);
    }

    private function etiquetaProcedimientoId(?int $procedimientoId): string
    {
        return $this->etiquetaProcedimiento(Procedimiento::query()->find($procedimientoId));
    }

    private function etiquetaProcedimiento(?Procedimiento $procedimiento): string
    {
        return $procedimiento?->denominacion ?? '';
    }

    private function signosVitalesTriaje(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return $this->atencion
            ->signosVitalesTriaje()
            ->with('signoVital')
            ->latest()
            ->get()
            ->map(function (TriajeSignoVital $registro): array {
                $estado = $this->estadoSignoVital($registro);

                return [
                    'id' => $registro->id,
                    'funcion' => $registro->signoVital?->descripcion ?? 'Sin catalogo',
                    'valor' => $registro->valor,
                    'unidad' => $registro->signoVital?->unidad ?: '-',
                    'estado' => $estado,
                ];
            });
    }

    /**
     * Evalua el valor numerico contra los rangos normales del catalogo de signos vitales.
     *
     * @return array{icono: string, clase: string, texto: string}
     */
    private function estadoSignoVital(TriajeSignoVital $registro): array
    {
        $valor = $this->valorNumerico($registro->valor);
        $rangoInicial = $registro->signoVital?->rango_inicial !== null ? (float) $registro->signoVital->rango_inicial : null;
        $rangoFinal = $registro->signoVital?->rango_final !== null ? (float) $registro->signoVital->rango_final : null;

        if ($valor === null || ($rangoInicial === null && $rangoFinal === null)) {
            return ['icono' => 'lucide:minus', 'clase' => 'is-neutral', 'texto' => 'Sin rango'];
        }

        if ($rangoFinal !== null && $valor > $rangoFinal) {
            return ['icono' => 'lucide:arrow-up', 'clase' => 'is-high', 'texto' => 'Por encima'];
        }

        if ($rangoInicial !== null && $valor < $rangoInicial) {
            return ['icono' => 'lucide:arrow-down', 'clase' => 'is-low', 'texto' => 'Por debajo'];
        }

        return ['icono' => 'lucide:check', 'clase' => 'is-ok', 'texto' => 'Dentro de rango'];
    }

    private function valorNumerico(?string $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = str_replace(',', '.', trim($valor));

        if (preg_match('/-?\d+(?:\.\d+)?/', $normalizado, $coincidencia) !== 1) {
            return null;
        }

        return (float) $coincidencia[0];
    }

    private function recalcularBalanceHidrico(): void
    {
        $ingresos = $this->valorNumerico($this->form['ingresos'] ?? null);
        $egresos = $this->valorNumerico($this->form['egresos'] ?? null);

        if ($ingresos === null && $egresos === null) {
            return;
        }

        $balance = ($ingresos ?? 0) - ($egresos ?? 0);
        $balanceTexto = rtrim(rtrim(number_format($balance, 2, '.', ''), '0'), '.');

        $this->form['balance'] = "{$balanceTexto} ml";
    }

    private function consultaSignoVitalTriaje(int $signoVitalTriajeId): TriajeSignoVital
    {
        abort_if($this->atencion === null, 404);

        return TriajeSignoVital::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($signoVitalTriajeId);
    }

    private function diagnosticosIngreso(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return IngresoDiagnostico::query()
            ->with('diagnosticoCie10')
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora')
            ->latest('id')
            ->get()
            ->map(fn (IngresoDiagnostico $registro): array => [
                'id' => $registro->id,
                'codigo' => $registro->diagnosticoCie10?->codigo ?: '--',
                'denominacion' => $registro->diagnosticoCie10?->nombre ?: 'Sin diagnostico',
                'tipo' => $this->etiquetaTipoDiagnostico($registro->tipo_diagnostico),
                'observacion' => $registro->observacion ?: '-',
            ]);
    }

    private function evolucionesClinicas(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return Evolucion::query()
            ->with('profesionalSalud')
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora')
            ->latest('id')
            ->get()
            ->map(fn (Evolucion $registro): array => [
                'id' => $registro->id,
                'fecha_hora' => $registro->fecha_hora?->format('d/m/Y H:i') ?: '--',
                'profesional' => $registro->profesionalSalud?->nombres ?: 'Sin profesional',
                'iniciales' => $this->inicialesProfesional($registro->profesionalSalud?->nombres),
                'detalle_clinico' => $registro->detalle_clinico ?: '-',
            ]);
    }

    private function diagnosticosEgreso(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return EgresoDiagnostico::query()
            ->with('diagnosticoCie10')
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('id')
            ->get()
            ->map(fn (EgresoDiagnostico $registro): array => [
                'id' => $registro->id,
                'codigo' => $registro->codigo_cie10 ?: $registro->diagnosticoCie10?->codigo ?: '--',
                'denominacion' => $registro->denominacion ?: $registro->diagnosticoCie10?->nombre ?: 'Sin diagnostico',
                'clasificacion' => $this->valorEnum($registro->clasificacion) ?: '-',
                'tipo' => $this->etiquetaTipoDiagnostico($registro->tipo_diagnostico),
                'observacion' => $registro->observaciones ?: '-',
            ]);
    }

    private function ordenesClinicas(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return Orden::query()
            ->with(['tipoOrden', 'profesionalSalud'])
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora_solicitud')
            ->latest('id')
            ->get()
            ->map(fn (Orden $registro): array => [
                'id' => $registro->id,
                'tipo' => $registro->tipoOrden?->descripcion ?: 'Sin tipo',
                'detalle' => $registro->detalle ?: '-',
                'prioridad' => $this->valorEnum($registro->prioridad) ?: '-',
                'fecha_hora' => $registro->fecha_hora_solicitud?->format('d/m/Y H:i') ?: '--',
                'resultado' => $registro->resultado ?: 'Pendiente',
                'profesional' => $registro->profesionalSalud?->nombres ?: 'Sin profesional',
                'iniciales' => $this->inicialesProfesional($registro->profesionalSalud?->nombres),
            ]);
    }

    private function solicitudesExamenes(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return SolicitudExamen::query()
            ->with(['examen', 'profesionalSalud'])
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora_solicitud')
            ->latest('id')
            ->get()
            ->map(fn (SolicitudExamen $registro): array => [
                'id' => $registro->id,
                'examen' => $registro->examen?->descripcion ?: 'Sin examen',
                'prioridad' => $this->valorEnum($registro->prioridad) ?: '-',
                'fecha_hora' => $registro->fecha_hora_solicitud?->format('d/m/Y H:i') ?: '--',
                'resultado' => $registro->resultado ?: 'Pendiente',
                'estado' => $this->valorEnum($registro->estado) ?: EstadoSolicitudExamen::Solicitado->value,
                'profesional' => $registro->profesionalSalud?->nombres ?: 'Sin profesional',
                'iniciales' => $this->inicialesProfesional($registro->profesionalSalud?->nombres),
            ]);
    }

    private function prescripcionesMedicamentos(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return Prescripcion::query()
            ->with(['medicamentoCatalogo', 'profesionalSalud'])
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora')
            ->latest('id')
            ->get()
            ->map(fn (Prescripcion $registro): array => [
                'id' => $registro->id,
                'medicamento' => $registro->medicamento,
                'presentacion' => $registro->presentacion ?: '-',
                'dosis' => $registro->dosis ?: '-',
                'via' => $registro->via ?: '-',
                'duracion' => $registro->duracion ?: '-',
                'cantidad' => $registro->cantidad ?: '-',
                'fecha_hora' => $registro->fecha_hora?->format('d/m/Y H:i') ?: '--',
                'profesional' => $registro->profesionalSalud?->nombres ?: 'Sin profesional',
                'iniciales' => $this->inicialesProfesional($registro->profesionalSalud?->nombres),
                'origen' => $registro->medicamento_id ? 'Catalogado' : 'No catalogado',
            ]);
    }

    private function procedimientosRealizados(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return ProcedimientoRealizado::query()
            ->with('procedimiento')
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora')
            ->latest('id')
            ->get()
            ->map(fn (ProcedimientoRealizado $registro): array => [
                'id' => $registro->id,
                'codigo' => $registro->procedimiento?->codigo ?: '--',
                'denominacion' => $registro->denominacion ?: $registro->procedimiento?->denominacion ?: 'Sin procedimiento',
                'sesiones' => $registro->sesiones ?: '-',
                'fecha_hora' => $registro->fecha_hora?->format('d/m/Y H:i') ?: '--',
                'origen' => $registro->procedimiento_id ? 'Catalogado' : 'No catalogado',
            ]);
    }

    private function notasEnfermeria(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return EnfermeriaNota::query()
            ->with('profesionalSalud')
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora')
            ->latest('id')
            ->get()
            ->map(fn (EnfermeriaNota $registro): array => [
                'id' => $registro->id,
                'fecha' => $registro->fecha_hora?->format('d/m/Y') ?: '--',
                'hora' => $registro->fecha_hora?->format('H:i') ?: '--',
                'profesional' => $registro->profesionalSalud?->nombres ?: 'Sin profesional',
                'iniciales' => $this->inicialesProfesional($registro->profesionalSalud?->nombres),
                'nota' => $registro->nota,
            ]);
    }

    private function interconsultasClinicas(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return Interconsulta::query()
            ->with('profesionalSalud.especialidad')
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora')
            ->latest('id')
            ->get()
            ->map(fn (Interconsulta $registro): array => [
                'id' => $registro->id,
                'fecha' => $registro->fecha_hora?->format('d/m/Y') ?: '--',
                'hora' => $registro->fecha_hora?->format('H:i') ?: '--',
                'profesional' => $registro->profesionalSalud?->nombres ?: 'Sin profesional',
                'especialidad' => $registro->profesionalSalud?->especialidad?->nombre ?: 'Sin especialidad',
                'iniciales' => $this->inicialesProfesional($registro->profesionalSalud?->nombres),
                'nota' => $registro->nota ?: '-',
            ]);
    }

    private function transferenciasClinicas(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return Transferencia::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora')
            ->latest('id')
            ->get()
            ->map(fn (Transferencia $registro): array => [
                'id' => $registro->id,
                'fecha_hora' => $registro->fecha_hora?->format('d/m/Y H:i') ?: '--',
                'origen' => $registro->origen ?: '-',
                'destino' => $registro->destino ?: '-',
                'motivo' => $registro->motivo ?: '-',
                'responsable' => $registro->responsable ?: 'Sin responsable',
                'iniciales' => $this->inicialesProfesional($registro->responsable),
            ]);
    }

    private function referenciasClinicas(): Collection
    {
        if ($this->atencion === null) {
            return collect();
        }

        return HcReferencia::query()
            ->with(['upsOrigen', 'upsDestino'])
            ->where('hc_emergencia_id', $this->atencion->id)
            ->latest('fecha_hora_solicitud')
            ->latest('id')
            ->get()
            ->map(fn (HcReferencia $registro): array => [
                'id' => $registro->id,
                'ipress_origen' => $registro->ipress_origen ?: '-',
                'ups_origen' => $registro->upsOrigen?->denominacion ?: '-',
                'ipress_referencia' => $registro->ipress_referencia ?: '-',
                'ups_destino' => $registro->upsDestino?->denominacion ?: '-',
                'fecha_hora_solicitud' => $registro->fecha_hora_solicitud?->format('d/m/Y H:i') ?: '--',
                'respuesta_solicitud' => $registro->respuesta_solicitud ?: 'Pendiente',
                'fecha_hora_respuesta' => $registro->fecha_hora_respuesta?->format('d/m/Y H:i') ?: '--',
            ]);
    }

    /**
     * @return array{ingresos: string, egresos: string, balance: string, balance_numero: float|null, estado: string, clase: string}
     */
    private function balanceHidrico(): array
    {
        $balance = $this->valorNumerico($this->form['balance'] ?? null);

        return [
            'ingresos' => $this->form['ingresos'] ?: '0 ml',
            'egresos' => $this->form['egresos'] ?: '0 ml',
            'balance' => $this->form['balance'] ?: '0 ml',
            'balance_numero' => $balance,
            'estado' => $balance === null || $balance === 0.0 ? 'Balance Neutro' : ($balance > 0 ? 'Balance Positivo' : 'Balance Negativo'),
            'clase' => $balance === null || $balance === 0.0 ? 'is-neutral' : ($balance > 0 ? 'is-positive' : 'is-negative'),
        ];
    }

    private function consultaDiagnosticoIngreso(int $diagnosticoIngresoId): IngresoDiagnostico
    {
        abort_if($this->atencion === null, 404);

        return IngresoDiagnostico::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($diagnosticoIngresoId);
    }

    private function consultaEvolucion(int $evolucionId): Evolucion
    {
        abort_if($this->atencion === null, 404);

        return Evolucion::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($evolucionId);
    }

    private function consultaDiagnosticoEgreso(int $diagnosticoEgresoId): EgresoDiagnostico
    {
        abort_if($this->atencion === null, 404);

        return EgresoDiagnostico::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($diagnosticoEgresoId);
    }

    private function consultaOrden(int $ordenId): Orden
    {
        abort_if($this->atencion === null, 404);

        return Orden::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($ordenId);
    }

    private function consultaSolicitudExamen(int $solicitudExamenId): SolicitudExamen
    {
        abort_if($this->atencion === null, 404);

        return SolicitudExamen::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($solicitudExamenId);
    }

    private function consultaPrescripcion(int $prescripcionId): Prescripcion
    {
        abort_if($this->atencion === null, 404);

        return Prescripcion::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($prescripcionId);
    }

    private function consultaProcedimientoRealizado(int $procedimientoRealizadoId): ProcedimientoRealizado
    {
        abort_if($this->atencion === null, 404);

        return ProcedimientoRealizado::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($procedimientoRealizadoId);
    }

    private function consultaNotaEnfermeria(int $notaEnfermeriaId): EnfermeriaNota
    {
        abort_if($this->atencion === null, 404);

        return EnfermeriaNota::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($notaEnfermeriaId);
    }

    private function consultaInterconsulta(int $interconsultaId): Interconsulta
    {
        abort_if($this->atencion === null, 404);

        return Interconsulta::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($interconsultaId);
    }

    private function consultaTransferencia(int $transferenciaId): Transferencia
    {
        abort_if($this->atencion === null, 404);

        return Transferencia::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($transferenciaId);
    }

    private function consultaReferencia(int $referenciaId): HcReferencia
    {
        abort_if($this->atencion === null, 404);

        return HcReferencia::query()
            ->where('hc_emergencia_id', $this->atencion->id)
            ->findOrFail($referenciaId);
    }

    private function cargarNotaEnfermeriaFormulario(int $notaEnfermeriaId, bool $soloLectura): void
    {
        $registro = $this->consultaNotaEnfermeria($notaEnfermeriaId);

        $this->notaEnfermeriaEditandoId = $registro->id;
        $this->notaEnfermeriaSoloLectura = $soloLectura;
        $this->notaEnfermeriaForm = [
            'fecha_hora' => $registro->fecha_hora?->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $registro->profesional_salud_id,
            'nota' => $registro->nota,
            'firma' => $registro->firma,
        ];
        $this->profesionalEnfermeriaBusqueda = $this->etiquetaProfesionalEvolucionId($registro->profesional_salud_id);
        $this->formularioNotaEnfermeriaAbierto = true;
        $this->resetValidation(['notaEnfermeriaForm.fecha_hora', 'notaEnfermeriaForm.profesional_salud_id', 'notaEnfermeriaForm.nota', 'notaEnfermeriaForm.firma']);
    }

    private function cargarInterconsultaFormulario(int $interconsultaId, bool $soloLectura): void
    {
        $registro = $this->consultaInterconsulta($interconsultaId);

        $this->interconsultaEditandoId = $registro->id;
        $this->interconsultaSoloLectura = $soloLectura;
        $this->interconsultaForm = [
            'fecha_hora' => $registro->fecha_hora?->format('Y-m-d\TH:i'),
            'profesional_salud_id' => $registro->profesional_salud_id,
            'nota' => $registro->nota,
        ];
        $this->profesionalInterconsultaBusqueda = $this->etiquetaProfesionalEvolucionId($registro->profesional_salud_id);
        $this->formularioInterconsultaAbierto = true;
        $this->resetValidation(['interconsultaForm.fecha_hora', 'interconsultaForm.profesional_salud_id', 'interconsultaForm.nota']);
    }

    private function cargarTransferenciaFormulario(int $transferenciaId, bool $soloLectura): void
    {
        $registro = $this->consultaTransferencia($transferenciaId);

        $this->transferenciaEditandoId = $registro->id;
        $this->transferenciaSoloLectura = $soloLectura;
        $this->transferenciaForm = [
            'fecha_hora' => $registro->fecha_hora?->format('Y-m-d\TH:i'),
            'origen' => $registro->origen,
            'destino' => $registro->destino,
            'motivo' => $registro->motivo,
            'responsable' => $registro->responsable,
        ];
        $this->transferenciaResponsableBusqueda = $registro->responsable ?: '';
        $this->formularioTransferenciaAbierto = true;
        $this->resetValidation(['transferenciaForm.fecha_hora', 'transferenciaForm.origen', 'transferenciaForm.destino', 'transferenciaForm.motivo', 'transferenciaForm.responsable']);
    }

    private function cargarReferenciaFormulario(int $referenciaId, bool $soloLectura): void
    {
        $registro = $this->consultaReferencia($referenciaId);

        $this->referenciaEditandoId = $registro->id;
        $this->referenciaSoloLectura = $soloLectura;
        $this->referenciaForm = [
            'ipress_origen' => $registro->ipress_origen,
            'ups_origen_id' => $registro->ups_origen_id,
            'ipress_referencia' => $registro->ipress_referencia,
            'ups_destino_id' => $registro->ups_destino_id,
            'motivo_referencia' => $registro->motivo_referencia,
            'fecha_hora_solicitud' => $registro->fecha_hora_solicitud?->format('Y-m-d\TH:i'),
            'respuesta_solicitud' => $registro->respuesta_solicitud,
            'fecha_hora_respuesta' => $registro->fecha_hora_respuesta?->format('Y-m-d\TH:i'),
        ];
        $this->formularioReferenciaAbierto = true;
        $this->resetValidation(['referenciaForm.ipress_origen', 'referenciaForm.ups_origen_id', 'referenciaForm.ipress_referencia', 'referenciaForm.ups_destino_id', 'referenciaForm.motivo_referencia', 'referenciaForm.fecha_hora_solicitud', 'referenciaForm.respuesta_solicitud', 'referenciaForm.fecha_hora_respuesta']);
    }

    /**
     * @return array<int, string>
     */
    private function resultadosShockTrauma(): array
    {
        return ['Estabilizado', 'Traslado critico', 'Observacion continua', 'Ingreso a UCI', 'Derivado a procedimiento'];
    }

    /**
     * @return array<int, string>
     */
    private function destinosShockTrauma(): array
    {
        return ['Unidad de Cuidados Intensivos (UCI)', 'Sala de observacion', 'Centro quirurgico', 'Topico de medicina', 'Otra UPSS'];
    }

    /**
     * @return array<int, string>
     */
    private function estadosRespuestaReferencia(): array
    {
        return ['Pendiente', 'Aceptada', 'Rechazada', 'Observada'];
    }

    private function etiquetaTipoDiagnostico(TipoDiagnostico|string|null $tipo): string
    {
        $valor = $this->valorEnum($tipo);

        return match ($valor) {
            TipoDiagnostico::Presuntivo->value => 'PRESUNTIVO',
            TipoDiagnostico::Definitivo->value => 'DEFINITIVO',
            TipoDiagnostico::Repetido->value => 'REPETIDO',
            default => '-',
        };
    }

    private function inicialesProfesional(?string $nombre): string
    {
        if (blank($nombre)) {
            return '--';
        }

        return Str::of($nombre)
            ->replaceMatches('/\b(Dr|Dra|Lic|Mg|Sr|Sra)\.?\s*/i', '')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $parte): string => Str::of($parte)->substr(0, 1)->upper()->toString())
            ->implode('');
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
