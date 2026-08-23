<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;
use App\Models\Paciente;

class HistoriaClinicaPacienteController extends Controller
{
    public function __invoke(Paciente $paciente)
    {
        return view('historia-clinica.atenciones-paciente.historia', [
            'paciente' => $paciente,
        ]);
    }
}
