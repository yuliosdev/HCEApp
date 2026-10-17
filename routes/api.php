<?php

use App\Http\Controllers\Api\HcEmergenciaRegistroController;
use App\Http\Controllers\Api\DiagnosticoCie10CodigoController;
use App\Http\Controllers\Api\ExamenSistemaExternoController;
use App\Http\Controllers\Api\HcEmergenciaExamenesLaboratorioController;
use App\Http\Controllers\Api\HcEmergenciaDiagnosticosIngresoController;
use App\Http\Controllers\Api\HcEmergenciaPrescripcionesMedicamentosController;
use App\Http\Controllers\Api\HcEmergenciaOrdenesController;
use App\Http\Controllers\Api\HcEmergenciaPdfApiController;
use App\Http\Controllers\Api\MedicamentoProductoExternoController;
use App\Http\Controllers\Api\OrdenEstadoController;
use App\Http\Controllers\Api\PrescripcionEstadoController;
use App\Http\Controllers\Api\ProcedimientoCodigoController;
use App\Http\Controllers\Api\ProfesionalSaludCodigoServicioController;
use App\Http\Controllers\Api\SolicitudExamenEstadoController;
use Illuminate\Support\Facades\Route;

Route::post('/hc-emergencia/registrar-datos-atencion', HcEmergenciaRegistroController::class)
    ->name('api.hc-emergencia.registrar-datos-atencion');

Route::post('/examenes/actualizar-por-sistema-externo', ExamenSistemaExternoController::class)
    ->name('api.examenes.actualizar-por-sistema-externo');

Route::post('/medicamentos/actualizar-por-producto-externo', MedicamentoProductoExternoController::class)
    ->name('api.medicamentos.actualizar-por-producto-externo');

Route::post('/procedimientos/actualizar-por-codigo', ProcedimientoCodigoController::class)
    ->name('api.procedimientos.actualizar-por-codigo');

Route::post('/profesionales-salud/actualizar-por-codigo-servicio', ProfesionalSaludCodigoServicioController::class)
    ->name('api.profesionales-salud.actualizar-por-codigo-servicio');

Route::post('/diagnosticos-cie10/actualizar-por-codigo', DiagnosticoCie10CodigoController::class)
    ->name('api.diagnosticos-cie10.actualizar-por-codigo');

Route::get('/hc-emergencia/{atencion}/historia-clinica/pdf', HcEmergenciaPdfApiController::class)
    ->whereNumber('atencion')
    ->name('api.hc-emergencia.historia-clinica.pdf');

Route::get('/hc-emergencia/{numeroAtencion}/examenes-laboratorio', HcEmergenciaExamenesLaboratorioController::class)
    ->whereNumber('numeroAtencion')
    ->name('api.hc-emergencia.examenes-laboratorio');

Route::get('/hc-emergencia/{numeroAtencion}/diagnosticos-ingreso', HcEmergenciaDiagnosticosIngresoController::class)
    ->whereNumber('numeroAtencion')
    ->name('api.hc-emergencia.diagnosticos-ingreso');

Route::get('/hc-emergencia/{numeroAtencion}/prescripciones-medicamentos', HcEmergenciaPrescripcionesMedicamentosController::class)
    ->whereNumber('numeroAtencion')
    ->name('api.hc-emergencia.prescripciones-medicamentos');

Route::get('/hc-emergencia/{numeroAtencion}/ordenes', HcEmergenciaOrdenesController::class)
    ->whereNumber('numeroAtencion')
    ->name('api.hc-emergencia.ordenes');

Route::patch('/solicitud-examenes/{id}/estado', SolicitudExamenEstadoController::class)
    ->whereNumber('id')
    ->name('api.solicitud-examenes.estado');

Route::patch('/prescripciones/{id}/estado', PrescripcionEstadoController::class)
    ->whereNumber('id')
    ->name('api.prescripciones.estado');

Route::patch('/ordenes/{id}/estado', OrdenEstadoController::class)
    ->whereNumber('id')
    ->name('api.ordenes.estado');
