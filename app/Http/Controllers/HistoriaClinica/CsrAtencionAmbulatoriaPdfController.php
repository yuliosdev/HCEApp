<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Services\Interoperabilidad\CsrAtencionesClient;
use App\Services\Interoperabilidad\CsrAtencionesException;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CsrAtencionAmbulatoriaPdfController extends Controller
{
    public function __invoke(Paciente $paciente, string $numero, CsrAtencionesClient $cliente): Response
    {
        try {
            $contenido = $cliente->historiaClinicaPdf($numero);
        } catch (CsrAtencionesException $exception) {
            abort($exception->status && $exception->status >= 400 && $exception->status < 500 ? $exception->status : 502, $exception->mensajeUsuario());
        }

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::slug('historia clinica ambulatoria '.$numero).'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
