<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    /**
     * Muestra la administracion de usuarios del sistema.
     */
    public function __invoke(): View
    {
        return view('seguridad.usuarios.index');
    }
}
