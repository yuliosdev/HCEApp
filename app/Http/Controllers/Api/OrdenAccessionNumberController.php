<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoAtencion;
use App\Http\Controllers\Controller;
use App\Models\Orden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrdenAccessionNumberController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $request->merge([
            'accession_number' => trim((string) $request->input('accession_number', '')),
        ]);

        $datos = $request->validate([
            'accession_number' => ['required', 'string', 'max:20'],
        ], [], [
            'accession_number' => 'numero de accession de la orden',
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
            'accession_number' => $datos['accession_number'],
        ])->save();

        return response()->json([
            'message' => 'Accession number de la orden actualizado correctamente.',
            'data' => [
                'id' => $orden->id,
                'hc_emergencia_id' => $orden->hc_emergencia_id,
                'numero_atencion' => $orden->hcEmergencia?->numero_atencion,
                'accession_number' => $orden->accession_number,
            ],
        ]);
    }
}
