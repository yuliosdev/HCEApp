<?php

namespace Database\Factories;

use App\Enums\EstadoSolicitudExamen;
use App\Models\HcEmergencia;
use App\Models\Medicamento;
use App\Models\Prescripcion;
use App\Models\ProfesionalSalud;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescripcion>
 */
class PrescripcionFactory extends Factory
{
    protected $model = Prescripcion::class;

    public function definition(): array
    {
        return [
            'hc_emergencia_id' => HcEmergencia::factory(),
            'medicamento_id' => null,
            'medicamento' => $this->faker->words(2, true),
            'presentacion' => 'Tableta 500mg',
            'dosis' => '1 tableta',
            'via' => 'Oral',
            'duracion' => '3 dias',
            'cantidad' => $this->faker->numberBetween(1, 10),
            'fecha_hora' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'profesional_salud_id' => ProfesionalSalud::factory(),
            'estado' => EstadoSolicitudExamen::Solicitado->value,
        ];
    }

    public function catalogada(): self
    {
        return $this->state(function (): array {
            $medicamento = Medicamento::query()->create([
                'descripcion' => $this->faker->words(2, true),
                'estado' => 'Activo',
            ]);

            return [
                'medicamento_id' => $medicamento->id,
                'medicamento' => $medicamento->descripcion,
            ];
        });
    }
}
