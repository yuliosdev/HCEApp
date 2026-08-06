<?php

declare(strict_types=1);

use Flasher\Prime\Configuration;

/*
 * Configuracion principal de PHPFlasher para HCEApp.
 *
 * PHPFlasher permite mostrar notificaciones modernas desde Laravel sin escribir
 * JavaScript manual en cada pantalla. Esta configuracion centraliza el adaptador
 * por defecto, la ubicacion de los assets y las opciones visuales generales.
 *
 * @return array<string, mixed> Configuracion de PHPFlasher.
 */
return Configuration::from([
    // Adaptador por defecto: flasher, toastr, noty o sweetalert.
    'default' => env('FLASHER_ADAPTER', 'flasher'),

    // Script principal publicado por php artisan flasher:install.
    'main_script' => '/vendor/flasher/flasher.min.js',

    // Prefijo para los assets cuando la aplicacion se sirve desde subdirectorio o CDN.
    'public_path' => '',

    // Hojas de estilo base que PHPFlasher inyecta automaticamente.
    'styles' => [
        '/vendor/flasher/flasher.min.css',
    ],

    // Opciones generales para las notificaciones del adaptador base.
    'options' => [
        'timeout' => 5000,
        'position' => 'top-right',
    ],

    // Inyecta automaticamente los assets en respuestas HTML de Laravel.
    'inject_assets' => true,

    // Permite que los mensajes puedan pasar por el sistema de traducciones de Laravel.
    'translate' => true,

    // Rutas que no deben recibir inyeccion de assets ni conversion de mensajes flash.
    'excluded_paths' => [],

    // Convierte claves flash de sesion de Laravel en notificaciones visuales.
    'flash_bag' => [
        'success' => ['success'],
        'error' => ['error', 'danger'],
        'warning' => ['warning', 'alarm'],
        'info' => ['info', 'notice', 'alert'],
    ],

    // Limita la cantidad maxima de notificaciones mostradas por respuesta.
    // 'filter' => [
    //     'limit' => 5,
    // ],

    // Opciones particulares de los adaptadores instalados para el proyecto.
    'plugins' => [
        'toastr' => [
            'options' => [
                'closeButton' => true,
                'progressBar' => true,
                'positionClass' => 'toast-top-right',
                'timeOut' => 5000,
            ],
        ],
        'noty' => [
            'options' => [
                'layout' => 'topRight',
                'theme' => 'mint',
                'timeout' => 5000,
                'progressBar' => true,
                'closeWith' => ['click', 'button'],
            ],
        ],
        'sweetalert' => [
            'options' => [
                'timer' => 5000,
                'timerProgressBar' => true,
                'showConfirmButton' => false,
            ],
        ],
    ],

    // Presets reutilizables para mensajes frecuentes del sistema.
    'presets' => [
        'operacion_exitosa' => [
            'type' => 'success',
            'title' => 'Operacion completada',
            'message' => 'La accion se realizo correctamente.',
        ],
    ],
]);
