<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Services\HistoriaClinica\HistoriasAbiertasArchivoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HistoriasAbiertasArchivoController extends Controller
{
    public function exportarExcel(Request $request, HistoriasAbiertasArchivoService $archivos): BinaryFileResponse
    {
        $filtros = $archivos->filtrosNormalizados(
            $request->string('desde')->toString(),
            $request->string('hasta')->toString(),
            $request->string('estado')->toString(),
        );

        $rutaArchivo = $archivos->generarExcel($filtros['desde'], $filtros['hasta'], $filtros['estado']);

        return response()
            ->download($rutaArchivo, $archivos->nombreArchivo($filtros).'.xlsx')
            ->deleteFileAfterSend();
    }

    public function exportarPdf(Request $request, HistoriasAbiertasArchivoService $archivos)
    {
        $filtros = $archivos->filtrosNormalizados(
            $request->string('desde')->toString(),
            $request->string('hasta')->toString(),
            $request->string('estado')->toString(),
        );

        $atenciones = $archivos
            ->consulta($filtros['desde'], $filtros['hasta'], $filtros['estado'])
            ->with([
                'ambiente',
                'paciente',
                'profesionalResponsable',
                'prioridad',
                'diagnosticosIngreso.diagnosticoCie10',
            ])
            ->orderByDesc('fecha_hora_atencion')
            ->orderByDesc('id')
            ->get();

        return Pdf::loadView('historia-clinica.historias-abiertas.exportar-pdf', [
            'atenciones' => $atenciones,
            'filtros' => $filtros,
            'estadisticas' => $archivos->estadisticas($filtros['desde'], $filtros['hasta']),
            'archivos' => $archivos,
            'generadoPor' => $request->user(),
            'generadoEn' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download($archivos->nombreArchivo($filtros).'.pdf');
    }
}
