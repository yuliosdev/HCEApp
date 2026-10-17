<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCatalogo;
use App\Http\Controllers\Controller;
use App\Models\Medicamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicamentoProductoExternoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'id_producto_externo' => ['required', 'integer'],
            'descripcion' => ['required', 'string', 'max:255'],
            'presentacion' => ['nullable', 'string', 'max:150'],
            'generico' => ['nullable', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'estado' => ['required', Rule::enum(EstadoCatalogo::class)],
        ], [], [
            'id_producto_externo' => 'id producto externo',
            'descripcion' => 'descripcion',
            'presentacion' => 'presentacion',
            'generico' => 'generico',
            'stock' => 'stock',
            'estado' => 'estado',
        ]);

        $medicamento = Medicamento::query()
            ->where('id_producto_externo', $datos['id_producto_externo'])
            ->first();

        if ($medicamento === null) {
            return response()->json([
                'message' => 'No existe medicamento con el id_producto_externo indicado.',
            ], 404);
        }

        $medicamento->forceFill([
            'descripcion' => $datos['descripcion'],
            'presentacion' => $datos['presentacion'] ?? null,
            'generico' => $datos['generico'] ?? null,
            'stock' => $datos['stock'],
            'estado' => $datos['estado'],
        ])->save();

        return response()->json([
            'message' => 'Medicamento actualizado correctamente.',
            'data' => [
                'id' => $medicamento->id,
                'id_producto_externo' => $medicamento->id_producto_externo,
                'descripcion' => $medicamento->descripcion,
                'presentacion' => $medicamento->presentacion,
                'generico' => $medicamento->generico,
                'stock' => $medicamento->stock,
                'estado' => $medicamento->estado instanceof EstadoCatalogo
                    ? $medicamento->estado->value
                    : $medicamento->estado,
            ],
        ]);
    }
}
