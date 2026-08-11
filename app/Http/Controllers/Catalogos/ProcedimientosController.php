<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProcedimientosController extends Controller
{
    /**
     * Muestra el catalogo de procedimientos.
     */
    public function __invoke(): View
    {
        return view('catalogos.procedimientos.index');
    }
}
