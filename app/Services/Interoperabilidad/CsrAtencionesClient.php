<?php

namespace App\Services\Interoperabilidad;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CsrAtencionesClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtener(int $idPacienteExterno): array
    {
        $baseUrl = rtrim((string) config('services.external_api.url'), '/');
        $token = (string) config('services.external_api.token');

        if ($baseUrl === '' || $token === '') {
            throw new CsrAtencionesException('configuration');
        }

        try {
            $respuesta = Http::withHeaders([
                'x-api-key' => $token,
            ])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->get($baseUrl.'/api/external/sisclin/pacientes/'.$idPacienteExterno.'/atenciones', [
                    'limite' => 1000,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('API externa de atenciones ambulatorias no disponible.', [
                'id_paciente_externo' => $idPacienteExterno,
                'error' => $exception->getMessage(),
            ]);

            throw new CsrAtencionesException('unavailable');
        }

        if ($respuesta->successful()) {
            $atenciones = $respuesta->json('data.atenciones');

            if (! is_array($atenciones)) {
                throw new CsrAtencionesException('invalid_response', $respuesta->status());
            }

            return $atenciones;
        }

        $tipo = match ($respuesta->status()) {
            401 => 'unauthorized',
            404 => 'not_found',
            default => 'server',
        };

        Log::warning('API externa de atenciones ambulatorias respondió con error.', [
            'id_paciente_externo' => $idPacienteExterno,
            'status' => $respuesta->status(),
        ]);

        throw new CsrAtencionesException($tipo, $respuesta->status());
    }

    public function historiaClinicaPdf(string $numeroAdmision): string
    {
        $numeroAdmision = trim($numeroAdmision);
        $baseUrl = rtrim((string) config('services.external_api.url'), '/');
        $token = (string) config('services.external_api.token');

        if ($numeroAdmision === '' || ! ctype_digit($numeroAdmision)) {
            throw new CsrAtencionesException('invalid_admission', 422);
        }

        if ($baseUrl === '' || $token === '') {
            throw new CsrAtencionesException('configuration');
        }

        try {
            $respuesta = Http::withHeaders([
                'x-api-key' => $token,
            ])
                ->accept('application/pdf, application/json')
                ->connectTimeout(5)
                ->timeout(120)
                ->get($baseUrl.'/api/external/sisclin/admisiones/'.$numeroAdmision.'/historia-clinica-pdf');
        } catch (ConnectionException $exception) {
            Log::warning('PDF externo de historia ambulatoria no disponible.', [
                'numero_admision' => $numeroAdmision,
                'error' => $exception->getMessage(),
            ]);

            throw new CsrAtencionesException('unavailable');
        }

        if ($respuesta->successful() && str_starts_with($respuesta->body(), '%PDF')) {
            return $respuesta->body();
        }

        $tipo = match ($respuesta->status()) {
            401 => 'unauthorized',
            404 => 'not_found',
            422 => 'validation',
            default => 'server',
        };

        Log::warning('API externa rechazó el PDF de historia ambulatoria.', [
            'numero_admision' => $numeroAdmision,
            'status' => $respuesta->status(),
        ]);

        throw new CsrAtencionesException($tipo, $respuesta->status());
    }
}
