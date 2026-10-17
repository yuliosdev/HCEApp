<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\Controller;
use App\Models\ProfesionalSalud;
use App\Support\GestionClinica\RegistrosClinicos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfesionalSaludCodigoServicioController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo_servicio' => ['required', 'string', 'max:50'],
            'nombres' => ['required', 'string', 'max:255'],
            'tipo_colegiatura' => ['required', 'string', 'max:100', Rule::in(RegistrosClinicos::tiposColegiatura())],
            'cmp' => ['nullable', 'string', 'max:30'],
            'estado' => ['required', Rule::enum(EstadoCatalogo::class)],
        ], [], [
            'codigo_servicio' => 'codigo servicio',
            'nombres' => 'nombres',
            'tipo_colegiatura' => 'tipo colegiatura',
            'cmp' => 'cmp',
            'estado' => 'estado',
        ]);

        $profesional = ProfesionalSalud::query()
            ->where('codigo_servicio', $datos['codigo_servicio'])
            ->first();

        if ($profesional === null) {
            return response()->json([
                'message' => 'No existe profesional de salud con el codigo_servicio indicado.',
            ], 404);
        }

        $profesional->forceFill([
            'nombres' => $datos['nombres'],
            'tipo_colegiatura' => $datos['tipo_colegiatura'],
            'cmp' => $datos['cmp'] ?? null,
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => 'Profesional de salud actualizado correctamente.',
            'data' => [
                'id' => $profesional->id,
                'codigo_servicio' => $profesional->codigo_servicio,
                'nombres' => $profesional->nombres,
                'tipo_colegiatura' => $profesional->tipo_colegiatura,
                'cmp' => $profesional->cmp,
                'estado' => $profesional->estado instanceof EstadoCatalogo
                    ? $profesional->estado->value
                    : $profesional->estado,
            ],
        ]);
    }
}
