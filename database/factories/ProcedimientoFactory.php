<?php

namespace Database\Factories;

use App\Models\Procedimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Procedimiento>
 */
class ProcedimientoFactory extends Factory
{
    protected $model = Procedimiento::class;

    public function definition(): array
    {
        return [
            'codigo' => 'PROC-'.$this->faker->unique()->numerify('#####'),
            'denominacion' => $this->faker->sentence(3),
            'estado' => 'Activo',
        ];
    }
}
