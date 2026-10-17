<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoSolicitudExamen;
use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use Illuminate\Http\JsonResponse;

class HcEmergenciaPrescripcionesMedicamentosController extends Controller
{
    public function __invoke(int $numeroAtencion): JsonResponse
    {
        $atencion = HcEmergencia::query()
            ->where('numero_atencion', $numeroAtencion)
            ->first();

        if ($atencion === null) {
            return response()->json([
                'message' => 'No existe la atención con el número de atención indicado.',
            ], 404);
        }

        $medicamentos = $atencion->prescripciones()
            ->with(['medicamentoCatalogo', 'profesionalSalud'])
            ->where('estado', EstadoSolicitudExamen::Solicitado->value)
            ->orderBy('id')
            ->get()
            ->map(fn ($prescripcion): array => [
                'id' => $prescripcion->id,
                'medicamento_id' => $prescripcion->medicamento_id,
                'medicamento' => $prescripcion->medicamento,
                'cantidad' => $prescripcion->cantidad,
                'dosis' => $prescripcion->dosis,
                'duracion' => $prescripcion->duracion,
                'via' => $prescripcion->via,
                'id_producto_externo' => $prescripcion->medicamentoCatalogo?->id_producto_externo,
                'profesional_salud_id' => $prescripcion->profesional_salud_id,
                'nombres' => $prescripcion->profesionalSalud?->nombres,
                'codigo_servicio' => $prescripcion->profesionalSalud?->codigo_servicio,
            ])
            ->values();

        return response()->json([
            'id' => $atencion->id,
            'numero_atencion' => $atencion->numero_atencion,
            'medicamentos' => $medicamentos,
        ]);
    }
}
