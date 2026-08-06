<?php

use App\Http\Controllers\Auth\SesionController;
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
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [SesionController::class, 'destruir'])->name('logout');

    if (app()->environment(['local', 'testing'])) {
        Route::get('/test/tecnologias', TestTecnologiasController::class)->name('test.tecnologias');
    }
});
