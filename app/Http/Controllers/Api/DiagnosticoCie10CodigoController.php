<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCatalogo;
use App\Enums\GeneroCie10;
use App\Http\Controllers\Controller;
use App\Models\DiagnosticoCie10;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiagnosticoCie10CodigoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:255'],
            'genero' => ['required', Rule::enum(GeneroCie10::class)],
            'estado' => ['required', Rule::enum(EstadoCatalogo::class)],
        ], [], [
            'codigo' => 'codigo',
            'nombre' => 'nombre',
            'genero' => 'genero',
            'estado' => 'estado',
        ]);

        $diagnostico = DiagnosticoCie10::query()
            ->where('codigo', $datos['codigo'])
            ->first();

        if ($diagnostico === null) {
            return response()->json([
                'message' => 'No existe diagnóstico CIE10 con el codigo indicado.',
            ], 404);
        }

        $diagnostico->forceFill([
            'nombre' => $datos['nombre'],
            'genero' => $datos['genero'],
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => 'Diagnóstico CIE10 actualizado correctamente.',
            'data' => [
                'id' => $diagnostico->id,
                'codigo' => $diagnostico->codigo,
                'nombre' => $diagnostico->nombre,
                'genero' => $diagnostico->genero instanceof GeneroCie10
                    ? $diagnostico->genero->value
                    : $diagnostico->genero,
                'estado' => $diagnostico->estado instanceof EstadoCatalogo
                    ? $diagnostico->estado->value
                    : $diagnostico->estado,
            ],
        ]);
    }
}
