<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoAtencion;
use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use App\Models\ProfesionalSalud;
use App\Support\GestionClinica\RegistrosClinicos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HcEmergenciaRegistroController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datosAtencion = $request->validate([
            'hce_id' => ['required', 'integer'],
        ], [], [
            'hce_id' => 'id de la atencion',
        ]);

        $atencion = HcEmergencia::query()->find($datosAtencion['hce_id']);

        if ($atencion === null) {
            return response()->json([
                'message' => 'No existe la atención',
            ], 404);
        }

        if ($atencion->estado_atencion !== EstadoAtencion::Abierta) {
            return response()->json([
                'message' => "La atención existe pero está 'Cerrada'",
            ], 409);
        }

        if (blank($request->input('numero_atencion')) || (int) $request->input('numero_atencion') === 0) {
            return response()->json([
                'message' => 'No existe número de admisión',
            ], 422);
        }

        $datos = $this->validarDatosClinicos($request);

        $resultado = DB::transaction(function () use ($atencion, $datos): array {
            $paciente = Paciente::query()->updateOrCreate(
                ['id_paciente_externo' => $datos['paciente']['id_paciente_externo']],
                [
                    'nombre' => $datos['paciente']['nombres'],
                    'direccion' => $datos['paciente']['direccion'] ?? null,
                    'telefono' => $datos['paciente']['telefono'] ?? null,
                    'sexo' => $datos['paciente']['sexo'],
                    'fecha_nacimiento' => $datos['paciente']['fecha_nacimiento'],
                    'tipo_documento' => $datos['paciente']['tipo_documento'],
                    'numero_documento' => $datos['paciente']['numero_documento'],
                ]
            );

            $profesional = ProfesionalSalud::query()->updateOrCreate(
                ['codigo_servicio' => $datos['profesional']['codigo_servicio']],
                [
                    'nombres' => $datos['profesional']['nombres'],
                    'tipo_colegiatura' => $datos['profesional']['tipo_colegiatura'],
                    'cmp' => $datos['profesional']['cmp'],
                ]
            );

            $atencion->forceFill([
                'numero_atencion' => $datos['numero_atencion'],
                'paciente_id' => $paciente->id,
                'profesional_salud_id' => $profesional->id,
                'sin_identificacion' => 'No',
            ])->save();

            return [
                'hce_id' => $atencion->id,
                'numero_atencion' => $atencion->numero_atencion,
                'paciente_id' => $paciente->id,
                'profesional_salud_id' => $profesional->id,
            ];
        });

        return response()->json([
            'message' => 'Se actualizaron datos en la atención',
            'data' => $resultado,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarDatosClinicos(Request $request): array
    {
        try {
            return $request->validate([
                'numero_atencion' => ['required', 'integer', 'min:1'],
                'paciente' => ['required', 'array'],
                'paciente.nombres' => ['required', 'string', 'max:255'],
                'paciente.direccion' => ['nullable', 'string', 'max:255'],
                'paciente.telefono' => ['nullable', 'string', 'max:30'],
                'paciente.sexo' => ['required', 'string', 'max:30'],
                'paciente.fecha_nacimiento' => ['required', 'date'],
                'paciente.tipo_documento' => ['required', 'string', 'max:30'],
                'paciente.numero_documento' => ['required', 'string', 'max:30'],
                'paciente.id_paciente_externo' => ['required', 'integer'],
                'profesional' => ['required', 'array'],
                'profesional.nombres' => ['required', 'string', 'max:255'],
                'profesional.tipo_colegiatura' => ['required', 'string', 'max:100', Rule::in(RegistrosClinicos::tiposColegiatura())],
                'profesional.cmp' => ['required', 'string', 'max:30'],
                'profesional.codigo_servicio' => ['required', 'string', 'max:50'],
            ], [], [
                'numero_atencion' => 'numero de admision',
                'paciente.nombres' => 'nombres del paciente',
                'paciente.direccion' => 'direccion del paciente',
                'paciente.telefono' => 'telefono del paciente',
                'paciente.sexo' => 'sexo del paciente',
                'paciente.fecha_nacimiento' => 'fecha de nacimiento del paciente',
                'paciente.tipo_documento' => 'tipo de documento del paciente',
                'paciente.numero_documento' => 'numero de documento del paciente',
                'paciente.id_paciente_externo' => 'id externo del paciente',
                'profesional.nombres' => 'nombres del profesional',
                'profesional.tipo_colegiatura' => 'tipo de colegiatura del profesional',
                'profesional.cmp' => 'cmp del profesional',
                'profesional.codigo_servicio' => 'codigo de servicio del profesional',
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }
}
