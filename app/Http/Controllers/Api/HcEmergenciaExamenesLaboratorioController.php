<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoSolicitudExamen;
use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use Illuminate\Http\JsonResponse;

class HcEmergenciaExamenesLaboratorioController extends Controller
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

        $examenes = $atencion->solicitudesExamenes()
            ->with(['examen', 'profesionalSalud'])
            ->where('estado', EstadoSolicitudExamen::Solicitado->value)
            ->whereHas('examen')
            ->orderBy('id')
            ->get()
            ->map(fn ($solicitud): array => [
                'id' => $solicitud->id,
                'examen_id' => $solicitud->examen_id,
                'descripcion' => $solicitud->examen?->descripcion,
                'id_sistema_externo' => $solicitud->examen?->id_sistema_externo,
                'profesional_salud_id' => $solicitud->profesional_salud_id,
                'nombres' => $solicitud->profesionalSalud?->nombres,
                'codigo_servicio' => $solicitud->profesionalSalud?->codigo_servicio,
                'prioridad' => $solicitud->prioridad?->value ?? $solicitud->prioridad,
            ])
            ->values();

        return response()->json([
            'id' => $atencion->id,
            'numero_atencion' => $atencion->numero_atencion,
            'examenes' => $examenes,
        ]);
    }
}
