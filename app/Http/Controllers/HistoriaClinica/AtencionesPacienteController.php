<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;

class AtencionesPacienteController extends Controller
{
    public function __invoke()
    {
        return view('historia-clinica.atenciones-paciente.index');
    }
}
