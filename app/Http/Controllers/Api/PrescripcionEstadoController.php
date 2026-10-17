<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoAtencion;
use App\Enums\EstadoSolicitudExamen;
use App\Http\Controllers\Controller;
use App\Models\Prescripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrescripcionEstadoController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $estadosPermitidos = [
            EstadoSolicitudExamen::EnProceso->value,
            EstadoSolicitudExamen::Terminado->value,
        ];

        $datos = $request->validate([
            'estado' => ['required', 'string', Rule::in($estadosPermitidos)],
        ], [], [
            'estado' => 'estado del medicamento',
        ]);

        $prescripcion = Prescripcion::query()
            ->with('hcEmergencia')
            ->find($id);

        if ($prescripcion === null) {
            return response()->json([
                'message' => 'No existe la prescripción indicada.',
            ], 404);
        }

        if ($prescripcion->hcEmergencia?->estado_atencion !== EstadoAtencion::Abierta) {
            return response()->json([
                'message' => "La atención existe pero está 'Cerrada'",
            ], 409);
        }

        $prescripcion->forceFill([
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => 'Estado del medicamento actualizado correctamente.',
            'data' => [
                'id' => $prescripcion->id,
                'medicamento_id' => $prescripcion->medicamento_id,
                'hc_emergencia_id' => $prescripcion->hc_emergencia_id,
                'numero_atencion' => $prescripcion->hcEmergencia?->numero_atencion,
                'estado' => $prescripcion->estado instanceof EstadoSolicitudExamen
                    ? $prescripcion->estado->value
                    : $prescripcion->estado,
            ],
        ]);
    }
}
