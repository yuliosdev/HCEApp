<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\Controller;
use App\Models\Procedimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProcedimientoCodigoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30'],
            'denominacion' => ['required', 'string', 'max:255'],
            'estado' => ['required', Rule::enum(EstadoCatalogo::class)],
        ], [], [
            'codigo' => 'codigo',
            'denominacion' => 'denominacion',
            'estado' => 'estado',
        ]);

        $procedimiento = Procedimiento::query()->firstOrNew([
            'codigo' => $datos['codigo'],
        ]);

        $procedimiento->forceFill([
            'denominacion' => $datos['denominacion'],
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => $procedimiento->wasRecentlyCreated
                ? 'Procedimiento creado correctamente.'
                : 'Procedimiento actualizado correctamente.',
            'data' => [
                'id' => $procedimiento->id,
                'codigo' => $procedimiento->codigo,
                'denominacion' => $procedimiento->denominacion,
                'estado' => $procedimiento->estado instanceof EstadoCatalogo
                    ? $procedimiento->estado->value
                    : $procedimiento->estado,
            ],
        ]);
    }
}
