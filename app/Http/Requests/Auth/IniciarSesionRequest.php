<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IniciarSesionRequest extends FormRequest
{
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
            RateLimiter::hit($this->claveDeBloqueo());

            throw ValidationException::withMessages([
                'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
            ]);
        }

        RateLimiter::clear($this->claveDeBloqueo());
    }

    private function asegurarQueNoEsteBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->claveDeBloqueo(), 5)) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->claveDeBloqueo());

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Intenta nuevamente en {$segundos} segundos.",
        ]);
    }

    private function claveDeBloqueo(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
