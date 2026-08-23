<?php

namespace Database\Factories;

use App\Models\Glasgow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Glasgow>
 */
class GlasgowFactory extends Factory
{
    protected $model = Glasgow::class;

    /**
     * Define un puntaje Glasgow ficticio para pruebas automatizadas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(['Ocular', 'Motora', 'Verbal']),
            'puntaje' => fake()->numberBetween(1, 6),
            'descripcion' => fake()->sentence(4),
        ];
    }
}
