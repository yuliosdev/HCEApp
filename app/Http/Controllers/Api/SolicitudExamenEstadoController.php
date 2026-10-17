<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoAtencion;
use App\Enums\EstadoSolicitudExamen;
use App\Http\Controllers\Controller;
use App\Models\SolicitudExamen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SolicitudExamenEstadoController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'string', Rule::enum(EstadoSolicitudExamen::class)],
        ], [], [
            'estado' => 'estado del examen',
        ]);

        $solicitud = SolicitudExamen::query()
            ->with('hcEmergencia')
            ->find($id);

        if ($solicitud === null) {
            return response()->json([
                'message' => 'No existe el examen solicitado.',
            ], 404);
        }

        if ($solicitud->hcEmergencia?->estado_atencion !== EstadoAtencion::Abierta) {
            return response()->json([
                'message' => "La atención existe pero está 'Cerrada'",
            ], 409);
        }

        $solicitud->forceFill([
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => 'Estado del examen actualizado correctamente.',
            'data' => [
                'id' => $solicitud->id,
                'examen_id' => $solicitud->examen_id,
                'hc_emergencia_id' => $solicitud->hc_emergencia_id,
                'numero_atencion' => $solicitud->hcEmergencia?->numero_atencion,
                'estado' => $solicitud->estado instanceof EstadoSolicitudExamen
                    ? $solicitud->estado->value
                    : $solicitud->estado,
            ],
        ]);
    }
}
