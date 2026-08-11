<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Services\Catalogos\ProcedimientosArchivoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProcedimientosArchivoController extends Controller
{
    /**
     * Descarga el catalogo de procedimientos en formato XLSX.
     */
    public function exportarExcel(Request $request, ProcedimientosArchivoService $archivos): BinaryFileResponse
    {
        $rutaArchivo = $archivos->generarExcel(
            $request->string('busqueda')->toString(),
            $request->string('estado')->toString(),
        );

        return response()
            ->download($rutaArchivo, 'procedimientos.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Descarga el catalogo de procedimientos en formato PDF.
     */
    public function exportarPdf(Request $request, ProcedimientosArchivoService $archivos)
    {
        $procedimientos = $archivos
            ->consulta($request->string('busqueda')->toString(), $request->string('estado')->toString())
            ->with(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'])
            ->orderBy('codigo')
            ->get();

        return Pdf::loadView('catalogos.procedimientos.exportar-pdf', [
            'procedimientos' => $procedimientos,
            'filtros' => [
                'busqueda' => $request->string('busqueda')->toString() ?: 'Todos',
                'estado' => $request->string('estado')->toString() ?: 'Todos',
            ],
            'generadoPor' => $request->user(),
            'generadoEn' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('procedimientos.pdf');
    }

    /**
     * Descarga la plantilla oficial para importar procedimientos.
     */
    public function descargarPlantilla(ProcedimientosArchivoService $archivos): BinaryFileResponse
    {
        $rutaArchivo = $archivos->generarPlantilla();

        return response()
            ->download($rutaArchivo, 'plantilla_procedimientos.xlsx')
            ->deleteFileAfterSend();
    }
}
