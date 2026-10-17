<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoSolicitudExamen;
use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use Illuminate\Http\JsonResponse;

class HcEmergenciaOrdenesController extends Controller
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

        $ordenes = $atencion->ordenes()
            ->with(['procedimiento', 'profesionalSalud'])
            ->where('estado', EstadoSolicitudExamen::Solicitado->value)
            ->orderBy('id')
            ->get()
            ->map(fn ($orden): array => [
                'id' => $orden->id,
                'procedimiento_id' => $orden->procedimiento_id,
                'codigo' => $orden->procedimiento?->codigo,
                'detalle' => $orden->detalle,
                'fecha_hora_solicitud' => $orden->fecha_hora_solicitud?->toDateTimeString(),
                'profesional_salud_id' => $orden->profesional_salud_id,
                'nombres' => $orden->profesionalSalud?->nombres,
                'codigo_servicio' => $orden->profesionalSalud?->codigo_servicio,
            ])
            ->values();

        return response()->json([
            'id' => $atencion->id,
            'numero_atencion' => $atencion->numero_atencion,
            'ordenes' => $ordenes,
        ]);
    }
}
