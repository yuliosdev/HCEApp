<?php

namespace Tests\Feature;

use App\Enums\EstadoAtencion;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use App\Models\ProfesionalSalud;
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
                'data' => ['hce_id', 'paciente_id', 'profesional_salud_id'],
            ]);

        $paciente = Paciente::query()->where('id_paciente_externo', 789875)->firstOrFail();
        $profesional = ProfesionalSalud::query()->where('codigo_servicio', 'MED-001')->firstOrFail();

        $this->assertSame('Pedro Angeles', $paciente->nombre);
        $this->assertSame('12345678', $paciente->numero_documento);
        $this->assertSame('Jhon Montoya Nakanichi', $profesional->nombres);
        $this->assertSame('023440', $profesional->cmp);

        $atencion->refresh();
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
        $this->assertSame($paciente->id, $atencion->paciente_id);
        $this->assertSame($profesional->id, $atencion->profesional_salud_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadBase(int $hceId): array
    {
        return [
            'hce_id' => $hceId,
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
