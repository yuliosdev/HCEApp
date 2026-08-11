<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use App\Support\GestionClinica\RegistrosClinicos;
use Illuminate\View\View;

class RegistroClinicoController extends Controller
{
    /**
     * Muestra una vista maestra de gestion clinica.
     */
    public function __invoke(string $registroClinico): View
    {
        $configuracion = RegistrosClinicos::obtener($registroClinico);

        return view('gestion-clinica.registros.index', [
            'registroClinico' => $registroClinico,
            'titulo' => $configuracion['titulo'],
        ]);
    }
}
