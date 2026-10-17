<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use Illuminate\Http\JsonResponse;

class HcEmergenciaDiagnosticosIngresoController extends Controller
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

        $diagnosticos = $atencion->diagnosticosIngreso()
            ->with('diagnosticoCie10')
            ->whereHas('diagnosticoCie10')
            ->orderBy('id')
            ->get()
            ->map(fn ($diagnostico): array => [
                'diagnostico_cie10_id' => $diagnostico->diagnostico_cie10_id,
                'codigo' => $diagnostico->diagnosticoCie10?->codigo,
                'nombre' => $diagnostico->diagnosticoCie10?->nombre,
            ])
            ->values();

        return response()->json([
            'id' => $atencion->id,
            'numero_atencion' => $atencion->numero_atencion,
            'diagnosticos' => $diagnosticos,
        ]);
    }
}
