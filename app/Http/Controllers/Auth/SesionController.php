<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IniciarSesionRequest;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SesionController extends Controller
{
    public function crear(): View
    {
        return view('auth.login', [
            'configuracion' => ConfiguracionSistema::actual(),
        ]);
    }

    public function guardar(IniciarSesionRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        toastr()->success('Bienvenido a HCEApp.', [], 'Acceso autorizado');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destruir(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        flash()->success('Sesion cerrada correctamente.', [], 'Sesion finalizada');

        return redirect()->route('login');
    }
}
