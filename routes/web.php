<?php

use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Auth\RestablecerContrasenaController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\Catalogos\CatalogoClinicoController;
use App\Http\Controllers\Catalogos\CatalogosClinicosArchivoController;
use App\Http\Controllers\Catalogos\ProcedimientosArchivoController;
use App\Http\Controllers\Catalogos\ProcedimientosController;
use App\Http\Controllers\ConfiguracionLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GestionClinica\RegistroClinicoController;
use App\Http\Controllers\GestionClinica\RegistrosClinicosArchivoController;
use App\Http\Controllers\HistoriaClinica\AtencionesPacienteController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaAltaPdfController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaExamenesPdfController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaLaboratorioPdfController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaMedicamentosPdfController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaOrdenesPdfController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaPanelController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaPdfController;
use App\Http\Controllers\HistoriaClinica\HcEmergenciaTicketController;
use App\Http\Controllers\HistoriaClinica\HistoriasAbiertasArchivoController;
use App\Http\Controllers\HistoriaClinica\HistoriasAbiertasController;
use App\Http\Controllers\HistoriaClinica\HistoriaClinicaPacienteController;
use App\Http\Controllers\HistoriaClinica\PacienteDashboardController;
use App\Http\Controllers\Seguridad\RolesPermisosController;
use App\Http\Controllers\Seguridad\UsuariosController;
use App\Http\Controllers\TestTecnologiasController;
use App\Support\Seguridad\Permisos;
use Illuminate\Support\Facades\Route;

// Ruta principal de HCEApp.
Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
)->name('inicio');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SesionController::class, 'crear'])->name('login');
    Route::post('/login', [SesionController::class, 'guardar'])->name('login.guardar');
    Route::get('/recuperar-contrasena', [RecuperarContrasenaController::class, 'crear'])->name('password.request');
    Route::post('/recuperar-contrasena', [RecuperarContrasenaController::class, 'guardar'])->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [RestablecerContrasenaController::class, 'editar'])->name('password.reset');
    Route::post('/restablecer-contrasena', [RestablecerContrasenaController::class, 'actualizar'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('can:'.Permisos::DASHBOARD_VER)
        ->name('dashboard');

    Route::get('/seguridad/usuarios', UsuariosController::class)
        ->middleware('can:'.Permisos::USUARIOS_VER)
        ->name('seguridad.usuarios.index');

    Route::get('/seguridad/roles-y-permisos', RolesPermisosController::class)
        ->middleware('can:'.Permisos::ROLES_VER)
        ->name('seguridad.roles.index');

    Route::get('/gestion-clinica/pacientes', RegistroClinicoController::class)
        ->defaults('registroClinico', 'pacientes')
        ->middleware('can:'.Permisos::PACIENTES_VER)
        ->name('gestion-clinica.pacientes.index');

    Route::get('/gestion-clinica/profesionales', RegistroClinicoController::class)
        ->defaults('registroClinico', 'profesionales')
        ->middleware('can:'.Permisos::PROFESIONALES_VER)
        ->name('gestion-clinica.profesionales.index');

    Route::get('/historia-clinica/atenciones-paciente', AtencionesPacienteController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.index');

    Route::get('/historia-clinica/historias-abiertas', HistoriasAbiertasController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.historias-abiertas.index');

    Route::get('/historia-clinica/historias-abiertas/exportar/xlsx', [HistoriasAbiertasArchivoController::class, 'exportarExcel'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.historias-abiertas.exportar.xlsx');

    Route::get('/historia-clinica/historias-abiertas/exportar/pdf', [HistoriasAbiertasArchivoController::class, 'exportarPdf'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.historias-abiertas.exportar.pdf');

    Route::get('/historia-clinica/emergencia/{atencion}/ver', [HcEmergenciaPanelController::class, 'verGeneral'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.ver');

    Route::get('/historia-clinica/emergencia/{atencion}/editar', [HcEmergenciaPanelController::class, 'editarGeneral'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.editar');

    Route::get('/historia-clinica/emergencia/{atencion}/imprimir', [HcEmergenciaPdfController::class, 'imprimirGeneral'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.imprimir');

    Route::get('/historia-clinica/emergencia/{atencion}/ticket', [HcEmergenciaTicketController::class, 'general'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.ticket');

    Route::get('/historia-clinica/emergencia/{atencion}/laboratorio/pdf', HcEmergenciaLaboratorioPdfController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.laboratorio.pdf');

    Route::get('/historia-clinica/emergencia/{atencion}/examenes/pdf', HcEmergenciaExamenesPdfController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.examenes.pdf');

    Route::get('/historia-clinica/emergencia/{atencion}/ordenes/pdf', HcEmergenciaOrdenesPdfController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.ordenes.pdf');

    Route::get('/historia-clinica/emergencia/{atencion}/medicamentos/pdf', HcEmergenciaMedicamentosPdfController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.medicamentos.pdf');

    Route::get('/historia-clinica/emergencia/{atencion}/alta/pdf', HcEmergenciaAltaPdfController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.emergencia.alta.pdf');

    Route::get('/historia-clinica/atenciones-paciente/{paciente}/dashboard', PacienteDashboardController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.dashboard');

    Route::get('/historia-clinica/atenciones-paciente/{paciente}/historia-clinica', HistoriaClinicaPacienteController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.historia');

    Route::get('/historia-clinica/atenciones-paciente/{paciente}/emergencia/nuevo', [HcEmergenciaPanelController::class, 'nuevo'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.emergencia.nuevo');

    Route::get('/historia-clinica/atenciones-paciente/{paciente}/emergencia/{atencion}/ver', [HcEmergenciaPanelController::class, 'ver'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.emergencia.ver');

    Route::get('/historia-clinica/atenciones-paciente/{paciente}/emergencia/{atencion}/editar', [HcEmergenciaPanelController::class, 'editar'])
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.emergencia.editar');

    Route::get('/historia-clinica/atenciones-paciente/{paciente}/emergencia/{atencion}/imprimir', HcEmergenciaPdfController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.emergencia.imprimir');

    Route::get('/historia-clinica/atenciones-paciente/{paciente}/emergencia/{atencion}/ticket', HcEmergenciaTicketController::class)
        ->middleware('can:'.Permisos::HISTORIA_CLINICA_VER)
        ->name('historia-clinica.atenciones-paciente.emergencia.ticket');

    foreach ([
        'pacientes' => 'pacientes',
        'profesionales' => 'profesionales',
    ] as $claveRegistro => $segmentoRuta) {
        Route::middleware(['can:'.($claveRegistro === 'pacientes' ? Permisos::PACIENTES_VER : Permisos::PROFESIONALES_VER), 'role:Administrador'])
            ->prefix('/gestion-clinica/'.$segmentoRuta)
            ->name('gestion-clinica.'.$claveRegistro.'.')
            ->group(function () use ($claveRegistro): void {
                Route::get('/exportar/xlsx', [RegistrosClinicosArchivoController::class, 'exportarExcel'])
                    ->defaults('registroClinico', $claveRegistro)
                    ->name('exportar.xlsx');
                Route::get('/exportar/pdf', [RegistrosClinicosArchivoController::class, 'exportarPdf'])
                    ->defaults('registroClinico', $claveRegistro)
                    ->name('exportar.pdf');
                Route::get('/plantilla-importacion', [RegistrosClinicosArchivoController::class, 'descargarPlantilla'])
                    ->defaults('registroClinico', $claveRegistro)
                    ->name('plantilla');
            });
    }

    Route::get('/catalogos/procedimientos', ProcedimientosController::class)
        ->middleware('can:'.Permisos::CATALOGOS_VER)
        ->name('catalogos.procedimientos.index');

    foreach ([
        'cie10' => 'cie10',
        'medicamentos' => 'medicamentos',
        'ups' => 'ups',
        'examenes' => 'examenes',
        'especialidades' => 'especialidades',
        'signos-vitales' => 'signos-vitales',
        'glasgow' => 'glasgow',
        'alergias' => 'alergias',
        'tipo-ordenes' => 'tipo-ordenes',
        'prioridades' => 'prioridades',
        'ambientes' => 'ambientes',
    ] as $claveCatalogo => $segmentoRuta) {
        Route::get('/catalogos/'.$segmentoRuta, CatalogoClinicoController::class)
            ->defaults('catalogo', $claveCatalogo)
            ->middleware('can:'.Permisos::CATALOGOS_VER)
            ->name('catalogos.'.$claveCatalogo.'.index');

        Route::middleware(['can:'.Permisos::CATALOGOS_VER, 'role:Administrador'])
            ->prefix('/catalogos/'.$segmentoRuta)
            ->name('catalogos.'.$claveCatalogo.'.')
            ->group(function () use ($claveCatalogo): void {
                Route::get('/exportar/xlsx', [CatalogosClinicosArchivoController::class, 'exportarExcel'])
                    ->defaults('catalogo', $claveCatalogo)
                    ->name('exportar.xlsx');
                Route::get('/exportar/pdf', [CatalogosClinicosArchivoController::class, 'exportarPdf'])
                    ->defaults('catalogo', $claveCatalogo)
                    ->name('exportar.pdf');
                Route::get('/plantilla-importacion', [CatalogosClinicosArchivoController::class, 'descargarPlantilla'])
                    ->defaults('catalogo', $claveCatalogo)
                    ->name('plantilla');
            });
    }

    Route::middleware(['can:'.Permisos::CATALOGOS_VER, 'role:Administrador'])
        ->prefix('/catalogos/procedimientos')
        ->name('catalogos.procedimientos.')
        ->group(function (): void {
            Route::get('/exportar/xlsx', [ProcedimientosArchivoController::class, 'exportarExcel'])
                ->name('exportar.xlsx');
            Route::get('/exportar/pdf', [ProcedimientosArchivoController::class, 'exportarPdf'])
                ->name('exportar.pdf');
            Route::get('/plantilla-importacion', [ProcedimientosArchivoController::class, 'descargarPlantilla'])
                ->name('plantilla');
        });

    Route::get('/configuracion/login', [ConfiguracionLoginController::class, 'editar'])
        ->middleware('can:'.Permisos::CONFIGURACION_VER)
        ->name('configuracion.login.editar');
    Route::put('/configuracion/login', [ConfiguracionLoginController::class, 'actualizar'])
        ->middleware('can:'.Permisos::CONFIGURACION_EDITAR)
        ->name('configuracion.login.actualizar');
    Route::post('/logout', [SesionController::class, 'destruir'])->name('logout');

    if (app()->environment(['local', 'testing'])) {
        Route::get('/test/tecnologias', TestTecnologiasController::class)->name('test.tecnologias');
        Route::view('/test/cards-neon', 'test-cards.index')->name('test.cards-neon');
    }
});
