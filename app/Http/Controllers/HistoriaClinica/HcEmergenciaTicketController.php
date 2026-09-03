<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class HcEmergenciaTicketController extends Controller
{
    /**
     * Genera el ticket termico de una historia clinica de emergencia vinculada a paciente.
     */
    public function __invoke(Request $request, Paciente $paciente, HcEmergencia $atencion)
    {
        abort_unless($atencion->paciente_id === $paciente->id, 404);

        return $this->generar($atencion);
    }

    /**
     * Genera el ticket termico de una historia clinica sin exigir paciente en la ruta.
     */
    public function general(Request $request, HcEmergencia $atencion)
    {
        return $this->generar($atencion);
    }

    private function generar(HcEmergencia $atencion)
    {
        $atencion->loadMissing(['paciente', 'profesionalResponsable']);

        return Pdf::loadView('historia-clinica.atenciones-paciente.emergencia-ticket', [
            'atencion' => $atencion,
        ])
            ->setPaper([0, 0, 226.77, 430], 'portrait')
            ->stream('ticket-emergencia-'.$atencion->id.'.pdf');
    }
}
