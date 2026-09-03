<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Models\HcEmergencia;
use App\Models\Paciente;
use Illuminate\Http\Request;

class HcEmergenciaPanelController
{
    /**
     * Presenta el panel para registrar una nueva atencion de emergencia.
     */
    public function nuevo(Request $request, Paciente $paciente)
    {
        return view('historia-clinica.atenciones-paciente.emergencia', [
            'paciente' => $paciente,
            'atencion' => null,
            'modo' => 'nuevo',
        ]);
    }

    /**
     * Presenta una atencion existente en modo solo lectura.
     */
    public function ver(Request $request, Paciente $paciente, HcEmergencia $atencion)
    {
        abort_unless($atencion->paciente_id === $paciente->id, 404);

        return view('historia-clinica.atenciones-paciente.emergencia', [
            'paciente' => $paciente,
            'atencion' => $atencion,
            'modo' => 'ver',
        ]);
    }

    /**
     * Presenta una atencion existente sin exigir paciente en la ruta.
     */
    public function verGeneral(Request $request, HcEmergencia $atencion)
    {
        return view('historia-clinica.atenciones-paciente.emergencia', [
            'paciente' => $atencion->paciente,
            'atencion' => $atencion,
            'modo' => 'ver',
        ]);
    }

    /**
     * Presenta una atencion existente en modo edicion.
     */
    public function editar(Request $request, Paciente $paciente, HcEmergencia $atencion)
    {
        abort_unless($atencion->paciente_id === $paciente->id, 404);

        return view('historia-clinica.atenciones-paciente.emergencia', [
            'paciente' => $paciente,
            'atencion' => $atencion,
            'modo' => 'editar',
        ]);
    }

    /**
     * Presenta una atencion existente en modo edicion sin exigir paciente en la ruta.
     */
    public function editarGeneral(Request $request, HcEmergencia $atencion)
    {
        return view('historia-clinica.atenciones-paciente.emergencia', [
            'paciente' => $atencion->paciente,
            'atencion' => $atencion,
            'modo' => 'editar',
        ]);
    }
}
