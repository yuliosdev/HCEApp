<?php

namespace App\Support\Seguridad;

use Illuminate\Support\Collection;

final class Permisos
{
    public const DASHBOARD_VER = 'dashboard.ver';

    public const USUARIOS_VER = 'usuarios.ver';

    public const USUARIOS_CREAR = 'usuarios.crear';

    public const USUARIOS_EDITAR = 'usuarios.editar';

    public const USUARIOS_CAMBIAR_ESTADO = 'usuarios.cambiar_estado';

    public const ROLES_VER = 'roles.ver';

    public const ROLES_CREAR = 'roles.crear';

    public const ROLES_EDITAR = 'roles.editar';

    public const PACIENTES_VER = 'pacientes.ver';

    public const PACIENTES_CREAR = 'pacientes.crear';

    public const PACIENTES_EDITAR = 'pacientes.editar';

    public const PROFESIONALES_VER = 'profesionales.ver';

    public const PROFESIONALES_CREAR = 'profesionales.crear';

    public const PROFESIONALES_EDITAR = 'profesionales.editar';

    public const HISTORIA_CLINICA_VER = 'historia_clinica.ver';

    public const HISTORIA_CLINICA_ABRIR = 'historia_clinica.abrir';

    public const HISTORIA_CLINICA_CERRAR = 'historia_clinica.cerrar';

    public const CATALOGOS_VER = 'catalogos.ver';

    public const CATALOGOS_GESTIONAR = 'catalogos.gestionar';

    public const REPORTES_VER = 'reportes.ver';

    public const AUDITORIA_VER = 'auditoria.ver';

    public const CONFIGURACION_VER = 'configuracion.ver';

    public const CONFIGURACION_EDITAR = 'configuracion.editar';

    /**
     * Define los permisos funcionales agrupados por modulo.
     *
     * @return array<int, array{grupo: string, permisos: array<int, array{nombre: string, descripcion: string}>}>
     */
    public static function definiciones(): array
    {
        return [
            [
                'grupo' => 'Panel principal',
                'permisos' => [
                    ['nombre' => self::DASHBOARD_VER, 'descripcion' => 'Ver panel principal'],
                ],
            ],
            [
                'grupo' => 'Seguridad',
                'permisos' => [
                    ['nombre' => self::USUARIOS_VER, 'descripcion' => 'Ver usuarios'],
                    ['nombre' => self::USUARIOS_CREAR, 'descripcion' => 'Crear usuarios'],
                    ['nombre' => self::USUARIOS_EDITAR, 'descripcion' => 'Editar usuarios'],
                    ['nombre' => self::USUARIOS_CAMBIAR_ESTADO, 'descripcion' => 'Activar o inactivar usuarios'],
                    ['nombre' => self::ROLES_VER, 'descripcion' => 'Ver roles y permisos'],
                    ['nombre' => self::ROLES_CREAR, 'descripcion' => 'Crear roles'],
                    ['nombre' => self::ROLES_EDITAR, 'descripcion' => 'Editar permisos de roles'],
                ],
            ],
            [
                'grupo' => 'Gestion clinica',
                'permisos' => [
                    ['nombre' => self::PACIENTES_VER, 'descripcion' => 'Ver pacientes'],
                    ['nombre' => self::PACIENTES_CREAR, 'descripcion' => 'Crear pacientes'],
                    ['nombre' => self::PACIENTES_EDITAR, 'descripcion' => 'Editar pacientes'],
                    ['nombre' => self::PROFESIONALES_VER, 'descripcion' => 'Ver profesionales'],
                    ['nombre' => self::PROFESIONALES_CREAR, 'descripcion' => 'Crear profesionales'],
                    ['nombre' => self::PROFESIONALES_EDITAR, 'descripcion' => 'Editar profesionales'],
                    ['nombre' => self::HISTORIA_CLINICA_VER, 'descripcion' => 'Ver historias clinicas'],
                    ['nombre' => self::HISTORIA_CLINICA_ABRIR, 'descripcion' => 'Abrir historia clinica de emergencia'],
                    ['nombre' => self::HISTORIA_CLINICA_CERRAR, 'descripcion' => 'Cerrar historia clinica de emergencia'],
                ],
            ],
            [
                'grupo' => 'Catalogos',
                'permisos' => [
                    ['nombre' => self::CATALOGOS_VER, 'descripcion' => 'Ver catalogos clinicos'],
                    ['nombre' => self::CATALOGOS_GESTIONAR, 'descripcion' => 'Gestionar catalogos clinicos'],
                ],
            ],
            [
                'grupo' => 'Reportes y control',
                'permisos' => [
                    ['nombre' => self::REPORTES_VER, 'descripcion' => 'Ver reportes'],
                    ['nombre' => self::AUDITORIA_VER, 'descripcion' => 'Ver auditoria'],
                ],
            ],
            [
                'grupo' => 'Configuracion',
                'permisos' => [
                    ['nombre' => self::CONFIGURACION_VER, 'descripcion' => 'Ver configuracion del sistema'],
                    ['nombre' => self::CONFIGURACION_EDITAR, 'descripcion' => 'Editar configuracion del sistema'],
                ],
            ],
        ];
    }

    /**
     * Devuelve solo los nombres tecnicos de permisos.
     *
     * @return array<int, string>
     */
    public static function nombres(): array
    {
        return self::coleccion()->pluck('nombre')->values()->all();
    }

    /**
     * Define la asignacion inicial de permisos por rol.
     *
     * @return array<string, array<int, string>>
     */
    public static function permisosPorRol(): array
    {
        return [
            'Administrador' => self::nombres(),
            'Operador' => [
                self::DASHBOARD_VER,
                self::PACIENTES_VER,
                self::PACIENTES_CREAR,
                self::PACIENTES_EDITAR,
                self::PROFESIONALES_VER,
                self::HISTORIA_CLINICA_VER,
                self::HISTORIA_CLINICA_ABRIR,
                self::CATALOGOS_VER,
            ],
            'Medico' => [
                self::DASHBOARD_VER,
                self::PACIENTES_VER,
                self::PROFESIONALES_VER,
                self::HISTORIA_CLINICA_VER,
                self::HISTORIA_CLINICA_ABRIR,
                self::HISTORIA_CLINICA_CERRAR,
                self::CATALOGOS_VER,
            ],
            'Enfermeria' => [
                self::DASHBOARD_VER,
                self::PACIENTES_VER,
                self::PROFESIONALES_VER,
                self::HISTORIA_CLINICA_VER,
                self::CATALOGOS_VER,
            ],
            'Laboratorio' => [
                self::DASHBOARD_VER,
                self::HISTORIA_CLINICA_VER,
                self::CATALOGOS_VER,
            ],
            'Auditor' => [
                self::DASHBOARD_VER,
                self::REPORTES_VER,
                self::AUDITORIA_VER,
            ],
        ];
    }

    private static function coleccion(): Collection
    {
        return collect(self::definiciones())->flatMap(fn (array $grupo): array => $grupo['permisos']);
    }
}
