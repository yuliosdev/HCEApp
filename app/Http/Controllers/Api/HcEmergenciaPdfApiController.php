<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use App\Services\HistoriaClinica\HcEmergenciaPdfService;
use Illuminate\Http\Request;

class HcEmergenciaPdfApiController extends Controller
{
    public function __invoke(Request $request, HcEmergencia $atencion, HcEmergenciaPdfService $pdfService)
    {
        $pdf = $pdfService->generar($atencion, $request->user());

        return $pdf->stream($pdfService->nombreArchivo($atencion));
    }
}
