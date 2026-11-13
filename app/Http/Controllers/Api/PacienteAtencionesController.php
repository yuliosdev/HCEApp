<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;

class PacienteAtencionesController extends Controller
{
    public function __invoke(string $idPacienteExterno): JsonResponse
    {
        if (! ctype_digit($idPacienteExterno) || (int) $idPacienteExterno < 1) {
            return response()->json([
                'message' => 'El id externo del paciente no es válido.',
            ], 422);
        }

        $paciente = Paciente::query()
            ->where('id_paciente_externo', (int) $idPacienteExterno)
            ->first();

        if ($paciente === null) {
            return response()->json([
                'message' => 'No existe un paciente con el id externo indicado.',
            ], 404);
        }

        $atenciones = $paciente->hcEmergencias()
            ->with([
                'profesionalResponsable',
                'diagnosticosIngreso' => fn ($query) => $query
                    ->with('diagnosticoCie10')
                    ->orderBy('id'),
            ])
            ->orderByDesc('fecha_hora_inicio_episodio')
            ->orderByDesc('id')
            ->get()
            ->map(function ($atencion): array {
                $diagnostico = $atencion->diagnosticosIngreso->first(
                    fn ($item): bool => $item->diagnosticoCie10 !== null,
                );

                return [
                    'id' => $atencion->id,
                    'numero_atencion' => $atencion->numero_atencion,
                    'fecha_hora_inicio_episodio' => $atencion->fecha_hora_inicio_episodio?->format('Y-m-d\TH:i:s'),
                    'codigo_servicio' => $atencion->profesionalResponsable?->codigo_servicio,
                    'profesional_medico' => $atencion->profesionalResponsable?->nombres,
                    'codigo_diagnostico_ingreso' => $diagnostico?->diagnosticoCie10?->codigo,
                    'diagnostico_ingreso' => $diagnostico?->diagnosticoCie10?->nombre,
                ];
            })
            ->values();

        return response()->json([
            'id_paciente_externo' => (int) $paciente->id_paciente_externo,
            'atenciones' => $atenciones,
        ]);
    }
}
