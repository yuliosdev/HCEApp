<?php

namespace Database\Seeders;

use App\Models\ConfiguracionSistema;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConfiguracionSistemaSeeder extends Seeder
{
    /**
     * Registra la configuracion visual inicial del login.
     */
    public function run(): void
    {
        $administradorId = User::query()->where('email', 'admin@hceapp.test')->value('id');

        ConfiguracionSistema::query()->updateOrCreate(
            ['id' => 1],
            [
                'nombre_sistema' => 'HCEApp',
                'titulo_login' => 'Acceso seguro a HCEApp',
                'subtitulo_login' => 'Historia clinica electronica de emergencias',
                'texto_institucional_login' => 'Plataforma clinica para atencion de emergencias con trazabilidad, seguridad y control institucional.',
                'logo_path' => '/images/login/logo-default.png',
                'login_background_path' => '/images/login/login-background-default.png',
                'registrado_por' => $administradorId,
                'actualizado_por' => $administradorId,
            ],
        );
    }
}
