<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use App\Services\HistoriaClinica\HcEmergenciaPdfService;
use Illuminate\Http\Request;

class HcEmergenciaPdfController extends Controller
{
    public function __construct(private readonly HcEmergenciaPdfService $pdfService)
    {
    }

    /**
     * Genera el formato imprimible de la historia clinica de emergencia.
     */
    public function __invoke(Request $request, Paciente $paciente, HcEmergencia $atencion)
    {
        abort_unless($atencion->paciente_id === $paciente->id, 404);

        return $this->generar($request, $atencion);
    }

    /**
     * Genera el PDF desde el listado general de historias clinicas.
     */
    public function imprimirGeneral(Request $request, HcEmergencia $atencion)
    {
        return $this->generar($request, $atencion);
    }

    private function generar(Request $request, HcEmergencia $atencion)
    {
        $pdf = $this->pdfService->generar($atencion, $request->user());

        return $pdf->stream($this->pdfService->nombreArchivo($atencion));
    }
}
