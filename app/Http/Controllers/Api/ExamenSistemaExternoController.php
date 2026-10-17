<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\Controller;
use App\Models\Examen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamenSistemaExternoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'id_sistema_externo' => ['required', 'integer'],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', Rule::enum(EstadoCatalogo::class)],
        ], [], [
            'id_sistema_externo' => 'id sistema externo',
            'descripcion' => 'descripcion',
            'estado' => 'estado',
        ]);

        $examen = Examen::query()
            ->where('id_sistema_externo', $datos['id_sistema_externo'])
            ->first();

        if ($examen === null) {
            return response()->json([
                'message' => 'No existe examen con el id_sistema_externo indicado.',
            ], 404);
        }

        $examen->forceFill([
            'descripcion' => $datos['descripcion'],
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => 'Examen actualizado correctamente.',
            'data' => [
                'id' => $examen->id,
                'id_sistema_externo' => $examen->id_sistema_externo,
                'descripcion' => $examen->descripcion,
                'estado' => $examen->estado instanceof EstadoCatalogo
                    ? $examen->estado->value
                    : $examen->estado,
            ],
        ]);
    }
}
