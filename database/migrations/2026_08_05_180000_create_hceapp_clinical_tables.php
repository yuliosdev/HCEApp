<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas clinicas aprobadas para HCEApp.
     */
    public function up(): void
    {
        $this->crearCatalogosClinicos();
        $this->crearTablasDePacientes();
        $this->crearTablaCentralDeEmergencia();
        $this->crearTablasClinicasDelEpisodio();
    }

    /**
     * Elimina las tablas en orden inverso para respetar las claves foraneas.
     */
    public function down(): void
    {
        $tablas = [
            'documentos_adjuntos',
            'alta_medicamentos',
            'egreso_diagnosticos',
            'transferencias',
            'enfermeria_notas',
            'interconsultas',
            'evoluciones',
            'procedimientos_realizados',
            'prescripciones',
            'ordenes',
            'solicitud_examenes',
            'ingreso_diagnosticos',
            'medicamentos_habituales',
            'triaje_signos_vitales',
            'hc_emergencia',
            'antecedentes_familiares',
            'hospitalizaciones_previas',
            'cirugias_previas',
            'reacciones_adversas_medicamentos',
            'antecedentes_patologicos',
            'paciente_alergias',
            'pacientes',
            'medicamentos',
            'tipos_ordenes',
            'examenes',
            'profesionales_salud',
            'especialidades',
            'alergias',
            'diagnosticos_cie10',
            'signos_vitales',
            'procedimientos',
            'prioridades',
        ];

        foreach ($tablas as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }

    /**
     * Agrega auditoria basica relacionada con users.
     */
    private function auditoriaBasica(Blueprint $table, bool $usaEliminacionLogica = false): void
    {
        $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();

        if ($usaEliminacionLogica) {
            $table->softDeletes();
        }
    }

    /**
     * Estados generales para catalogos administrables.
     *
     * @return array<int, string>
     */
    private function estadosCatalogo(): array
    {
        return ['Activo', 'Inactivo'];
    }

    /**
     * Valores de uso comun para preguntas binarias.
     *
     * @return array<int, string>
     */
    private function siNo(): array
    {
        return ['Si', 'No'];
    }

    /**
     * Valores de uso comun cuando el paciente no conoce la respuesta.
     *
     * @return array<int, string>
     */
    private function siNoNoSabe(): array
    {
        return ['Si', 'No', 'No sabe'];
    }

    /**
     * Generos aplicables al catalogo CIE10.
     *
     * @return array<int, string>
     */
    private function generosCie10(): array
    {
        return ['Masculino', 'Femenino', 'Ambos'];
    }

    /**
     * Grupos sanguineos especificados en el formato clinico.
     *
     * @return array<int, string>
     */
    private function gruposSanguineos(): array
    {
        return ['A', 'B', 'O', 'AB', 'No conocido'];
    }

    /**
     * Factores RH especificados en el formato clinico.
     *
     * @return array<int, string>
     */
    private function factoresRh(): array
    {
        return ['+', '-', 'No conocido'];
    }

    /**
     * Tipos de reaccion adversa a medicamentos.
     *
     * @return array<int, string>
     */
    private function tiposReaccionAdversaMedicamento(): array
    {
        return ['1 Reaccion adversa', '2 Error de medicacion', '3 Problema de calidad', '4 Otro'];
    }

    /**
     * Gravedades de reaccion adversa a medicamentos.
     *
     * @return array<int, string>
     */
    private function gravedadesReaccionAdversaMedicamento(): array
    {
        return ['1 Leve', '2 Moderado', '3 Grave'];
    }

    /**
     * Parentescos de antecedentes familiares.
     *
     * @return array<int, string>
     */
    private function parentescosFamiliares(): array
    {
        return [
            '1.MADRE',
            '2.PADRE',
            '3.HERMANO',
            '4.ABUELO(A) MATERNA',
            '5.ABUELO(A) PATERNA',
            '6.TIO(A) MATERNA',
            '7.TIO(A) PATERNA',
        ];
    }

    /**
     * Topicos clinicos de atencion inicial.
     *
     * @return array<int, string>
     */
    private function topicosAtencion(): array
    {
        return ['Topico', 'Sala de reanimacion'];
    }

    /**
     * Fuente de procedencia del paciente.
     *
     * @return array<int, string>
     */
    private function fuentesAtencion(): array
    {
        return ['Servicio medico de emergencia', 'Otro'];
    }

    /**
     * Colores de prioridad definidos para triaje.
     *
     * @return array<int, string>
     */
    private function coloresTriaje(): array
    {
        return ['Rojo', 'Naranja', 'Amarillo', 'Verde', 'Azul'];
    }

    /**
     * Unidades permitidas para tiempo de enfermedad.
     *
     * @return array<int, string>
     */
    private function unidadesTiempoEnfermedad(): array
    {
        return ['Min', 'Hrs', 'Dias', 'Meses', 'Anos'];
    }

    /**
     * Estados de firma del episodio clinico.
     *
     * @return array<int, string>
     */
    private function estadosFirma(): array
    {
        return ['Pendiente', 'Firmado'];
    }

    /**
     * Condiciones de egreso acordadas para HCEApp.
     *
     * @return array<int, string>
     */
    private function condicionesEgreso(): array
    {
        return ['1.CURADO', '2.MEJORADO', '3.CONTROLADO', '4.EMPEORADO', '5.FALLECIDO', '6.DESERCION'];
    }

    /**
     * Destinos dentro de la IPRESS.
     *
     * @return array<int, string>
     */
    private function destinosDentroIpress(): array
    {
        return ['Hospitalizacion', 'UCI', 'Centro quirurgico', 'Centro obstetrico', 'Otra UPSS', '6.DESERCION'];
    }

    /**
     * Destinos fuera de la IPRESS.
     *
     * @return array<int, string>
     */
    private function destinosFueraIpress(): array
    {
        return ['Transferencia a otra IPRESS', 'Otro destino no configurado'];
    }

    /**
     * Estados permitidos para la atencion.
     *
     * @return array<int, string>
     */
    private function estadosAtencion(): array
    {
        return ['Abierta', 'Cerrada'];
    }

    /**
     * Tipos de diagnostico de ingreso y egreso.
     *
     * @return array<int, string>
     */
    private function tiposDiagnostico(): array
    {
        return ['P', 'D', 'R'];
    }

    /**
     * Prioridades para solicitudes de examenes.
     *
     * @return array<int, string>
     */
    private function prioridadesSolicitudExamen(): array
    {
        return ['Urgente', 'Emergencia'];
    }

    /**
     * Estados para solicitudes de examenes.
     *
     * @return array<int, string>
     */
    private function estadosSolicitudExamen(): array
    {
        return ['Solicitado', 'En proceso', 'Terminado'];
    }

    /**
     * Prioridades para ordenes clinicas.
     *
     * @return array<int, string>
     */
    private function prioridadesOrden(): array
    {
        return ['Urgente', 'Emergencia', 'Inmediata'];
    }

    /**
     * Clasificacion del diagnostico de egreso.
     *
     * @return array<int, string>
     */
    private function clasificacionesDiagnosticoEgreso(): array
    {
        return ['Principal', 'Secundario'];
    }

    /**
     * Tipos de documentos adjuntos permitidos.
     *
     * @return array<int, string>
     */
    private function tiposDocumentoAdjunto(): array
    {
        return ['Imagen', 'PDF', 'Otro'];
    }

    /**
     * Crea catalogos reutilizables por los modulos clinicos.
     */
    private function crearCatalogosClinicos(): void
    {
        Schema::create('prioridades', function (Blueprint $table) {
            $table->id();
            $table->string('prioridad', 100)->unique();
            $table->enum('color', $this->coloresTriaje())->nullable();
            $table->unsignedTinyInteger('orden')->nullable()->unique();
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $this->auditoriaBasica($table, true);
        });

        Schema::create('procedimientos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('denominacion');
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $this->auditoriaBasica($table, true);
        });

        Schema::create('signos_vitales', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 150);
            $table->string('unidad', 50)->nullable();
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $this->auditoriaBasica($table, true);
            $table->unique(['descripcion', 'unidad']);
        });

        Schema::create('diagnosticos_cie10', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->enum('genero', $this->generosCie10())->default('Ambos');
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $this->auditoriaBasica($table, true);
        });

        Schema::create('alergias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('tipo', 100)->nullable();
            $table->string('descripcion')->nullable();
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $this->auditoriaBasica($table, true);
            $table->index('nombre');
        });

        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->string('descripcion')->nullable();
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $this->auditoriaBasica($table, true);
        });

        Schema::create('profesionales_salud', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->foreignId('especialidad_id')->nullable()->constrained('especialidades')->nullOnDelete();
            $table->string('tipo_colegiatura', 100)->nullable();
            $table->string('cmp', 30)->nullable()->index();
            $table->string('rne', 30)->nullable()->index();
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $table->string('codigo_servicio', 50)->nullable();
            $this->auditoriaBasica($table, true);
        });

        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $table->unsignedBigInteger('id_sistema_externo')->nullable();
            $this->auditoriaBasica($table, true);
        });

        Schema::create('tipos_ordenes', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 150);
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $this->auditoriaBasica($table, true);
        });

        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->string('presentacion', 150)->nullable();
            $table->string('generico')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->enum('estado', $this->estadosCatalogo())->default('Activo');
            $table->unsignedBigInteger('id_producto_externo')->nullable();
            $this->auditoriaBasica($table, true);
        });
    }

    /**
     * Crea tablas maestras e historicas asociadas al paciente.
     */
    private function crearTablasDePacientes(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_historia', 50)->nullable()->unique();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('sexo', 30)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('tipo_documento', 30)->nullable();
            $table->string('numero_documento', 30)->nullable();
            $table->enum('grupo_sanguineo', $this->gruposSanguineos())->default('No conocido');
            $table->enum('factor_rh', $this->factoresRh())->default('No conocido');
            $table->unsignedBigInteger('id_paciente_externo')->nullable();
            $this->auditoriaBasica($table, true);
            $table->unique(['tipo_documento', 'numero_documento']);
            $table->index('nombre');
        });

        Schema::create('paciente_alergias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('alergia_id')->constrained('alergias')->restrictOnDelete();
            $table->string('agente_causal')->nullable();
            $table->string('alergeno')->nullable();
            $table->string('periodo', 20)->nullable();
            $this->auditoriaBasica($table, true);
        });

        Schema::create('antecedentes_patologicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('diagnostico_cie10_id')->nullable()->constrained('diagnosticos_cie10')->nullOnDelete();
            $table->string('denominacion')->nullable();
            $table->string('origen', 50)->nullable();
            $table->string('periodo', 20)->nullable();
            $this->auditoriaBasica($table, true);
        });

        Schema::create('reacciones_adversas_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->text('descripcion');
            $table->enum('tipo', $this->tiposReaccionAdversaMedicamento())->nullable();
            $table->enum('gravedad', $this->gravedadesReaccionAdversaMedicamento())->nullable();
            $table->string('dci_medicamento')->nullable();
            $table->string('periodo', 20)->nullable();
            $this->auditoriaBasica($table, true);
        });

        Schema::create('cirugias_previas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->string('codigo_cpms', 30)->nullable();
            $table->string('denominacion')->nullable();
            $table->string('periodo', 20)->nullable();
            $this->auditoriaBasica($table, true);
        });

        Schema::create('hospitalizaciones_previas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->string('codigo_ups', 30)->nullable();
            $table->foreignId('diagnostico_cie10_id')->nullable()->constrained('diagnosticos_cie10')->nullOnDelete();
            $table->string('denominacion')->nullable();
            $table->string('periodo', 20)->nullable();
            $this->auditoriaBasica($table, true);
        });

        Schema::create('antecedentes_familiares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->enum('parentesco', $this->parentescosFamiliares())->nullable();
            $table->foreignId('diagnostico_cie10_id')->nullable()->constrained('diagnosticos_cie10')->nullOnDelete();
            $table->string('denominacion')->nullable();
            $table->string('periodo', 20)->nullable();
            $this->auditoriaBasica($table, true);
        });
    }

    /**
     * Crea la tabla central del episodio de emergencia.
     */
    private function crearTablaCentralDeEmergencia(): void
    {
        Schema::create('hc_emergencia', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_atencion')->index();
            $table->dateTime('fecha_hora_inicio_episodio')->nullable();
            $table->dateTime('fecha_hora_atencion')->nullable()->index();
            $table->enum('topico', $this->topicosAtencion())->nullable();
            $table->string('ambiente', 150)->nullable();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->enum('sin_identificacion', $this->siNo())->default('No');
            $table->string('acompanante')->nullable();
            $table->string('parentesco', 100)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->enum('fuente', $this->fuentesAtencion())->nullable();
            $table->text('lugar_evento')->nullable();
            $table->text('tipo_lesion_evento')->nullable();
            $table->text('signos_vitales_transmitidos')->nullable();
            $table->dateTime('fecha_hora_recepcion')->nullable();
            $table->dateTime('fecha_hora_triaje')->nullable();
            $table->foreignId('profesional_triaje_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $table->foreignId('prioridad_id')->nullable()->constrained('prioridades')->nullOnDelete();
            $table->enum('color_triaje', $this->coloresTriaje())->nullable();
            $table->boolean('triaje')->default(false);
            $table->string('destino_topico', 150)->nullable();
            $table->string('tiempo_espera_calculado', 50)->nullable();
            $table->dateTime('reevaluacion_programada')->nullable();
            $table->dateTime('fecha_hora_anamnesis')->nullable();
            $table->integer('tiempo_enfermedad')->nullable();
            $table->enum('unidad_tiempo_enfermedad', $this->unidadesTiempoEnfermedad())->nullable();
            $table->text('signos_sintomas')->nullable();
            $table->text('desarrollo_cronologico')->nullable();
            $table->enum('presenta_ram', $this->siNoNoSabe())->nullable();
            $table->enum('presenta_alergias', $this->siNoNoSabe())->nullable();
            $table->integer('eg_ocular')->nullable();
            $table->integer('eg_verbal')->nullable();
            $table->integer('eg_motora')->nullable();
            $table->integer('eg_total')->nullable();
            $table->text('estado_general')->nullable();
            $table->text('hidratacion')->nullable();
            $table->text('nutricion')->nullable();
            $table->text('estado_conciencia')->nullable();
            $table->text('piel_anexos')->nullable();
            $table->text('cabeza_cuello')->nullable();
            $table->text('torax_respiratorio')->nullable();
            $table->text('cardiovascular')->nullable();
            $table->text('abdomen')->nullable();
            $table->text('neurologico')->nullable();
            $table->text('musculoesqueletico_extremidades')->nullable();
            $table->text('genitourinario_ginecoobstetrico')->nullable();
            $table->text('otros')->nullable();
            $table->enum('en_shock_trauma', $this->siNo())->default('No');
            $table->dateTime('shock_trauma_fecha_hora')->nullable();
            $table->string('shock_trauma_responsable')->nullable();
            $table->text('shock_trauma_resultado')->nullable();
            $table->string('shock_trauma_destino', 150)->nullable();
            $table->text('registro_especializado')->nullable();
            $table->text('plan_trabajo')->nullable();
            $table->foreignId('profesional_salud_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $table->dateTime('fecha_hora_firma')->nullable();
            $table->enum('estado_firma', $this->estadosFirma())->default('Pendiente');
            $table->string('ingresos', 100)->nullable();
            $table->string('egresos', 100)->nullable();
            $table->string('balance', 100)->nullable();
            $table->enum('condicion_egreso', $this->condicionesEgreso())->nullable();
            $table->enum('destino_dentro_ipress', $this->destinosDentroIpress())->nullable();
            $table->enum('destino_fuera_ipress', $this->destinosFueraIpress())->nullable();
            $table->dateTime('fecha_hora_egreso')->nullable();
            $table->enum('estado_atencion', $this->estadosAtencion())->default('Abierta')->index();
            $this->auditoriaBasica($table, true);
        });
    }

    /**
     * Crea tablas dependientes del episodio de emergencia.
     */
    private function crearTablasClinicasDelEpisodio(): void
    {
        Schema::create('triaje_signos_vitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('signo_vital_id')->constrained('signos_vitales')->restrictOnDelete();
            $table->string('valor', 100);
            $this->auditoriaBasica($table);
        });

        Schema::create('medicamentos_habituales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->string('medicacion');
            $table->string('periodo', 20)->nullable();
            $this->auditoriaBasica($table);
        });

        Schema::create('ingreso_diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('diagnostico_cie10_id')->constrained('diagnosticos_cie10')->restrictOnDelete();
            $table->enum('tipo_diagnostico', $this->tiposDiagnostico());
            $table->text('observacion')->nullable();
            $table->dateTime('fecha_hora')->nullable();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora']);
        });

        Schema::create('solicitud_examenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('examen_id')->constrained('examenes')->restrictOnDelete();
            $table->enum('prioridad', $this->prioridadesSolicitudExamen())->nullable();
            $table->dateTime('fecha_hora_solicitud')->nullable();
            $table->text('resultado')->nullable();
            $table->enum('estado', $this->estadosSolicitudExamen())->default('Solicitado');
            $table->foreignId('profesional_salud_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora_solicitud']);
        });

        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('tipo_orden_id')->constrained('tipos_ordenes')->restrictOnDelete();
            $table->text('detalle');
            $table->enum('prioridad', $this->prioridadesOrden())->nullable();
            $table->dateTime('fecha_hora_solicitud')->nullable();
            $table->text('resultado')->nullable();
            $table->foreignId('profesional_salud_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora_solicitud']);
        });

        Schema::create('prescripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('medicamento_id')->nullable()->constrained('medicamentos')->nullOnDelete();
            $table->string('medicamento');
            $table->string('presentacion', 150)->nullable();
            $table->string('dosis', 100)->nullable();
            $table->string('via', 100)->nullable();
            $table->string('duracion', 100)->nullable();
            $table->integer('cantidad')->nullable();
            $table->dateTime('fecha_hora')->nullable();
            $table->foreignId('profesional_salud_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora']);
        });

        Schema::create('procedimientos_realizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('procedimiento_id')->nullable()->constrained('procedimientos')->nullOnDelete();
            $table->string('denominacion')->nullable();
            $table->integer('sesiones')->nullable();
            $table->dateTime('fecha_hora')->nullable();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora']);
        });

        Schema::create('evoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->dateTime('fecha_hora');
            $table->text('apreciacion_subjetiva')->nullable();
            $table->text('apreciacion_objetiva')->nullable();
            $table->text('funciones_vitales')->nullable();
            $table->text('tratamiento_dieta')->nullable();
            $table->text('interpretacion_examenes')->nullable();
            $table->text('plan_trabajo')->nullable();
            $table->text('procedimientos_realizados')->nullable();
            $table->foreignId('profesional_salud_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora']);
        });

        Schema::create('interconsultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('profesional_salud_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $table->dateTime('fecha_hora')->nullable();
            $table->text('nota')->nullable();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora']);
        });

        Schema::create('enfermeria_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->dateTime('fecha_hora')->nullable();
            $table->foreignId('profesional_salud_id')->nullable()->constrained('profesionales_salud')->nullOnDelete();
            $table->text('nota');
            $table->string('firma')->nullable();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora']);
        });

        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->dateTime('fecha_hora')->nullable();
            $table->string('origen', 150)->nullable();
            $table->string('destino', 150)->nullable();
            $table->text('motivo')->nullable();
            $table->string('responsable')->nullable();
            $this->auditoriaBasica($table);
            $table->index(['hc_emergencia_id', 'fecha_hora']);
        });

        Schema::create('egreso_diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('diagnostico_cie10_id')->constrained('diagnosticos_cie10')->restrictOnDelete();
            $table->enum('clasificacion', $this->clasificacionesDiagnosticoEgreso())->nullable();
            $table->string('codigo_cie10', 20)->nullable();
            $table->string('denominacion')->nullable();
            $table->enum('tipo_diagnostico', $this->tiposDiagnostico())->nullable();
            $table->text('observaciones')->nullable();
            $this->auditoriaBasica($table);
        });

        Schema::create('alta_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->foreignId('medicamento_id')->nullable()->constrained('medicamentos')->nullOnDelete();
            $table->string('medicamento');
            $table->string('presentacion', 150)->nullable();
            $table->string('dosis', 100)->nullable();
            $table->string('via', 100)->nullable();
            $table->text('indicaciones')->nullable();
            $this->auditoriaBasica($table);
        });

        Schema::create('documentos_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hc_emergencia_id')->constrained('hc_emergencia')->restrictOnDelete();
            $table->enum('tipo', $this->tiposDocumentoAdjunto());
            $table->text('contenido')->nullable();
            $table->string('ruta_archivo');
            $this->auditoriaBasica($table);
        });
    }
};
