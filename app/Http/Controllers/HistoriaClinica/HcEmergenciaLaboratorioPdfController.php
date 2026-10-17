<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HcEmergenciaLaboratorioPdfController extends Controller
{
    /**
     * Consulta la API externa de laboratorio en modo test y entrega el PDF consolidado.
     */
    public function __invoke(HcEmergencia $atencion)
    {
        $numeroAtencion = trim((string) $atencion->numero_atencion);

        abort_if($numeroAtencion === '' || ! ctype_digit($numeroAtencion), 422, 'La atención no tiene un número de admisión válido.');

        $baseUrl = rtrim((string) config('services.external_api.url'), '/');
        $token = (string) config('services.external_api.token');

        abort_if($baseUrl === '' || $token === '', 500, 'No se ha configurado la API externa de laboratorio.');

        try {
            $response = Http::withToken($token)
                ->accept('application/pdf')
                ->timeout(120)
                ->get($baseUrl.'/api/external/sislab/admisiones/'.$numeroAtencion.'/guialab-pdf', [
                    'origen' => 'test',
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            Log::error('No se pudo conectar con la API externa de laboratorio.', [
                'numero_atencion' => $numeroAtencion,
                'url' => $baseUrl.'/api/external/sislab/admisiones/{numero}/guialab-pdf',
                'exception' => $exception::class,
            ]);

            abort(502, 'No se pudo conectar con la API externa de laboratorio.');
        } catch (RequestException $exception) {
            $status = $exception->response?->status() ?: 502;
            $message = $exception->response?->json('message')
                ?: 'No se pudo obtener el PDF de exámenes desde la API externa.';

            Log::warning('La API externa de laboratorio rechazó la solicitud del PDF.', [
                'numero_atencion' => $numeroAtencion,
                'external_status' => $status,
                'message' => $message,
            ]);

            abort($status >= 500 ? 502 : $status, $message);
        }

        $filename = Str::slug('laboratorio admision '.$numeroAtencion.' test').'.pdf';

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'application/pdf'),
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
