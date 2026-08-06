<?php

namespace Database\Factories;

use App\Enums\FactorRh;
use App\Enums\GrupoSanguineo;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
class PacienteFactory extends Factory
{
    /**
     * Modelo asociado a esta factory.
     */
    protected $model = Paciente::class;

    /**
     * Define un paciente ficticio sin informacion personal real.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'direccion' => fake()->streetAddress(),
            'sexo' => fake()->randomElement(['Masculino', 'Femenino']),
            'fecha_nacimiento' => fake()->dateTimeBetween('-90 years', '-1 year')->format('Y-m-d'),
            'tipo_documento' => 'DNI',
            'numero_documento' => fake()->unique()->numerify('########'),
            'grupo_sanguineo' => fake()->randomElement(GrupoSanguineo::cases()),
            'factor_rh' => fake()->randomElement(FactorRh::cases()),
        ];
    }
}
