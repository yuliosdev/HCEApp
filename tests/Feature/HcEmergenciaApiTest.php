<?php

namespace Tests\Feature;

use App\Enums\EstadoAtencion;
use App\Enums\EstadoCatalogo;
use App\Enums\EstadoSolicitudExamen;
use App\Enums\GeneroCie10;
use App\Enums\PrioridadSolicitudExamen;
use App\Models\Examen;
use App\Models\DiagnosticoCie10;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use App\Models\Medicamento;
use App\Models\Orden;
use App\Models\Paciente;
use App\Models\Prescripcion;
use App\Models\Procedimiento;
use App\Models\ProfesionalSalud;
use App\Models\SolicitudExamen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HcEmergenciaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_responde_error_cuando_la_atencion_no_existe(): void
    {
        $respuesta = $this->postJson(route('api.hc-emergencia.registrar-datos-atencion'), [
            'hce_id' => 999999,
        ]);

        $respuesta
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe la atención',
            ]);
    }

    public function test_responde_error_cuando_la_atencion_esta_cerrada(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Cerrada,
        ]);

        $respuesta = $this->postJson(route('api.hc-emergencia.registrar-datos-atencion'), [
            'hce_id' => $atencion->id,
        ]);

        $respuesta
            ->assertStatus(409)
            ->assertJson([
                'message' => "La atención existe pero está 'Cerrada'",
            ]);
    }

    public function test_registra_paciente_y_profesional_en_una_atencion_abierta(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
            'numero_atencion' => null,
            'paciente_id' => null,
            'profesional_salud_id' => null,
        ]);

        $respuesta = $this->postJson(route('api.hc-emergencia.registrar-datos-atencion'), $this->payloadBase($atencion->id));

        $respuesta
            ->assertOk()
            ->assertJson([
                'message' => 'Se actualizaron datos en la atención',
            ])
            ->assertJsonStructure([
                'data' => ['hce_id', 'numero_atencion', 'paciente_id', 'profesional_salud_id'],
            ]);

        $paciente = Paciente::query()->where('id_paciente_externo', 789875)->firstOrFail();
        $profesional = ProfesionalSalud::query()->where('codigo_servicio', 'MED-001')->firstOrFail();

        $this->assertSame('Pedro Angeles', $paciente->nombre);
        $this->assertSame('12345678', $paciente->numero_documento);
        $this->assertSame('Jhon Montoya Nakanichi', $profesional->nombres);
        $this->assertSame('023440', $profesional->cmp);

        $atencion->refresh();
        $this->assertSame(100234, $atencion->numero_atencion);
        $this->assertTrue($atencion->paciente->is($paciente));
        $this->assertTrue($atencion->profesionalResponsable->is($profesional));
        $this->assertSame('No', $atencion->sin_identificacion->value);
    }

    public function test_actualiza_paciente_y_profesional_existentes_por_llaves_externas(): void
    {
        $paciente = Paciente::factory()->create([
            'nombre' => 'Paciente anterior',
            'id_paciente_externo' => 789875,
            'numero_documento' => '11111111',
        ]);
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Medico anterior',
            'codigo_servicio' => 'MED-001',
            'cmp' => '000000',
        ]);
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
            'paciente_id' => null,
            'profesional_salud_id' => null,
        ]);

        $respuesta = $this->postJson(route('api.hc-emergencia.registrar-datos-atencion'), $this->payloadBase($atencion->id));

        $respuesta->assertOk();

        $paciente->refresh();
        $profesional->refresh();
        $atencion->refresh();

        $this->assertSame('Pedro Angeles', $paciente->nombre);
        $this->assertSame('12345678', $paciente->numero_documento);
        $this->assertSame('Jhon Montoya Nakanichi', $profesional->nombres);
        $this->assertSame('023440', $profesional->cmp);
        $this->assertSame(100234, $atencion->numero_atencion);
        $this->assertSame($paciente->id, $atencion->paciente_id);
        $this->assertSame($profesional->id, $atencion->profesional_salud_id);
    }

    public function test_responde_error_cuando_no_existe_numero_de_admision(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);

        $payloadSinNumero = $this->payloadBase($atencion->id);
        $payloadSinNumero['numero_atencion'] = '';

        $this->postJson(route('api.hc-emergencia.registrar-datos-atencion'), $payloadSinNumero)
            ->assertStatus(422)
            ->assertJson([
                'message' => 'No existe número de admisión',
            ]);

        $payloadConCero = $this->payloadBase($atencion->id);
        $payloadConCero['numero_atencion'] = 0;

        $this->postJson(route('api.hc-emergencia.registrar-datos-atencion'), $payloadConCero)
            ->assertStatus(422)
            ->assertJson([
                'message' => 'No existe número de admisión',
            ]);
    }

    public function test_devuelve_todos_los_examenes_solicitados_de_la_atencion(): void
    {
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Jhon Montoya Nakanichi',
            'codigo_servicio' => 'MED-001',
        ]);
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100234,
        ]);
        $hemograma = Examen::query()->create([
            'descripcion' => 'Hemograma completo',
            'id_sistema_externo' => 501,
        ]);
        $glucosa = Examen::query()->create([
            'descripcion' => 'Glucosa',
            'id_sistema_externo' => 502,
        ]);
        $radiografia = Examen::query()->create([
            'descripcion' => 'Radiografia de torax',
            'id_sistema_externo' => 503,
        ]);

        SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $hemograma->id,
            'prioridad' => PrioridadSolicitudExamen::Emergencia,
            'estado' => EstadoSolicitudExamen::Solicitado,
            'profesional_salud_id' => $profesional->id,
        ]);
        SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $glucosa->id,
            'prioridad' => PrioridadSolicitudExamen::Urgente,
            'estado' => EstadoSolicitudExamen::Solicitado,
            'profesional_salud_id' => $profesional->id,
        ]);
        SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $radiografia->id,
            'prioridad' => PrioridadSolicitudExamen::Urgente,
            'estado' => EstadoSolicitudExamen::EnProceso,
            'profesional_salud_id' => $profesional->id,
        ]);

        $respuesta = $this->getJson(route('api.hc-emergencia.examenes-laboratorio', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]));

        $respuesta
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'numero_atencion',
                'examenes' => [[
                    'id',
                    'examen_id',
                    'descripcion',
                    'id_sistema_externo',
                    'profesional_salud_id',
                    'nombres',
                    'codigo_servicio',
                    'prioridad',
                ]],
            ])
            ->assertJsonCount(2, 'examenes')
            ->assertJsonPath('id', $atencion->id)
            ->assertJsonPath('numero_atencion', 100234)
            ->assertJsonPath('examenes.0.id', fn ($id): bool => is_int($id) && $id > 0)
            ->assertJsonPath('examenes.0.descripcion', 'Hemograma completo')
            ->assertJsonPath('examenes.0.id_sistema_externo', 501)
            ->assertJsonPath('examenes.0.nombres', 'Jhon Montoya Nakanichi')
            ->assertJsonPath('examenes.0.codigo_servicio', 'MED-001')
            ->assertJsonPath('examenes.0.prioridad', 'Emergencia');

        $respuesta->assertJsonMissing([
            'examen_id' => $radiografia->id,
            'descripcion' => 'Radiografia de torax',
        ]);
    }

    public function test_responde_atencion_con_lista_vacia_si_no_hay_examenes_solicitados(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100235,
        ]);
        $examen = Examen::query()->create([
            'descripcion' => 'Perfil lipidico',
            'id_sistema_externo' => 504,
        ]);

        SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $examen->id,
            'estado' => EstadoSolicitudExamen::Terminado,
        ]);

        $this->getJson(route('api.hc-emergencia.examenes-laboratorio', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]))
            ->assertOk()
            ->assertJson([
                'id' => $atencion->id,
                'numero_atencion' => 100235,
                'examenes' => [],
            ]);
    }

    public function test_responde_error_cuando_no_existe_el_numero_de_atencion(): void
    {
        $this->getJson(route('api.hc-emergencia.examenes-laboratorio', [
            'numeroAtencion' => 999999,
        ]))
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe la atención con el número de atención indicado.',
            ]);
    }

    public function test_devuelve_todos_los_diagnosticos_de_ingreso_de_la_atencion(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100236,
        ]);
        $hipertension = DiagnosticoCie10::query()->create([
            'codigo' => 'I10',
            'nombre' => 'Hipertensión esencial primaria',
        ]);
        $cefalea = DiagnosticoCie10::query()->create([
            'codigo' => 'R51',
            'nombre' => 'Cefalea',
        ]);

        IngresoDiagnostico::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $hipertension->id,
            'tipo_diagnostico' => 'D',
        ]);
        IngresoDiagnostico::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $cefalea->id,
            'tipo_diagnostico' => 'P',
        ]);

        $respuesta = $this->getJson(route('api.hc-emergencia.diagnosticos-ingreso', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]));

        $respuesta
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'numero_atencion',
                'diagnosticos' => [[
                    'diagnostico_cie10_id',
                    'codigo',
                    'nombre',
                ]],
            ])
            ->assertJsonCount(2, 'diagnosticos')
            ->assertJsonPath('id', $atencion->id)
            ->assertJsonPath('numero_atencion', 100236)
            ->assertJsonPath('diagnosticos.0.diagnostico_cie10_id', $hipertension->id)
            ->assertJsonPath('diagnosticos.0.codigo', 'I10')
            ->assertJsonPath('diagnosticos.0.nombre', 'Hipertensión esencial primaria')
            ->assertJsonPath('diagnosticos.1.diagnostico_cie10_id', $cefalea->id)
            ->assertJsonPath('diagnosticos.1.codigo', 'R51')
            ->assertJsonPath('diagnosticos.1.nombre', 'Cefalea');
    }

    public function test_responde_atencion_con_lista_vacia_si_no_hay_diagnosticos_de_ingreso(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100237,
        ]);

        $this->getJson(route('api.hc-emergencia.diagnosticos-ingreso', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]))
            ->assertOk()
            ->assertJson([
                'id' => $atencion->id,
                'numero_atencion' => 100237,
                'diagnosticos' => [],
            ]);
    }

    public function test_responde_error_cuando_no_existe_el_numero_de_atencion_para_diagnosticos(): void
    {
        $this->getJson(route('api.hc-emergencia.diagnosticos-ingreso', [
            'numeroAtencion' => 999998,
        ]))
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe la atención con el número de atención indicado.',
            ]);
    }

    public function test_devuelve_todos_los_medicamentos_solicitados_de_la_atencion(): void
    {
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Jhon Montoya Nakanichi',
            'codigo_servicio' => 'MED-001',
        ]);
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100239,
        ]);
        $paracetamol = Medicamento::query()->create([
            'descripcion' => 'Paracetamol',
            'presentacion' => 'Tableta 500mg',
            'id_producto_externo' => 701,
            'estado' => 'Activo',
        ]);

        Prescripcion::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'medicamento_id' => $paracetamol->id,
            'medicamento' => 'Paracetamol',
            'cantidad' => 10,
            'dosis' => '1 tableta',
            'duracion' => '3 dias',
            'via' => 'Oral',
            'estado' => EstadoSolicitudExamen::Solicitado,
            'profesional_salud_id' => $profesional->id,
        ]);
        Prescripcion::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'medicamento_id' => null,
            'medicamento' => 'Medicamento no catalogado',
            'cantidad' => 2,
            'dosis' => '5 ml',
            'duracion' => '1 dia',
            'via' => 'EV',
            'estado' => EstadoSolicitudExamen::Solicitado,
            'profesional_salud_id' => $profesional->id,
        ]);
        Prescripcion::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'medicamento_id' => $paracetamol->id,
            'medicamento' => 'Paracetamol terminado',
            'cantidad' => 1,
            'dosis' => '1 tableta',
            'duracion' => '1 dia',
            'via' => 'Oral',
            'estado' => EstadoSolicitudExamen::Terminado,
            'profesional_salud_id' => $profesional->id,
        ]);

        $respuesta = $this->getJson(route('api.hc-emergencia.prescripciones-medicamentos', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]));

        $respuesta
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'numero_atencion',
                'medicamentos' => [[
                    'id',
                    'medicamento_id',
                    'medicamento',
                    'cantidad',
                    'dosis',
                    'duracion',
                    'via',
                    'id_producto_externo',
                    'profesional_salud_id',
                    'nombres',
                    'codigo_servicio',
                ]],
            ])
            ->assertJsonCount(2, 'medicamentos')
            ->assertJsonPath('id', $atencion->id)
            ->assertJsonPath('numero_atencion', 100239)
            ->assertJsonPath('medicamentos.0.medicamento_id', $paracetamol->id)
            ->assertJsonPath('medicamentos.0.medicamento', 'Paracetamol')
            ->assertJsonPath('medicamentos.0.cantidad', 10)
            ->assertJsonPath('medicamentos.0.dosis', '1 tableta')
            ->assertJsonPath('medicamentos.0.duracion', '3 dias')
            ->assertJsonPath('medicamentos.0.via', 'Oral')
            ->assertJsonPath('medicamentos.0.id_producto_externo', 701)
            ->assertJsonPath('medicamentos.0.profesional_salud_id', $profesional->id)
            ->assertJsonPath('medicamentos.0.nombres', 'Jhon Montoya Nakanichi')
            ->assertJsonPath('medicamentos.0.codigo_servicio', 'MED-001')
            ->assertJsonPath('medicamentos.1.medicamento_id', null)
            ->assertJsonPath('medicamentos.1.medicamento', 'Medicamento no catalogado')
            ->assertJsonPath('medicamentos.1.id_producto_externo', null);

        $respuesta->assertJsonMissing([
            'medicamento' => 'Paracetamol terminado',
        ]);
    }

    public function test_responde_atencion_con_lista_vacia_si_no_hay_medicamentos_solicitados(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100240,
        ]);

        Prescripcion::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::Terminado,
        ]);

        $this->getJson(route('api.hc-emergencia.prescripciones-medicamentos', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]))
            ->assertOk()
            ->assertJson([
                'id' => $atencion->id,
                'numero_atencion' => 100240,
                'medicamentos' => [],
            ]);
    }

    public function test_responde_error_cuando_no_existe_el_numero_de_atencion_para_medicamentos(): void
    {
        $this->getJson(route('api.hc-emergencia.prescripciones-medicamentos', [
            'numeroAtencion' => 999997,
        ]))
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe la atención con el número de atención indicado.',
            ]);
    }

    public function test_devuelve_todas_las_ordenes_solicitadas_de_la_atencion(): void
    {
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Roel Carreño Cisneros',
            'codigo_servicio' => 'RAD-001',
        ]);
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100242,
        ]);
        $ecografia = Procedimiento::factory()->create([
            'codigo' => 'ECO-001',
            'denominacion' => 'Ecografía abdominal enfocada',
        ]);
        $tomografia = Procedimiento::factory()->create([
            'codigo' => 'TAC-001',
            'denominacion' => 'Tomografía cerebral',
        ]);
        $radiografia = Procedimiento::factory()->create([
            'codigo' => 'RX-001',
            'denominacion' => 'Radiografía de tórax',
        ]);

        Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'procedimiento_id' => $ecografia->id,
            'detalle' => 'Ecografía abdominal enfocada',
            'fecha_hora_solicitud' => '2026-09-23 08:30:00',
            'estado' => EstadoSolicitudExamen::Solicitado,
            'profesional_salud_id' => $profesional->id,
        ]);
        Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'procedimiento_id' => $tomografia->id,
            'detalle' => 'Tomografía cerebral',
            'fecha_hora_solicitud' => '2026-09-23 09:10:00',
            'estado' => EstadoSolicitudExamen::Solicitado,
            'profesional_salud_id' => $profesional->id,
        ]);
        Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'procedimiento_id' => $radiografia->id,
            'detalle' => 'Radiografía de tórax',
            'estado' => EstadoSolicitudExamen::Terminado,
            'profesional_salud_id' => $profesional->id,
        ]);

        $respuesta = $this->getJson(route('api.hc-emergencia.ordenes', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]));

        $respuesta
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'numero_atencion',
                'ordenes' => [[
                    'id',
                    'procedimiento_id',
                    'codigo',
                    'detalle',
                    'fecha_hora_solicitud',
                    'profesional_salud_id',
                    'nombres',
                    'codigo_servicio',
                ]],
            ])
            ->assertJsonCount(2, 'ordenes')
            ->assertJsonPath('id', $atencion->id)
            ->assertJsonPath('numero_atencion', 100242)
            ->assertJsonPath('ordenes.0.procedimiento_id', $ecografia->id)
            ->assertJsonPath('ordenes.0.codigo', 'ECO-001')
            ->assertJsonPath('ordenes.0.detalle', 'Ecografía abdominal enfocada')
            ->assertJsonPath('ordenes.0.fecha_hora_solicitud', '2026-09-23 08:30:00')
            ->assertJsonPath('ordenes.0.profesional_salud_id', $profesional->id)
            ->assertJsonPath('ordenes.0.nombres', 'Roel Carreño Cisneros')
            ->assertJsonPath('ordenes.0.codigo_servicio', 'RAD-001')
            ->assertJsonPath('ordenes.1.procedimiento_id', $tomografia->id)
            ->assertJsonPath('ordenes.1.codigo', 'TAC-001');

        $respuesta->assertJsonMissing([
            'procedimiento_id' => $radiografia->id,
            'detalle' => 'Radiografía de tórax',
        ]);
    }

    public function test_responde_atencion_con_lista_vacia_si_no_hay_ordenes_solicitadas(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 100243,
        ]);

        Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::Terminado,
        ]);

        $this->getJson(route('api.hc-emergencia.ordenes', [
            'numeroAtencion' => $atencion->numero_atencion,
        ]))
            ->assertOk()
            ->assertJson([
                'id' => $atencion->id,
                'numero_atencion' => 100243,
                'ordenes' => [],
            ]);
    }

    public function test_responde_error_cuando_no_existe_el_numero_de_atencion_para_ordenes(): void
    {
        $this->getJson(route('api.hc-emergencia.ordenes', [
            'numeroAtencion' => 999996,
        ]))
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe la atención con el número de atención indicado.',
            ]);
    }

    public function test_actualiza_estado_de_orden_y_devuelve_procedimiento_id(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
            'numero_atencion' => 100244,
        ]);
        $procedimiento = Procedimiento::factory()->create([
            'codigo' => 'RX-010',
            'denominacion' => 'Radiografía de tórax',
        ]);
        $orden = Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'procedimiento_id' => $procedimiento->id,
            'detalle' => 'Radiografía de tórax',
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.ordenes.estado', ['id' => $orden->id]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Estado de la orden actualizado correctamente.',
                'data' => [
                    'id' => $orden->id,
                    'procedimiento_id' => $procedimiento->id,
                    'hc_emergencia_id' => $atencion->id,
                    'numero_atencion' => 100244,
                    'estado' => EstadoSolicitudExamen::EnProceso->value,
                ],
            ]);

        $this->assertDatabaseHas('ordenes', [
            'id' => $orden->id,
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ]);
    }

    public function test_actualiza_orden_a_terminado(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);
        $orden = Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::EnProceso,
        ]);

        $this->patchJson(route('api.ordenes.estado', ['id' => $orden->id]), [
            'estado' => EstadoSolicitudExamen::Terminado->value,
        ])->assertOk();

        $this->assertDatabaseHas('ordenes', [
            'id' => $orden->id,
            'estado' => EstadoSolicitudExamen::Terminado->value,
        ]);
    }

    public function test_actualiza_orden_a_pagado(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);
        $orden = Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::EnProceso,
        ]);

        $this->patchJson(route('api.ordenes.estado', ['id' => $orden->id]), [
            'estado' => EstadoSolicitudExamen::Pagado->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Estado de la orden actualizado correctamente.',
                'data' => [
                    'id' => $orden->id,
                    'estado' => EstadoSolicitudExamen::Pagado->value,
                ],
            ]);

        $this->assertDatabaseHas('ordenes', [
            'id' => $orden->id,
            'estado' => EstadoSolicitudExamen::Pagado->value,
        ]);
    }

    public function test_no_actualiza_orden_si_atencion_esta_cerrada(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Cerrada,
        ]);
        $orden = Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.ordenes.estado', ['id' => $orden->id]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertStatus(409)
            ->assertJson([
                'message' => "La atención existe pero está 'Cerrada'",
            ]);

        $this->assertDatabaseHas('ordenes', [
            'id' => $orden->id,
            'estado' => EstadoSolicitudExamen::Solicitado->value,
        ]);
    }

    public function test_rechaza_estado_no_permitido_para_orden(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);
        $orden = Orden::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.ordenes.estado', ['id' => $orden->id]), [
            'estado' => EstadoSolicitudExamen::Solicitado->value,
        ])->assertStatus(422);

        $this->patchJson(route('api.ordenes.estado', ['id' => $orden->id]), [
            'estado' => 'Cancelado',
        ])->assertStatus(422);
    }

    public function test_responde_error_si_no_existe_orden(): void
    {
        $this->patchJson(route('api.ordenes.estado', ['id' => 999999]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe la orden indicada.',
            ]);
    }

    public function test_actualiza_estado_de_medicamento_prescrito_y_devuelve_medicamento_id(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
            'numero_atencion' => 100241,
        ]);
        $medicamento = Medicamento::query()->create([
            'descripcion' => 'Ibuprofeno',
            'presentacion' => 'Tableta 400mg',
            'id_producto_externo' => 801,
            'estado' => 'Activo',
        ]);
        $prescripcion = Prescripcion::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'medicamento_id' => $medicamento->id,
            'medicamento' => 'Ibuprofeno',
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.prescripciones.estado', ['id' => $prescripcion->id]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Estado del medicamento actualizado correctamente.',
                'data' => [
                    'id' => $prescripcion->id,
                    'medicamento_id' => $medicamento->id,
                    'hc_emergencia_id' => $atencion->id,
                    'numero_atencion' => 100241,
                    'estado' => EstadoSolicitudExamen::EnProceso->value,
                ],
            ]);

        $this->assertDatabaseHas('prescripciones', [
            'id' => $prescripcion->id,
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ]);
    }

    public function test_actualiza_medicamento_prescrito_a_terminado(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);
        $prescripcion = Prescripcion::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::EnProceso,
        ]);

        $this->patchJson(route('api.prescripciones.estado', ['id' => $prescripcion->id]), [
            'estado' => EstadoSolicitudExamen::Terminado->value,
        ])->assertOk();

        $this->assertDatabaseHas('prescripciones', [
            'id' => $prescripcion->id,
            'estado' => EstadoSolicitudExamen::Terminado->value,
        ]);
    }

    public function test_no_actualiza_medicamento_prescrito_si_atencion_esta_cerrada(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Cerrada,
        ]);
        $prescripcion = Prescripcion::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.prescripciones.estado', ['id' => $prescripcion->id]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertStatus(409)
            ->assertJson([
                'message' => "La atención existe pero está 'Cerrada'",
            ]);

        $this->assertDatabaseHas('prescripciones', [
            'id' => $prescripcion->id,
            'estado' => EstadoSolicitudExamen::Solicitado->value,
        ]);
    }

    public function test_rechaza_estado_no_permitido_para_medicamento_prescrito(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);
        $prescripcion = Prescripcion::factory()->create([
            'hc_emergencia_id' => $atencion->id,
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.prescripciones.estado', ['id' => $prescripcion->id]), [
            'estado' => EstadoSolicitudExamen::Solicitado->value,
        ])->assertStatus(422);

        $this->patchJson(route('api.prescripciones.estado', ['id' => $prescripcion->id]), [
            'estado' => 'Cancelado',
        ])->assertStatus(422);
    }

    public function test_responde_error_si_no_existe_prescripcion(): void
    {
        $this->patchJson(route('api.prescripciones.estado', ['id' => 999999]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe la prescripción indicada.',
            ]);
    }

    public function test_actualiza_estado_de_examen_y_devuelve_examen_id(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
            'numero_atencion' => 100238,
        ]);
        $examen = Examen::query()->create([
            'descripcion' => 'Hemograma para actualizar',
            'id_sistema_externo' => 601,
        ]);
        $solicitud = SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $examen->id,
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.solicitud-examenes.estado', ['id' => $solicitud->id]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Estado del examen actualizado correctamente.',
                'data' => [
                    'id' => $solicitud->id,
                    'examen_id' => $examen->id,
                    'hc_emergencia_id' => $atencion->id,
                    'numero_atencion' => 100238,
                    'estado' => EstadoSolicitudExamen::EnProceso->value,
                ],
            ]);

        $this->assertDatabaseHas('solicitud_examenes', [
            'id' => $solicitud->id,
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ]);
    }

    public function test_actualiza_examen_a_terminado(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);
        $examen = Examen::query()->create([
            'descripcion' => 'Perfil lipidico para actualizar',
            'id_sistema_externo' => 602,
        ]);
        $solicitud = SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $examen->id,
            'estado' => EstadoSolicitudExamen::EnProceso,
        ]);

        $this->patchJson(route('api.solicitud-examenes.estado', ['id' => $solicitud->id]), [
            'estado' => EstadoSolicitudExamen::Terminado->value,
        ])->assertOk();

        $this->assertDatabaseHas('solicitud_examenes', [
            'id' => $solicitud->id,
            'estado' => EstadoSolicitudExamen::Terminado->value,
        ]);
    }

    public function test_no_actualiza_examen_si_atencion_esta_cerrada(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Cerrada,
        ]);
        $examen = Examen::query()->create([
            'descripcion' => 'Examen de atención cerrada',
            'id_sistema_externo' => 603,
        ]);
        $solicitud = SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $examen->id,
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.solicitud-examenes.estado', ['id' => $solicitud->id]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertStatus(409)
            ->assertJson([
                'message' => "La atención existe pero está 'Cerrada'",
            ]);

        $this->assertDatabaseHas('solicitud_examenes', [
            'id' => $solicitud->id,
            'estado' => EstadoSolicitudExamen::Solicitado->value,
        ]);
    }

    public function test_rechaza_estado_no_permitido_para_examen(): void
    {
        $atencion = HcEmergencia::factory()->create([
            'estado_atencion' => EstadoAtencion::Abierta,
        ]);
        $examen = Examen::query()->create([
            'descripcion' => 'Examen con estado inválido',
            'id_sistema_externo' => 604,
        ]);
        $solicitud = SolicitudExamen::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $examen->id,
            'estado' => EstadoSolicitudExamen::Solicitado,
        ]);

        $this->patchJson(route('api.solicitud-examenes.estado', ['id' => $solicitud->id]), [
            'estado' => 'Cancelado',
        ])->assertStatus(422);
    }

    public function test_responde_error_si_no_existe_solicitud_de_examen(): void
    {
        $this->patchJson(route('api.solicitud-examenes.estado', ['id' => 999999]), [
            'estado' => EstadoSolicitudExamen::EnProceso->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe el examen solicitado.',
            ]);
    }

    public function test_actualiza_examen_por_id_sistema_externo(): void
    {
        $examen = Examen::query()->create([
            'descripcion' => 'Hemograma',
            'id_sistema_externo' => 901,
            'estado' => EstadoCatalogo::Activo->value,
        ]);

        $this->postJson(route('api.examenes.actualizar-por-sistema-externo'), [
            'id_sistema_externo' => 901,
            'descripcion' => 'Hemograma completo actualizado',
            'estado' => EstadoCatalogo::Inactivo->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Examen actualizado correctamente.',
                'data' => [
                    'id' => $examen->id,
                    'id_sistema_externo' => 901,
                    'descripcion' => 'Hemograma completo actualizado',
                    'estado' => EstadoCatalogo::Inactivo->value,
                ],
            ]);

        $this->assertDatabaseHas('examenes', [
            'id' => $examen->id,
            'id_sistema_externo' => 901,
            'descripcion' => 'Hemograma completo actualizado',
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);
    }

    public function test_responde_error_si_examen_externo_no_existe(): void
    {
        $this->postJson(route('api.examenes.actualizar-por-sistema-externo'), [
            'id_sistema_externo' => 999999,
            'descripcion' => 'Examen inexistente',
            'estado' => EstadoCatalogo::Activo->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe examen con el id_sistema_externo indicado.',
            ]);
    }

    public function test_valida_datos_para_actualizar_examen_externo(): void
    {
        $this->postJson(route('api.examenes.actualizar-por-sistema-externo'), [
            'id_sistema_externo' => '',
            'descripcion' => '',
            'estado' => 'Suspendido',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_sistema_externo', 'descripcion', 'estado']);
    }

    public function test_actualiza_medicamento_por_id_producto_externo(): void
    {
        $medicamento = Medicamento::query()->create([
            'descripcion' => 'Paracetamol',
            'presentacion' => 'Tableta 500 mg',
            'generico' => 'Paracetamol',
            'stock' => 25,
            'id_producto_externo' => 7001,
            'estado' => EstadoCatalogo::Activo->value,
        ]);

        $this->postJson(route('api.medicamentos.actualizar-por-producto-externo'), [
            'id_producto_externo' => 7001,
            'descripcion' => 'Paracetamol actualizado',
            'presentacion' => 'Tableta 1 g',
            'generico' => 'Acetaminofen',
            'stock' => 120,
            'estado' => EstadoCatalogo::Inactivo->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Medicamento actualizado correctamente.',
                'data' => [
                    'id' => $medicamento->id,
                    'id_producto_externo' => 7001,
                    'descripcion' => 'Paracetamol actualizado',
                    'presentacion' => 'Tableta 1 g',
                    'generico' => 'Acetaminofen',
                    'stock' => 120,
                    'estado' => EstadoCatalogo::Inactivo->value,
                ],
            ]);

        $this->assertDatabaseHas('medicamentos', [
            'id' => $medicamento->id,
            'id_producto_externo' => 7001,
            'descripcion' => 'Paracetamol actualizado',
            'presentacion' => 'Tableta 1 g',
            'generico' => 'Acetaminofen',
            'stock' => 120,
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);
    }

    public function test_responde_error_si_medicamento_externo_no_existe(): void
    {
        $this->postJson(route('api.medicamentos.actualizar-por-producto-externo'), [
            'id_producto_externo' => 999999,
            'descripcion' => 'Medicamento inexistente',
            'presentacion' => 'Tableta',
            'generico' => 'Generico',
            'stock' => 10,
            'estado' => EstadoCatalogo::Activo->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe medicamento con el id_producto_externo indicado.',
            ]);
    }

    public function test_valida_datos_para_actualizar_medicamento_externo(): void
    {
        $this->postJson(route('api.medicamentos.actualizar-por-producto-externo'), [
            'id_producto_externo' => '',
            'descripcion' => '',
            'presentacion' => str_repeat('A', 151),
            'stock' => -1,
            'estado' => 'Suspendido',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'id_producto_externo',
                'descripcion',
                'presentacion',
                'stock',
                'estado',
            ]);
    }

    public function test_actualiza_procedimiento_por_codigo(): void
    {
        $procedimiento = Procedimiento::query()->create([
            'codigo' => 'PROC-001',
            'denominacion' => 'Radiografia de torax',
            'estado' => EstadoCatalogo::Activo->value,
        ]);

        $this->postJson(route('api.procedimientos.actualizar-por-codigo'), [
            'codigo' => 'PROC-001',
            'denominacion' => 'Radiografia de torax actualizada',
            'estado' => EstadoCatalogo::Inactivo->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Procedimiento actualizado correctamente.',
                'data' => [
                    'id' => $procedimiento->id,
                    'codigo' => 'PROC-001',
                    'denominacion' => 'Radiografia de torax actualizada',
                    'estado' => EstadoCatalogo::Inactivo->value,
                ],
            ]);

        $this->assertDatabaseHas('procedimientos', [
            'id' => $procedimiento->id,
            'codigo' => 'PROC-001',
            'denominacion' => 'Radiografia de torax actualizada',
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);
    }

    public function test_responde_error_si_procedimiento_no_existe(): void
    {
        $this->postJson(route('api.procedimientos.actualizar-por-codigo'), [
            'codigo' => 'PROC-999',
            'denominacion' => 'Procedimiento inexistente',
            'estado' => EstadoCatalogo::Activo->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe procedimiento con el codigo indicado.',
            ]);
    }

    public function test_valida_datos_para_actualizar_procedimiento(): void
    {
        $this->postJson(route('api.procedimientos.actualizar-por-codigo'), [
            'codigo' => '',
            'denominacion' => '',
            'estado' => 'Suspendido',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo', 'denominacion', 'estado']);
    }

    public function test_actualiza_profesional_salud_por_codigo_servicio(): void
    {
        $profesional = ProfesionalSalud::factory()->create([
            'codigo_servicio' => 'MED-777',
            'nombres' => 'Profesional original',
            'tipo_colegiatura' => 'CMP',
            'cmp' => '123456',
            'estado' => EstadoCatalogo::Activo->value,
        ]);

        $this->postJson(route('api.profesionales-salud.actualizar-por-codigo-servicio'), [
            'codigo_servicio' => 'MED-777',
            'nombres' => 'Profesional actualizado',
            'tipo_colegiatura' => 'CEP',
            'cmp' => '654321',
            'estado' => EstadoCatalogo::Inactivo->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Profesional de salud actualizado correctamente.',
                'data' => [
                    'id' => $profesional->id,
                    'codigo_servicio' => 'MED-777',
                    'nombres' => 'Profesional actualizado',
                    'tipo_colegiatura' => 'CEP',
                    'cmp' => '654321',
                    'estado' => EstadoCatalogo::Inactivo->value,
                ],
            ]);

        $this->assertDatabaseHas('profesionales_salud', [
            'id' => $profesional->id,
            'codigo_servicio' => 'MED-777',
            'nombres' => 'Profesional actualizado',
            'tipo_colegiatura' => 'CEP',
            'cmp' => '654321',
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);
    }

    public function test_responde_error_si_profesional_salud_no_existe(): void
    {
        $this->postJson(route('api.profesionales-salud.actualizar-por-codigo-servicio'), [
            'codigo_servicio' => 'MED-999',
            'nombres' => 'Profesional inexistente',
            'tipo_colegiatura' => 'CMP',
            'cmp' => '999999',
            'estado' => EstadoCatalogo::Activo->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe profesional de salud con el codigo_servicio indicado.',
            ]);
    }

    public function test_valida_datos_para_actualizar_profesional_salud(): void
    {
        $this->postJson(route('api.profesionales-salud.actualizar-por-codigo-servicio'), [
            'codigo_servicio' => '',
            'nombres' => '',
            'tipo_colegiatura' => 'Colegio no valido',
            'estado' => 'Suspendido',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo_servicio', 'nombres', 'tipo_colegiatura', 'estado']);
    }

    public function test_actualiza_diagnostico_cie10_por_codigo(): void
    {
        $diagnostico = DiagnosticoCie10::query()->create([
            'codigo' => 'A00',
            'nombre' => 'Colera',
            'genero' => GeneroCie10::Ambos->value,
            'estado' => EstadoCatalogo::Activo->value,
        ]);

        $this->postJson(route('api.diagnosticos-cie10.actualizar-por-codigo'), [
            'codigo' => 'A00',
            'nombre' => 'Colera actualizado',
            'genero' => GeneroCie10::Masculino->value,
            'estado' => EstadoCatalogo::Inactivo->value,
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Diagnóstico CIE10 actualizado correctamente.',
                'data' => [
                    'id' => $diagnostico->id,
                    'codigo' => 'A00',
                    'nombre' => 'Colera actualizado',
                    'genero' => GeneroCie10::Masculino->value,
                    'estado' => EstadoCatalogo::Inactivo->value,
                ],
            ]);

        $this->assertDatabaseHas('diagnosticos_cie10', [
            'id' => $diagnostico->id,
            'codigo' => 'A00',
            'nombre' => 'Colera actualizado',
            'genero' => GeneroCie10::Masculino->value,
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);
    }

    public function test_responde_error_si_diagnostico_cie10_no_existe(): void
    {
        $this->postJson(route('api.diagnosticos-cie10.actualizar-por-codigo'), [
            'codigo' => 'Z99',
            'nombre' => 'Diagnóstico inexistente',
            'genero' => GeneroCie10::Ambos->value,
            'estado' => EstadoCatalogo::Activo->value,
        ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'No existe diagnóstico CIE10 con el codigo indicado.',
            ]);
    }

    public function test_valida_datos_para_actualizar_diagnostico_cie10(): void
    {
        $this->postJson(route('api.diagnosticos-cie10.actualizar-por-codigo'), [
            'codigo' => '',
            'nombre' => '',
            'genero' => 'No aplica',
            'estado' => 'Suspendido',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo', 'nombre', 'genero', 'estado']);
    }

    public function test_api_publica_genera_pdf_de_historia_clinica(): void
    {
        $atencion = HcEmergencia::factory()->create();

        $respuesta = $this->get(route('api.hc-emergencia.historia-clinica.pdf', $atencion));

        $respuesta->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $respuesta->headers->get('content-type'));
        $this->assertStringContainsString(
            'historia-clinica-emergencia-'.$atencion->id.'.pdf',
            (string) $respuesta->headers->get('content-disposition')
        );
    }

    public function test_api_publica_responde_404_si_no_existe_historia_clinica_para_pdf(): void
    {
        $this->get(route('api.hc-emergencia.historia-clinica.pdf', ['atencion' => 999999]))
            ->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadBase(int $hceId): array
    {
        return [
            'hce_id' => $hceId,
            'numero_atencion' => 100234,
            'paciente' => [
                'nombres' => 'Pedro Angeles',
                'direccion' => 'Av. Santa Rosa 123',
                'telefono' => '999999999',
                'sexo' => 'Masculino',
                'fecha_nacimiento' => '1985-05-10',
                'tipo_documento' => 'DNI',
                'numero_documento' => '12345678',
                'id_paciente_externo' => 789875,
            ],
            'profesional' => [
                'nombres' => 'Jhon Montoya Nakanichi',
                'tipo_colegiatura' => 'CMP',
                'cmp' => '023440',
                'codigo_servicio' => 'MED-001',
            ],
        ];
    }
}
