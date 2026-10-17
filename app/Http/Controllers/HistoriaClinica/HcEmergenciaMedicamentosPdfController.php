<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class HcEmergenciaMedicamentosPdfController extends Controller
{
    /**
     * Genera el formato imprimible de prescripcion de medicamentos de la atencion.
     */
    public function __invoke(HcEmergencia $atencion)
    {
        $atencion->load([
            'paciente',
            'diagnosticosIngreso.diagnosticoCie10',
            'prescripciones.profesionalSalud.especialidad',
        ]);

        $logoPath = public_path('images/login/LOGOC.jpg');
        $fallbackLogoPath = public_path('images/login/logo-default.png');
        $logoDataUri = $this->logoDataUri(is_file($logoPath) ? $logoPath : $fallbackLogoPath);

        $filename = Str::slug('prescripcion medicamentos hce '.$atencion->id).'.pdf';

        return Pdf::loadView('historia-clinica.atenciones-paciente.medicamentos-pdf', [
            'atencion' => $atencion,
            'paciente' => $atencion->paciente,
            'logoDataUri' => $logoDataUri,
            'generadoEn' => now(),
        ])
            ->setPaper('a4')
            ->stream($filename);
    }

    private function logoDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
