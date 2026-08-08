<?php

use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Auth\RestablecerContrasenaController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\ConfiguracionLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TestTecnologiasController;
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/configuracion/login', [ConfiguracionLoginController::class, 'editar'])->name('configuracion.login.editar');
    Route::put('/configuracion/login', [ConfiguracionLoginController::class, 'actualizar'])->name('configuracion.login.actualizar');
    Route::post('/logout', [SesionController::class, 'destruir'])->name('logout');

    if (app()->environment(['local', 'testing'])) {
        Route::get('/test/tecnologias', TestTecnologiasController::class)->name('test.tecnologias');
    }
});
