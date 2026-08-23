<?php

namespace Database\Factories;

use App\Models\Ups;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ups>
 */
class UpsFactory extends Factory
{
    protected $model = Ups::class;

    /**
     * Define una UPS ficticia para pruebas automatizadas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->numerify('######'),
            'denominacion' => fake()->sentence(4),
            'estado' => true,
        ];
    }
}
