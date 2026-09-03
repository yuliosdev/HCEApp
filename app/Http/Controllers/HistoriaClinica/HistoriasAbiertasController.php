<?php

namespace App\Http\Controllers\HistoriaClinica;

use App\Http\Controllers\Controller;

class HistoriasAbiertasController extends Controller
{
    public function __invoke()
    {
        return view('historia-clinica.historias-abiertas.index');
    }
}
