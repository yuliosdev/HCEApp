<?php

namespace App\Support\Catalogos;

use App\Enums\ColorTriaje;
use App\Enums\GeneroCie10;
use App\Models\Alergia;
use App\Models\Ambiente;
use App\Models\DiagnosticoCie10;
use App\Models\Especialidad;
use App\Models\Examen;
use App\Models\Glasgow;
use App\Models\Medicamento;
use App\Models\Prioridad;
use App\Models\SignoVital;
use App\Models\TipoOrden;
use App\Models\Ups;

final class CatalogosClinicos
{
    /**
     * Configuracion funcional de los catalogos clinicos administrables.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function todos(): array
    {
        return [
            'cie10' => [
                'titulo' => 'CIE10',
                'singular' => 'diagnostico CIE10',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra codigos diagnosticos CIE10 usados en antecedentes, ingreso y egreso clinico.',
                'modelo' => DiagnosticoCie10::class,
                'tabla' => 'diagnosticos_cie10',
                'orden' => 'codigo',
                'placeholder' => 'Buscar por codigo, nombre o genero',
                'etiqueta_uso' => 'referencias clinicas',
                'relaciones_uso' => ['antecedentesPatologicos', 'diagnosticosIngreso', 'diagnosticosEgreso'],
                'campos' => [
                    ['nombre' => 'codigo', 'etiqueta' => 'Codigo', 'tipo' => 'text', 'requerido' => true, 'max' => 20, 'unico' => true],
                    ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'textarea', 'requerido' => true, 'max' => 255],
                    ['nombre' => 'genero', 'etiqueta' => 'Genero', 'tipo' => 'select', 'requerido' => true, 'opciones' => self::valoresEnum(GeneroCie10::class)],
                ],
            ],
            'medicamentos' => [
                'titulo' => 'Medicamentos',
                'singular' => 'medicamento',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra medicamentos, presentaciones y genericos usados en prescripciones y alta.',
                'modelo' => Medicamento::class,
                'tabla' => 'medicamentos',
                'orden' => 'descripcion',
                'placeholder' => 'Buscar por descripcion, presentacion, generico o stock',
                'etiqueta_uso' => 'atenciones',
                'relaciones_uso' => ['prescripciones', 'medicamentosAlta'],
                'campos' => [
                    ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'textarea', 'requerido' => true, 'max' => 255, 'unico' => true],
                    ['nombre' => 'presentacion', 'etiqueta' => 'Presentacion', 'tipo' => 'text', 'requerido' => false, 'max' => 150],
                    ['nombre' => 'generico', 'etiqueta' => 'Generico', 'tipo' => 'text', 'requerido' => false, 'max' => 255],
                    ['nombre' => 'stock', 'etiqueta' => 'Stock', 'tipo' => 'number', 'requerido' => true, 'default' => 0],
                    ['nombre' => 'id_producto_externo', 'etiqueta' => 'ID producto externo', 'tipo' => 'number', 'requerido' => false],
                ],
            ],
            'ups' => [
                'titulo' => 'UPS',
                'singular' => 'UPS',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra unidades productoras de servicios usadas en referencias y hospitalizaciones previas.',
                'modelo' => Ups::class,
                'tabla' => 'ups',
                'orden' => 'codigo',
                'placeholder' => 'Buscar por codigo o denominacion',
                'etiqueta_uso' => 'referencias clinicas',
                'relaciones_uso' => ['hospitalizacionesPrevias', 'referenciasOrigen', 'referenciasDestino'],
                'estado_tipo' => 'booleano',
                'campos' => [
                    ['nombre' => 'codigo', 'etiqueta' => 'Codigo', 'tipo' => 'text', 'requerido' => true, 'max' => 20, 'unico' => true],
                    ['nombre' => 'denominacion', 'etiqueta' => 'Denominacion', 'tipo' => 'textarea', 'requerido' => true, 'max' => 255],
                ],
            ],
            'examenes' => [
                'titulo' => 'Examenes',
                'singular' => 'examen',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra examenes disponibles para solicitudes clinicas de emergencia.',
                'modelo' => Examen::class,
                'tabla' => 'examenes',
                'orden' => 'descripcion',
                'placeholder' => 'Buscar por descripcion o ID externo',
                'etiqueta_uso' => 'solicitudes',
                'relaciones_uso' => ['solicitudes'],
                'campos' => [
                    ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'textarea', 'requerido' => true, 'max' => 255, 'unico' => true],
                    ['nombre' => 'id_sistema_externo', 'etiqueta' => 'ID sistema externo', 'tipo' => 'number', 'requerido' => false],
                ],
            ],
            'especialidades' => [
                'titulo' => 'Especialidades',
                'singular' => 'especialidad',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra especialidades medicas y asistenciales referenciales para profesionales de salud.',
                'modelo' => Especialidad::class,
                'tabla' => 'especialidades',
                'orden' => 'nombre',
                'placeholder' => 'Buscar por nombre o descripcion',
                'etiqueta_uso' => 'referencias',
                'relaciones_uso' => [],
                'campos' => [
                    ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 150, 'unico' => true],
                    ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'textarea', 'requerido' => false, 'max' => 255],
                ],
            ],
            'signos-vitales' => [
                'titulo' => 'Signos Vitales',
                'singular' => 'signo vital',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra signos vitales y unidades usadas durante el triaje.',
                'modelo' => SignoVital::class,
                'tabla' => 'signos_vitales',
                'orden' => 'descripcion',
                'placeholder' => 'Buscar por descripcion o unidad',
                'etiqueta_uso' => 'registros de triaje',
                'relaciones_uso' => ['valoresTriaje'],
                'campos' => [
                    ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'text', 'requerido' => true, 'max' => 150, 'unico_compuesto' => ['descripcion', 'unidad']],
                    ['nombre' => 'unidad', 'etiqueta' => 'Unidad', 'tipo' => 'text', 'requerido' => false, 'max' => 50],
                    ['nombre' => 'rango_inicial', 'etiqueta' => 'Rango Inicial', 'tipo' => 'decimal', 'requerido' => false, 'min' => 0, 'step' => '0.01', 'seccion' => 'rangos_normales'],
                    ['nombre' => 'rango_final', 'etiqueta' => 'Rango Final', 'tipo' => 'decimal', 'requerido' => false, 'min' => 0, 'step' => '0.01', 'seccion' => 'rangos_normales'],
                    ['nombre' => 'favorito', 'etiqueta' => 'Favorito', 'tipo' => 'boolean', 'requerido' => false],
                ],
                'secciones_formulario' => [
                    [
                        'clave' => 'rangos_normales',
                        'titulo' => 'Rangos Normales',
                        'descripcion' => 'Define el limite inicial y final que se usara como referencia clinica del signo vital.',
                        'icono' => 'lucide:activity',
                        'campos' => ['rango_inicial', 'rango_final'],
                    ],
                ],
            ],
            'glasgow' => [
                'titulo' => 'Glasgow',
                'singular' => 'registro Glasgow',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra los puntajes de la escala de Glasgow para valoracion ocular, verbal y motora.',
                'modelo' => Glasgow::class,
                'tabla' => 'glasgow',
                'orden' => 'tipo',
                'placeholder' => 'Buscar por tipo, puntaje o descripcion',
                'etiqueta_uso' => 'triajes',
                'relaciones_uso' => [],
                'campos' => [
                    ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'select', 'requerido' => true, 'opciones' => ['Ocular', 'Motora', 'Verbal']],
                    ['nombre' => 'puntaje', 'etiqueta' => 'Puntaje', 'tipo' => 'number', 'requerido' => true, 'min' => 1, 'max_numero' => 6, 'unico_compuesto' => ['puntaje', 'tipo']],
                    ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'textarea', 'requerido' => true, 'max' => 255],
                ],
            ],
            'alergias' => [
                'titulo' => 'Alergias',
                'singular' => 'alergia',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra alergias y tipos de reaccion asociados a pacientes.',
                'modelo' => Alergia::class,
                'tabla' => 'alergias',
                'orden' => 'nombre',
                'placeholder' => 'Buscar por nombre, tipo o descripcion',
                'etiqueta_uso' => 'pacientes',
                'relaciones_uso' => ['pacienteAlergias'],
                'campos' => [
                    ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'max' => 150, 'unico' => true],
                    ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'text', 'requerido' => false, 'max' => 100],
                    ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'textarea', 'requerido' => false, 'max' => 255],
                ],
            ],
            'tipo-ordenes' => [
                'titulo' => 'Tipo de Ordenes',
                'singular' => 'tipo de orden',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra tipos de orden clinica para laboratorio, imagen, interconsulta y tratamiento.',
                'modelo' => TipoOrden::class,
                'tabla' => 'tipos_ordenes',
                'orden' => 'descripcion',
                'placeholder' => 'Buscar por descripcion',
                'etiqueta_uso' => 'ordenes',
                'relaciones_uso' => ['ordenes'],
                'campos' => [
                    ['nombre' => 'descripcion', 'etiqueta' => 'Descripcion', 'tipo' => 'text', 'requerido' => true, 'max' => 150, 'unico' => true],
                ],
            ],
            'prioridades' => [
                'titulo' => 'Prioridades',
                'singular' => 'prioridad',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra prioridades de triaje, color clinico, tiempo objetivo y orden visual de atencion.',
                'modelo' => Prioridad::class,
                'tabla' => 'prioridades',
                'orden' => 'orden',
                'placeholder' => 'Buscar por prioridad, color, orden o tiempo',
                'etiqueta_uso' => 'historias clinicas',
                'relaciones_uso' => ['hcEmergencias'],
                'campos' => [
                    ['nombre' => 'prioridad', 'etiqueta' => 'Prioridad', 'tipo' => 'text', 'requerido' => true, 'max' => 100, 'unico' => true],
                    ['nombre' => 'color', 'etiqueta' => 'Color', 'tipo' => 'select', 'requerido' => false, 'opciones' => self::valoresEnum(ColorTriaje::class)],
                    ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'requerido' => false, 'unico' => true],
                    ['nombre' => 'tiempo_atencion', 'etiqueta' => 'Tiempo', 'tipo' => 'number', 'requerido' => false, 'min' => 0, 'max_numero' => 1440],
                ],
            ],
            'ambientes' => [
                'titulo' => 'Ambientes',
                'singular' => 'ambiente',
                'kicker' => 'Catalogos clinicos',
                'descripcion' => 'Administra ambientes fisicos referenciales para la atencion de emergencia.',
                'modelo' => Ambiente::class,
                'tabla' => 'ambientes',
                'orden' => 'denominacion',
                'placeholder' => 'Buscar por denominacion',
                'etiqueta_uso' => 'historias clinicas',
                'relaciones_uso' => ['hcEmergencias'],
                'campos' => [
                    ['nombre' => 'denominacion', 'etiqueta' => 'Denominacion', 'tipo' => 'text', 'requerido' => true, 'max' => 255],
                ],
            ],
        ];
    }

    /**
     * Devuelve la configuracion de un catalogo por su clave de ruta.
     *
     * @return array<string, mixed>
     */
    public static function obtener(string $catalogo): array
    {
        abort_unless(array_key_exists($catalogo, self::todos()), 404);

        return self::todos()[$catalogo];
    }

    /**
     * Lista las claves de los catalogos administrables por el panel generico.
     *
     * @return array<int, string>
     */
    public static function claves(): array
    {
        return array_keys(self::todos());
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
