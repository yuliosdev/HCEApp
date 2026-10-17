<?php

namespace Database\Factories;

use App\Models\Especialidad;
use App\Models\ProfesionalSalud;
use App\Support\GestionClinica\RegistrosClinicos;
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
            'especialidad_id' => Especialidad::query()->value('id') ?? Especialidad::query()->create([
                'nombre' => 'Medicina de emergencias',
                'descripcion' => 'Atencion medica inicial de emergencias',
            ])->id,
            'tipo_colegiatura' => fake()->randomElement(RegistrosClinicos::tiposColegiatura()),
            'cmp' => fake()->unique()->numerify('######'),
            'rne' => fake()->optional()->numerify('#####'),
            'estado' => 'Activo',
            'codigo_servicio' => fake()->optional()->numerify('######'),
        ];
    }
}
