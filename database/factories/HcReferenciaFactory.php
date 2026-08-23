<?php

namespace Database\Factories;

use App\Models\HcEmergencia;
use App\Models\HcReferencia;
use App\Models\Ups;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HcReferencia>
 */
class HcReferenciaFactory extends Factory
{
    protected $model = HcReferencia::class;

    /**
     * Define una referencia clinica ficticia para pruebas automatizadas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hc_emergencia_id' => HcEmergencia::factory(),
            'ipress_origen' => fake()->company(),
            'ups_origen_id' => Ups::factory(),
            'ipress_referencia' => fake()->company(),
            'ups_destino_id' => Ups::factory(),
            'motivo_referencia' => fake()->sentence(10),
            'fecha_hora_solicitud' => now(),
            'respuesta_solicitud' => fake()->optional()->sentence(8),
            'fecha_hora_respuesta' => fake()->optional()->dateTimeBetween('-1 day', '+1 day'),
        ];
    }
}
