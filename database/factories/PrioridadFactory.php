<?php

namespace Database\Factories;

use App\Enums\ColorTriaje;
use App\Enums\EstadoCatalogo;
use App\Models\Prioridad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prioridad>
 */
class PrioridadFactory extends Factory
{
    /**
     * Modelo asociado a esta factory.
     */
    protected $model = Prioridad::class;

    /**
     * Define una prioridad de triaje ficticia para pruebas automatizadas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prioridad' => 'Prioridad '.fake()->unique()->word(),
            'color' => fake()->randomElement(ColorTriaje::cases()),
            'orden' => fake()->unique()->numberBetween(10, 250),
            'tiempo_atencion' => fake()->numberBetween(0, 240),
            'estado' => EstadoCatalogo::Activo,
        ];
    }
}
