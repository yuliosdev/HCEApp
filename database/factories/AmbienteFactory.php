<?php

namespace Database\Factories;

use App\Models\Ambiente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ambiente>
 */
class AmbienteFactory extends Factory
{
    protected $model = Ambiente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'denominacion' => 'Ambiente '.fake()->unique()->numberBetween(1, 9999),
            'estado' => 'Activo',
        ];
    }
}
