<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoAtencion;
use App\Enums\EstadoSolicitudExamen;
use App\Http\Controllers\Controller;
use App\Models\Orden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrdenEstadoController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $estadosPermitidos = [
            EstadoSolicitudExamen::EnProceso->value,
            EstadoSolicitudExamen::Terminado->value,
            EstadoSolicitudExamen::Pagado->value,
        ];

        $datos = $request->validate([
            'estado' => ['required', 'string', Rule::in($estadosPermitidos)],
        ], [], [
            'estado' => 'estado de la orden',
        ]);

        $orden = Orden::query()
            ->with('hcEmergencia')
            ->find($id);

        if ($orden === null) {
            return response()->json([
                'message' => 'No existe la orden indicada.',
            ], 404);
        }

        if ($orden->hcEmergencia?->estado_atencion !== EstadoAtencion::Abierta) {
            return response()->json([
                'message' => "La atención existe pero está 'Cerrada'",
            ], 409);
        }

        $orden->forceFill([
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => 'Estado de la orden actualizado correctamente.',
            'data' => [
                'id' => $orden->id,
                'procedimiento_id' => $orden->procedimiento_id,
                'hc_emergencia_id' => $orden->hc_emergencia_id,
                'numero_atencion' => $orden->hcEmergencia?->numero_atencion,
                'estado' => $orden->estado instanceof EstadoSolicitudExamen
                    ? $orden->estado->value
                    : $orden->estado,
            ],
        ]);
    }
}
