<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use App\Services\GestionClinica\RegistrosClinicosArchivoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistrosClinicosArchivoController extends Controller
{
    /**
     * Descarga la vista clinica en formato XLSX.
     */
    public function exportarExcel(string $registroClinico, Request $request, RegistrosClinicosArchivoService $archivos): BinaryFileResponse
    {
        $rutaArchivo = $archivos->generarExcel(
            $registroClinico,
            $request->string('busqueda')->toString(),
            $request->string('estado')->toString(),
        );

        return response()
            ->download($rutaArchivo, $archivos->nombreArchivo($registroClinico).'.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Descarga la vista clinica en formato PDF.
     */
    public function exportarPdf(string $registroClinico, Request $request, RegistrosClinicosArchivoService $archivos)
    {
        $configuracion = $archivos->configuracion($registroClinico);
        $relacionesSelect = collect($configuracion['campos'])
            ->filter(fn (array $campo): bool => ($campo['tipo'] ?? 'text') === 'model_select')
            ->pluck('relacion')
            ->values()
            ->all();
        $registros = $archivos
            ->consulta($registroClinico, $request->string('busqueda')->toString(), $request->string('estado')->toString())
            ->with(array_merge(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'], $relacionesSelect))
            ->orderBy($configuracion['orden'])
            ->get();

        return Pdf::loadView('gestion-clinica.registros.exportar-pdf', [
            'configuracion' => $configuracion,
            'registros' => $registros,
            'tieneEstado' => $archivos->tieneEstado($registroClinico),
            'filtros' => [
                'busqueda' => $request->string('busqueda')->toString() ?: 'Todos',
                'estado' => $request->string('estado')->toString() ?: 'Todos',
            ],
            'generadoPor' => $request->user(),
            'generadoEn' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download($archivos->nombreArchivo($registroClinico).'.pdf');
    }

    /**
     * Descarga la plantilla oficial para importar la vista clinica.
     */
    public function descargarPlantilla(string $registroClinico, RegistrosClinicosArchivoService $archivos): BinaryFileResponse
    {
        $rutaArchivo = $archivos->generarPlantilla($registroClinico);

        return response()
            ->download($rutaArchivo, 'plantilla_'.$archivos->nombreArchivo($registroClinico).'.xlsx')
            ->deleteFileAfterSend();
    }
}
