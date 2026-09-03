<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class HcEmergenciaPdfController extends Controller
{
    /**
     * Genera el formato imprimible de la historia clinica de emergencia.
     */
    public function __invoke(Request $request, Paciente $paciente, HcEmergencia $atencion)
    {
        abort_unless($atencion->paciente_id === $paciente->id, 404);

        return $this->generar($request, $atencion, $paciente);
    }

    /**
     * Genera el PDF desde el listado general de historias clinicas.
     */
    public function imprimirGeneral(Request $request, HcEmergencia $atencion)
    {
        return $this->generar($request, $atencion, $atencion->paciente);
    }

    private function generar(Request $request, HcEmergencia $atencion, ?Paciente $paciente)
    {
        $paciente?->load([
            'alergias.alergia',
            'antecedentesPatologicos.diagnosticoCie10',
            'reaccionesAdversasMedicamentos',
            'antecedentesFamiliares.diagnosticoCie10',
            'cirugiasPrevias',
            'hospitalizacionesPrevias.ups',
            'hospitalizacionesPrevias.diagnosticoCie10',
            'medicamentosHabituales',
        ]);

        $atencion->load([
            'paciente',
            'prioridad',
            'profesionalResponsable.especialidad',
            'profesionalTriaje.especialidad',
            'profesionalEgreso.especialidad',
            'signosVitalesTriaje.signoVital',
            'diagnosticosIngreso.diagnosticoCie10',
            'solicitudesExamenes.examen',
            'solicitudesExamenes.profesionalSalud.especialidad',
            'ordenes.tipoOrden',
            'ordenes.profesionalSalud.especialidad',
            'prescripciones.medicamentoCatalogo',
            'prescripciones.profesionalSalud.especialidad',
            'procedimientosRealizados.procedimiento',
            'evoluciones.profesionalSalud.especialidad',
            'interconsultas.profesionalSalud.especialidad',
            'notasEnfermeria.profesionalSalud.especialidad',
            'transferencias',
            'referencia.upsOrigen',
            'referencia.upsDestino',
            'diagnosticosEgreso.diagnosticoCie10',
            'altaMedicamentos.medicamentoCatalogo',
            'documentosAdjuntos.registradoPor',
            'registradoPor',
            'actualizadoPor',
        ]);

        return Pdf::loadView('historia-clinica.atenciones-paciente.emergencia-pdf', [
            'paciente' => $paciente,
            'atencion' => $atencion,
            'generadoPor' => $request->user(),
            'generadoEn' => now(),
        ])
            ->setPaper('a4')
            ->stream('historia-clinica-emergencia-'.$atencion->id.'.pdf');
    }
}
