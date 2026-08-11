<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RolesPermisosController extends Controller
{
    /**
     * Muestra la administracion de roles y permisos del sistema.
     */
    public function __invoke(): View
    {
        return view('seguridad.roles.index');
    }
}
