<?php

namespace App\Services\HistoriaClinica;

use App\Models\HcEmergencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class HcEmergenciaPdfService
{
    public function generar(HcEmergencia $atencion, $generadoPor = null, ?Carbon $generadoEn = null)
    {
        $generadoEn ??= now();

        $atencion->loadMissing('paciente');
        $paciente = $atencion->paciente;

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
            'ambiente',
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
            'ordenes.procedimiento',
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
            'generadoPor' => $generadoPor,
            'generadoEn' => $generadoEn,
        ])->setPaper('a4');
    }

    public function nombreArchivo(HcEmergencia $atencion): string
    {
        return 'historia-clinica-emergencia-'.$atencion->id.'.pdf';
    }
}
