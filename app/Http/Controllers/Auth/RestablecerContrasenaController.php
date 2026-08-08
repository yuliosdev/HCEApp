<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RestablecerContrasenaController extends Controller
{
    public function editar(Request $request, string $token): View
    {
        return view('auth.restablecer-contrasena', [
            'configuracion' => ConfiguracionSistema::actual(),
            'email' => $request->query('email'),
            'token' => $token,
        ]);
    }

    public function actualizar(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($usuario) use ($request): void {
                $usuario->forceFill([
                    'password' => Hash::make($request->string('password')->toString()),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            },
        );

        if ($estado === Password::PASSWORD_RESET) {
            flash()->success('Contrasena restablecida correctamente.', [], 'Acceso seguro');

            return redirect()->route('login')->with('estado', __($estado));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($estado)]);
    }
}
