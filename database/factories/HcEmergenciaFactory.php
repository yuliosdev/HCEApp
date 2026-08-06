<?php

namespace Database\Factories;

use App\Enums\ColorTriaje;
use App\Enums\EstadoAtencion;
use App\Enums\EstadoFirma;
use App\Enums\SiNo;
use App\Enums\TopicoAtencion;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use App\Models\Prioridad;
use App\Models\ProfesionalSalud;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HcEmergencia>
 */
class HcEmergenciaFactory extends Factory
{
    /**
     * Modelo asociado a esta factory.
     */
    protected $model = HcEmergencia::class;

    /**
     * Define un episodio de emergencia ficticio para pruebas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_atencion' => fake()->unique()->numberBetween(100000, 999999),
            'fecha_hora_inicio_episodio' => now()->subMinutes(30),
            'fecha_hora_atencion' => now(),
            'topico' => TopicoAtencion::Topico,
            'ambiente' => 'Cubiculo '.fake()->numberBetween(1, 10),
            'paciente_id' => Paciente::factory(),
            'sin_identificacion' => SiNo::No,
            'fecha_hora_recepcion' => now()->subMinutes(35),
            'fecha_hora_triaje' => now()->subMinutes(25),
            'profesional_triaje_id' => ProfesionalSalud::factory(),
            'prioridad_id' => Prioridad::query()->value('id'),
            'color_triaje' => fake()->randomElement(ColorTriaje::cases()),
            'estado_firma' => EstadoFirma::Pendiente,
            'estado_atencion' => EstadoAtencion::Abierta,
        ];
    }
}
