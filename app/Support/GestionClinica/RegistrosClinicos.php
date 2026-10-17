<?php

namespace App\Support\GestionClinica;

use App\Enums\FactorRh;
use App\Enums\GrupoSanguineo;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\ProfesionalSalud;
use App\Support\Seguridad\Permisos;

final class RegistrosClinicos
{
    /**
     * Configuracion funcional de las vistas maestras de gestion clinica.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function todos(): array
    {
        return [
            'pacientes' => [
                'titulo' => 'Pacientes',
                'singular' => 'paciente',
                'kicker' => 'Gestion clinica',
                'descripcion' => 'Administra la identificacion basica de los pacientes atendidos en emergencia.',
                'modelo' => Paciente::class,
                'tabla' => 'pacientes',
                'orden' => 'nombre',
                'placeholder' => 'Buscar por historia, nombre, documento, telefono, direccion o codigo externo',
                'permiso_ver' => Permisos::PACIENTES_VER,
                'permiso_crear' => Permisos::PACIENTES_CREAR,
                'permiso_editar' => Permisos::PACIENTES_EDITAR,
                'etiqueta_uso' => 'referencias clinicas',
                'relaciones_uso' => ['hcEmergencias', 'alergias', 'antecedentesPatologicos'],
                'campos' => [
                    ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 255],
                    ['nombre' => 'direccion', 'etiqueta' => 'Direccion', 'tipo' => 'textarea', 'requerido' => false, 'max' => 255],
                    ['nombre' => 'telefono', 'etiqueta' => 'Telefono', 'tipo' => 'text', 'requerido' => false, 'max' => 30],
                    ['nombre' => 'sexo', 'etiqueta' => 'Sexo', 'tipo' => 'select', 'requerido' => false, 'opciones' => ['Masculino', 'Femenino', 'No especificado']],
                    ['nombre' => 'fecha_nacimiento', 'etiqueta' => 'Fecha de nacimiento', 'tipo' => 'date', 'requerido' => false],
                    ['nombre' => 'tipo_documento', 'etiqueta' => 'Tipo documento', 'tipo' => 'select', 'requerido' => false, 'opciones' => ['DNI', 'CE', 'Pasaporte', 'Sin documento']],
                    ['nombre' => 'numero_documento', 'etiqueta' => 'Numero documento', 'tipo' => 'text', 'requerido' => false, 'max' => 30, 'unico_compuesto' => ['tipo_documento', 'numero_documento']],
                    ['nombre' => 'grupo_sanguineo', 'etiqueta' => 'Grupo sanguineo', 'tipo' => 'select', 'requerido' => true, 'opciones' => self::valoresEnum(GrupoSanguineo::class)],
                    ['nombre' => 'factor_rh', 'etiqueta' => 'Factor RH', 'tipo' => 'select', 'requerido' => true, 'opciones' => self::valoresEnum(FactorRh::class)],
                    ['nombre' => 'id_paciente_externo', 'etiqueta' => 'ID paciente externo', 'tipo' => 'number', 'requerido' => false],
                    ['nombre' => 'numero_historia', 'etiqueta' => 'Numero historia', 'tipo' => 'text', 'requerido' => false, 'max' => 50, 'unico' => true],
                ],
            ],
            'profesionales' => [
                'titulo' => 'Profesionales',
                'singular' => 'profesional',
                'kicker' => 'Gestion clinica',
                'descripcion' => 'Administra profesionales de salud disponibles para triaje y atencion clinica.',
                'modelo' => ProfesionalSalud::class,
                'tabla' => 'profesionales_salud',
                'orden' => 'nombres',
                'placeholder' => 'Buscar por nombres, especialidad, colegiatura, CMP, RNE o servicio',
                'permiso_ver' => Permisos::PROFESIONALES_VER,
                'permiso_crear' => Permisos::PROFESIONALES_CREAR,
                'permiso_editar' => Permisos::PROFESIONALES_EDITAR,
                'tiene_estado' => true,
                'etiqueta_uso' => 'atenciones',
                'relaciones_uso' => ['triajes', 'atencionesResponsables'],
                'campos' => [
                    ['nombre' => 'nombres', 'etiqueta' => 'Nombres', 'tipo' => 'text', 'requerido' => true, 'max' => 255],
                    ['nombre' => 'especialidad_id', 'etiqueta' => 'Especialidad', 'tipo' => 'model_select', 'requerido' => false, 'modelo_opciones' => Especialidad::class, 'opcion_valor' => 'id', 'opcion_texto' => 'nombre', 'relacion' => 'especialidad', 'relacion_campo' => 'nombre'],
                    ['nombre' => 'tipo_colegiatura', 'etiqueta' => 'Tipo colegiatura', 'tipo' => 'select', 'requerido' => false, 'opciones' => self::tiposColegiatura()],
                    ['nombre' => 'cmp', 'etiqueta' => 'N° Colegiatura', 'tipo' => 'text', 'requerido' => false, 'max' => 30],
                    ['nombre' => 'rne', 'etiqueta' => 'RNE', 'tipo' => 'text', 'requerido' => false, 'max' => 30],
                    ['nombre' => 'codigo_servicio', 'etiqueta' => 'Codigo servicio', 'tipo' => 'text', 'requerido' => false, 'max' => 50],
                ],
            ],
        ];
    }

    /**
     * Devuelve la configuracion de una vista clinica por su clave de ruta.
     *
     * @return array<string, mixed>
     */
    public static function obtener(string $registroClinico): array
    {
        abort_unless(array_key_exists($registroClinico, self::todos()), 404);

        return self::todos()[$registroClinico];
    }

    /**
     * Valores validos para el tipo de colegiatura de profesionales de salud.
     *
     * @return array<int, string>
     */
    public static function tiposColegiatura(): array
    {
        return [
            'CMP',
            'CEP',
            'Col.Químico Farm',
            'Col.Odontológico',
            'Col.Biólogos',
            'Col.Obstetrices',
            'Col.Trabajadores Sociales',
            'Col.Psicólogos',
            'Col.Tecnólogos Médicos',
            'Col.Nutricionistas',
            'Prof.Salud sin colegiatura',
        ];
    }

    /**
     * Extrae valores de un enum respaldado por string para controles select.
     *
     * @param  class-string  $enum
     * @return array<int, string>
     */
    private static function valoresEnum(string $enum): array
    {
        return array_map(fn ($case): string => $case->value, $enum::cases());
    }
}
