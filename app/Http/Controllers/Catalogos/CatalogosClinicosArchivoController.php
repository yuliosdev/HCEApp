<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Services\Catalogos\CatalogosClinicosArchivoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogosClinicosArchivoController extends Controller
{
    /**
     * Descarga el catalogo generico en formato XLSX.
     */
    public function exportarExcel(string $catalogo, Request $request, CatalogosClinicosArchivoService $archivos): BinaryFileResponse
    {
        $rutaArchivo = $archivos->generarExcel(
            $catalogo,
            $request->string('busqueda')->toString(),
            $request->string('estado')->toString(),
        );

        return response()
            ->download($rutaArchivo, $archivos->nombreArchivo($catalogo).'.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Descarga el catalogo generico en formato PDF.
     */
    public function exportarPdf(string $catalogo, Request $request, CatalogosClinicosArchivoService $archivos)
    {
        $configuracion = $archivos->configuracion($catalogo);
        $registros = $archivos
            ->consulta($catalogo, $request->string('busqueda')->toString(), $request->string('estado')->toString())
            ->with(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'])
            ->orderBy($configuracion['orden'])
            ->get();

        return Pdf::loadView('catalogos.clinicos.exportar-pdf', [
            'configuracion' => $configuracion,
            'registros' => $registros,
            'filtros' => [
                'busqueda' => $request->string('busqueda')->toString() ?: 'Todos',
                'estado' => $request->string('estado')->toString() ?: 'Todos',
            ],
            'generadoPor' => $request->user(),
            'generadoEn' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download($archivos->nombreArchivo($catalogo).'.pdf');
    }

    /**
     * Descarga la plantilla oficial para importar el catalogo generico.
     */
    public function descargarPlantilla(string $catalogo, CatalogosClinicosArchivoService $archivos): BinaryFileResponse
    {
        $rutaArchivo = $archivos->generarPlantilla($catalogo);

        return response()
            ->download($rutaArchivo, 'plantilla_'.$archivos->nombreArchivo($catalogo).'.xlsx')
            ->deleteFileAfterSend();
    }
}
