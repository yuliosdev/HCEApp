<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfiguracionLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas para actualizar la identidad visual del login.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nombre_sistema' => ['required', 'string', 'max:80'],
            'titulo_login' => ['required', 'string', 'max:120'],
            'subtitulo_login' => ['required', 'string', 'max:160'],
            'texto_institucional_login' => ['nullable', 'string', 'max:240'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=120,min_height=120,max_width=2000,max_height=2000'],
            'login_background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:min_width=1200,min_height=700,max_width=4000,max_height=2500'],
        ];
    }
}
