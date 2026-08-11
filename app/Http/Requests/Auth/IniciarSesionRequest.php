<?php

namespace App\Http\Requests\Auth;

use App\Mail\IntentoAccesoBloqueadoMail;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IniciarSesionRequest extends FormRequest
{
    private const MAX_INTENTOS = 5;

    private const MINUTOS_BLOQUEO = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validacion para iniciar sesion.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'recordarme' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->asegurarQueNoEsteBloqueado();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('recordarme'))) {
            RateLimiter::hit($this->claveDeBloqueo(), self::MINUTOS_BLOQUEO * 60);

            if ($this->notificarBloqueoSiCorresponde()) {
                $segundos = RateLimiter::availableIn($this->claveDeBloqueo());

                throw ValidationException::withMessages([
                    'email' => "Demasiados intentos. Intenta nuevamente en {$segundos} segundos.",
                ]);
            }

            throw ValidationException::withMessages([
                'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
            ]);
        }

        if (! Auth::user()->activo) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'El usuario se encuentra inactivo. Comunicate con el administrador del sistema.',
            ]);
        }

        RateLimiter::clear($this->claveDeBloqueo());
    }

    private function asegurarQueNoEsteBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->claveDeBloqueo(), self::MAX_INTENTOS)) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->claveDeBloqueo());

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Intenta nuevamente en {$segundos} segundos.",
        ]);
    }

    private function notificarBloqueoSiCorresponde(): bool
    {
        if (! RateLimiter::tooManyAttempts($this->claveDeBloqueo(), self::MAX_INTENTOS)) {
            return false;
        }

        event(new Lockout($this));

        $usuario = User::query()
            ->where('email', $this->string('email')->toString())
            ->first();

        if ($usuario === null) {
            return true;
        }

        Mail::to($usuario->email)->send(new IntentoAccesoBloqueadoMail(
            usuario: $usuario,
            ip: $this->ip() ?? 'IP no disponible',
            minutosBloqueo: self::MINUTOS_BLOQUEO,
        ));

        return true;
    }

    private function claveDeBloqueo(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
