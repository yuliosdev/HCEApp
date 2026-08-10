<?php

namespace Tests\Feature;

use App\Enums\ColorTriaje;
use App\Enums\EstadoAtencion;
use App\Enums\FactorRh;
use App\Enums\GrupoSanguineo;
use App\Enums\SiNo;
use App\Enums\TopicoAtencion;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use App\Models\Prioridad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BaseDatosClinicaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que las tablas principales de la HCE de emergencia existan.
     */
    public function test_tablas_clinicas_principales_existen(): void
    {
        $tablas = [
            'prioridades',
            'pacientes',
            'profesionales_salud',
            'hc_emergencia',
            'triaje_signos_vitales',
            'ingreso_diagnosticos',
            'prescripciones',
            'evoluciones',
            'egreso_diagnosticos',
        ];

        foreach ($tablas as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "La tabla {$tabla} debe existir.");
        }

        $this->assertTrue(Schema::hasColumn('hc_emergencia', 'triaje'), 'La tabla hc_emergencia debe tener el campo booleano triaje.');
    }

    /**
     * Verifica que los seeders carguen datos minimos para operar el sistema.
     */
    public function test_seeders_cargan_usuario_administrador_y_catalogos(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@hceapp.test',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'yuliostch@gmail.com',
        ]);

        $this->assertDatabaseCount('prioridades', 4);
        $this->assertDatabaseHas('prioridades', [
            'prioridad' => 'II.URGENCIA MAYOR',
            'color' => 'Naranja',
            'orden' => 2,
        ]);
        $this->assertDatabaseHas('signos_vitales', [
            'descripcion' => 'Temperatura',
        ]);
    }

    /**
     * Verifica relaciones basicas entre usuario, paciente y episodio.
     */
    public function test_puede_crear_un_episodio_de_emergencia_con_relaciones_basicas(): void
    {
        $this->seed();

        $administrador = User::query()->where('email', 'admin@hceapp.test')->firstOrFail();
        $prioridad = Prioridad::query()->where('prioridad', 'II.URGENCIA MAYOR')->firstOrFail();
        $paciente = Paciente::factory()->create([
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        $episodio = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'prioridad_id' => $prioridad->id,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
            'color_triaje' => ColorTriaje::Azul,
            'triaje' => true,
        ]);

        $this->assertTrue($episodio->paciente->is($paciente));
        $this->assertTrue($episodio->prioridad->is($prioridad));
        $this->assertTrue($episodio->registradoPor->is($administrador));
        $this->assertSame(EstadoAtencion::Abierta, $episodio->estado_atencion);
        $this->assertSame(TopicoAtencion::Topico, $episodio->topico);
        $this->assertSame(SiNo::No, $episodio->sin_identificacion);
        $this->assertSame(ColorTriaje::Azul, $episodio->color_triaje);
        $this->assertTrue($episodio->triaje);
        $this->assertInstanceOf(GrupoSanguineo::class, $paciente->grupo_sanguineo);
        $this->assertInstanceOf(FactorRh::class, $paciente->factor_rh);
    }
}
