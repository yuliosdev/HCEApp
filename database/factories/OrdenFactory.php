<?php

namespace Database\Factories;

use App\Enums\EstadoSolicitudExamen;
use App\Enums\PrioridadOrden;
use App\Models\HcEmergencia;
use App\Models\Orden;
use App\Models\Procedimiento;
use App\Models\ProfesionalSalud;
use App\Models\TipoOrden;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Orden>
 */
class OrdenFactory extends Factory
{
    protected $model = Orden::class;

    public function definition(): array
    {
        $procedimiento = Procedimiento::factory()->create();

        return [
            'hc_emergencia_id' => HcEmergencia::factory(),
            'tipo_orden_id' => TipoOrden::query()->create([
                'descripcion' => $this->faker->words(2, true),
                'estado' => 'Activo',
            ])->id,
            'procedimiento_id' => $procedimiento->id,
            'detalle' => $procedimiento->denominacion,
            'prioridad' => $this->faker->randomElement(array_column(PrioridadOrden::cases(), 'value')),
            'fecha_hora_solicitud' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'resultado' => 'Pendiente',
            'profesional_salud_id' => ProfesionalSalud::factory(),
            'estado' => EstadoSolicitudExamen::Solicitado->value,
            'guia' => null,
        ];
    }
}
