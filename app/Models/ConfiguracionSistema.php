<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    protected $table = 'configuraciones_sistema';

    protected $guarded = ['id'];

    /**
     * Obtiene la configuracion activa del sistema o devuelve valores por defecto.
     */
    public static function actual(): self
    {
        return self::query()->first() ?? new self([
            'nombre_sistema' => 'HCEApp',
            'titulo_login' => 'Acceso seguro a HCEApp',
            'subtitulo_login' => 'Historia clinica electronica de emergencias',
            'texto_institucional_login' => 'Plataforma clinica para atencion de emergencias con trazabilidad, seguridad y control institucional.',
            'logo_path' => '/images/login/logo-default.png',
            'login_background_path' => '/images/login/login-background-default.png',
        ]);
    }
}
