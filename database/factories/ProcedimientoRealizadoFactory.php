<?php

namespace Database\Factories;

use App\Enums\EstadoSolicitudExamen;
use App\Models\HcEmergencia;
use App\Models\Procedimiento;
use App\Models\ProcedimientoRealizado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcedimientoRealizado>
 */
class ProcedimientoRealizadoFactory extends Factory
{
    protected $model = ProcedimientoRealizado::class;

    public function definition(): array
    {
        $procedimiento = Procedimiento::factory()->create();

        return [
            'hc_emergencia_id' => HcEmergencia::factory(),
            'procedimiento_id' => $procedimiento->id,
            'denominacion' => $procedimiento->denominacion,
            'sesiones' => $this->faker->numberBetween(1, 3),
            'fecha_hora' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'estado' => EstadoSolicitudExamen::Solicitado->value,
        ];
    }
}
