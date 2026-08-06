<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TestTecnologiasController extends Controller
{
    public function __invoke(): View
    {
        return view('test-tecnologias.index');
    }
}
