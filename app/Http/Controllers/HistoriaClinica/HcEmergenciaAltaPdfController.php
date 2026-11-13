<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class HcEmergenciaAltaPdfController extends Controller
{
    /**
     * Genera la ficha imprimible de alta de la atencion.
     */
    public function __invoke(HcEmergencia $atencion)
    {
        $atencion->load([
            'paciente',
            'diagnosticosIngreso.diagnosticoCie10',
            'diagnosticosEgreso.diagnosticoCie10',
            'altaMedicamentos.medicamentoCatalogo',
            'profesionalEgreso.especialidad',
        ]);

        $logoPath = public_path('images/login/LOGOC.jpg');
        $fallbackLogoPath = public_path('images/login/logo-default.png');
        $logoDataUri = $this->logoDataUri(is_file($logoPath) ? $logoPath : $fallbackLogoPath);
        $firmaDataUri = $this->storedImageDataUri($atencion->profesionalEgreso?->firma_path);

        $filename = Str::slug('ficha alta paciente hce '.$atencion->id).'.pdf';

        return Pdf::loadView('historia-clinica.atenciones-paciente.alta-pdf', [
            'atencion' => $atencion,
            'paciente' => $atencion->paciente,
            'logoDataUri' => $logoDataUri,
            'firmaDataUri' => $firmaDataUri,
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

    private function storedImageDataUri(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }
}
