<?php

namespace Tests\Feature;

use App\Enums\ColorTriaje;
use App\Enums\EstadoAtencion;
use App\Enums\FactorRh;
use App\Enums\GrupoSanguineo;
use App\Enums\SiNo;
use App\Enums\TopicoAtencion;
use App\Models\HcEmergencia;
use App\Models\HcReferencia;
use App\Models\HospitalizacionPrevia;
use App\Models\MedicamentoHabitual;
use App\Models\Paciente;
use App\Models\Prioridad;
use App\Models\Ups;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'ups',
            'glasgow',
            'pacientes',
            'profesionales_salud',
            'hc_emergencia',
            'hc_referencias',
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
        $this->assertTrue(Schema::hasColumn('hc_emergencia', 'alias'), 'La tabla hc_emergencia debe tener el campo alias.');
        $this->assertTrue(Schema::hasColumn('hc_emergencia', 'indicaciones_finales'), 'La tabla hc_emergencia debe tener indicaciones finales.');
        $this->assertTrue(Schema::hasColumn('hc_emergencia', 'motivo_consulta'), 'La tabla hc_emergencia debe tener motivo_consulta.');
        $this->assertTrue(Schema::hasColumn('hospitalizaciones_previas', 'ups_id'), 'La tabla hospitalizaciones_previas debe relacionarse con ups.');
        $this->assertTrue(Schema::hasColumn('signos_vitales', 'rango_inicial'), 'La tabla signos_vitales debe tener rango inicial.');
        $this->assertTrue(Schema::hasColumn('signos_vitales', 'rango_final'), 'La tabla signos_vitales debe tener rango final.');
        $this->assertTrue(Schema::hasColumn('signos_vitales', 'favorito'), 'La tabla signos_vitales debe tener favorito.');
        $this->assertTrue(Schema::hasColumn('evoluciones', 'detalle_clinico'), 'La tabla evoluciones debe tener detalle clinico.');
        $this->assertTrue(Schema::hasColumn('alta_medicamentos', 'cantidad'), 'La tabla alta_medicamentos debe tener cantidad.');
        $this->assertFalse(Schema::hasColumn('medicamentos_habituales', 'hc_emergencia_id'), 'medicamentos_habituales ya no debe depender directamente de hc_emergencia.');
        $this->assertTrue(Schema::hasColumn('medicamentos_habituales', 'paciente_id'), 'medicamentos_habituales debe depender del paciente.');
        $this->assertTrue(Schema::hasColumn('users', 'tipo_usuario'), 'La tabla users debe tener el campo tipo_usuario.');
        $this->assertTrue(Schema::hasColumn('users', 'profesional_salud_id'), 'La tabla users debe relacionarse con profesionales_salud.');
        $this->assertTrue(
            $this->existeClaveForanea('users', 'profesional_salud_id', 'profesionales_salud', 'id'),
            'users.profesional_salud_id debe tener una clave foranea real hacia profesionales_salud.id.'
        );
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
            'tipo_usuario' => 'Supervisor',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'medico.seed@hceapp.test',
            'tipo_usuario' => 'Profesional de Salud',
        ]);

        $this->assertDatabaseCount('prioridades', 4);
        $this->assertDatabaseCount('ups', 392);
        $this->assertDatabaseCount('glasgow', 15);
        $this->assertDatabaseHas('prioridades', [
            'prioridad' => 'II.URGENCIA MAYOR',
            'color' => 'Naranja',
            'orden' => 2,
        ]);
        $this->assertDatabaseHas('ups', [
            'codigo' => '030000',
            'denominacion' => 'ANATOMO PATOLOGÍA / PATOLOGÍA',
            'estado' => true,
        ]);
        $this->assertDatabaseHas('glasgow', [
            'tipo' => 'Motora',
            'puntaje' => 6,
            'descripcion' => 'Obedece ordenes',
        ]);
        $this->assertDatabaseHas('signos_vitales', [
            'descripcion' => 'Temperatura',
            'favorito' => true,
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
            'alias' => 'Paciente no identificado',
            'motivo_consulta' => 'Dolor abdominal.',
            'indicaciones_finales' => 'Control por consultorio externo.',
        ]);

        $this->assertTrue($episodio->paciente->is($paciente));
        $this->assertTrue($episodio->prioridad->is($prioridad));
        $this->assertTrue($episodio->registradoPor->is($administrador));
        $this->assertSame(EstadoAtencion::Abierta, $episodio->estado_atencion);
        $this->assertSame(TopicoAtencion::Topico, $episodio->topico);
        $this->assertSame(SiNo::No, $episodio->sin_identificacion);
        $this->assertSame(ColorTriaje::Azul, $episodio->color_triaje);
        $this->assertTrue($episodio->triaje);
        $this->assertSame('Paciente no identificado', $episodio->alias);
        $this->assertSame('Dolor abdominal.', $episodio->motivo_consulta);
        $this->assertSame('Control por consultorio externo.', $episodio->indicaciones_finales);
        $this->assertInstanceOf(GrupoSanguineo::class, $paciente->grupo_sanguineo);
        $this->assertInstanceOf(FactorRh::class, $paciente->factor_rh);
    }

    /**
     * Verifica las relaciones agregadas en la reestructura de migraciones.
     */
    public function test_puede_usar_relaciones_nuevas_de_ups_referencias_y_medicamentos_habituales(): void
    {
        $this->seed();

        $administrador = User::query()->where('email', 'admin@hceapp.test')->firstOrFail();
        $paciente = Paciente::factory()->create([
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);
        $episodio = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);
        $ups = Ups::query()->where('codigo', '030000')->firstOrFail();

        $hospitalizacion = HospitalizacionPrevia::query()->create([
            'paciente_id' => $paciente->id,
            'ups_id' => $ups->id,
            'denominacion' => 'Hospitalizacion de prueba',
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        $medicamentoHabitual = MedicamentoHabitual::query()->create([
            'paciente_id' => $paciente->id,
            'medicacion' => 'Metformina',
            'periodo' => 'Habitual',
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        $referencia = HcReferencia::factory()->create([
            'hc_emergencia_id' => $episodio->id,
            'ups_origen_id' => $ups->id,
            'ups_destino_id' => $ups->id,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        $this->assertTrue($hospitalizacion->fresh()->ups->is($ups));
        $this->assertTrue($medicamentoHabitual->fresh()->paciente->is($paciente));
        $this->assertTrue($referencia->fresh()->hcEmergencia->is($episodio));
        $this->assertTrue($referencia->fresh()->upsOrigen->is($ups));
        $this->assertTrue($referencia->fresh()->upsDestino->is($ups));
        $this->assertTrue($episodio->fresh()->referencia->is($referencia));
        $this->assertTrue($paciente->fresh()->medicamentosHabituales->contains($medicamentoHabitual));
    }

    private function existeClaveForanea(string $tabla, string $columna, string $tablaReferenciada, string $columnaReferenciada): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            return DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
                ->where('TABLE_NAME', $tabla)
                ->where('COLUMN_NAME', $columna)
                ->where('REFERENCED_TABLE_NAME', $tablaReferenciada)
                ->where('REFERENCED_COLUMN_NAME', $columnaReferenciada)
                ->exists();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA foreign_key_list({$tabla})"))
                ->contains(fn (object $clave): bool => $clave->from === $columna
                    && $clave->table === $tablaReferenciada
                    && $clave->to === $columnaReferenciada);
        }

        return true;
    }
}
