<?php

use App\Http\Controllers\Api\HcEmergenciaRegistroController;
use Illuminate\Support\Facades\Route;

Route::post('/hc-emergencia/registrar-datos-atencion', HcEmergenciaRegistroController::class)
    ->name('api.hc-emergencia.registrar-datos-atencion');
