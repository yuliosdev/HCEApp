<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class RecuperarContrasenaController extends Controller
{
    public function crear(): View
    {
        return view('auth.recuperar-contrasena', [
            'configuracion' => ConfiguracionSistema::actual(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $estado = Password::sendResetLink($request->only('email'));

        if ($estado === Password::RESET_LINK_SENT) {
            flash()->success('Se envio el enlace de recuperacion al correo registrado.', [], 'Recuperacion de clave');

            return back()->with('estado', __($estado));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($estado)]);
    }
}
