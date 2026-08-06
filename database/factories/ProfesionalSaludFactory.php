<?php

namespace Database\Factories;

use App\Models\ProfesionalSalud;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfesionalSalud>
 */
class ProfesionalSaludFactory extends Factory
{
    /**
     * Modelo asociado a esta factory.
     */
    protected $model = ProfesionalSalud::class;

    /**
     * Define un profesional de salud ficticio.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => fake()->name(),
            'especialidad' => fake()->randomElement(['Medicina de emergencias', 'Cirugia general', 'Enfermeria', 'Laboratorio']),
            'tipo_colegiatura' => fake()->randomElement(['CMP', 'CEP', 'Otro']),
            'cmp' => fake()->unique()->numerify('######'),
            'rne' => fake()->optional()->numerify('#####'),
            'estado' => 'Activo',
            'codigo_servicio' => fake()->optional()->numerify('######'),
        ];
    }
}
