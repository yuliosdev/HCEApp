<?php

namespace Database\Factories;

use App\Models\SignoVital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignoVital>
 */
class SignoVitalFactory extends Factory
{
    protected $model = SignoVital::class;

    /**
     * Define un signo vital ficticio para pruebas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'descripcion' => fake()->unique()->words(2, true),
            'unidad' => fake()->randomElement(['lat/min', 'resp/min', 'mmHg', '°C', '%']),
            'rango_inicial' => fake()->randomFloat(2, 1, 80),
            'rango_final' => fake()->randomFloat(2, 81, 200),
            'favorito' => fake()->boolean(60),
            'estado' => 'Activo',
        ];
    }
}
