<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Support\Catalogos\CatalogosClinicos;
use Illuminate\View\View;

class CatalogoClinicoController extends Controller
{
    /**
     * Muestra un catalogo clinico administrable.
     */
    public function __invoke(string $catalogo): View
    {
        $configuracion = CatalogosClinicos::obtener($catalogo);

        return view('catalogos.clinicos.index', [
            'catalogo' => $catalogo,
            'titulo' => $configuracion['titulo'],
        ]);
    }
}
