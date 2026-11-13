<?php

namespace App\Services\Interoperabilidad;

use Carbon\CarbonImmutable;

class AtencionesAmbulatoriasHomologador
{
    /**
     * @param  array<int, array<string, mixed>>  $atenciones
     * @return list<array<string, mixed>>
     */
    public static function homologar(array $atenciones): array
    {
        $porAdmision = [];
        $sinAdmision = [];

        foreach ($atenciones as $atencion) {
            if (! is_array($atencion)) {
                continue;
            }

            $admision = $atencion['numero_admision'] ?? null;

            if ($admision === null || $admision === '') {
                $sinAdmision[] = $atencion;

                continue;
            }

            if (! isset($porAdmision[$admision]) || self::puntaje($atencion) > self::puntaje($porAdmision[$admision])) {
                $porAdmision[$admision] = $atencion;
            }
        }

        $filas = array_map(self::fila(...), [...array_values($porAdmision), ...$sinAdmision]);

        usort($filas, fn (array $a, array $b): int => strcmp((string) ($b['fecha_iso'] ?? ''), (string) ($a['fecha_iso'] ?? '')));

        return $filas;
    }

    /**
     * @return array<int, int>
     */
    private static function puntaje(array $atencion): array
    {
        $motivo = trim((string) ($atencion['motivo_atencion'] ?? ''));
        $relato = trim((string) ($atencion['relato_atencion'] ?? ''));
        $diagnosticos = count(array_filter($atencion['diagnosticos'] ?? [], 'is_array'));

        return [
            (int) ($motivo !== '') + (int) ($relato !== '') + (int) ($diagnosticos > 0),
            $diagnosticos,
            mb_strlen($motivo) + mb_strlen($relato),
            (int) ($atencion['firma_digital'] ?? false),
            (int) ($atencion['id_hc'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fila(array $atencion): array
    {
        $diagnostico = collect($atencion['diagnosticos'] ?? [])
            ->filter(fn (mixed $diagnostico): bool => is_array($diagnostico))
            ->first();

        $fecha = $atencion['fecha_hora_atencion'] ?? null;

        return [
            'origen' => 'ambulatoria',
            'id' => (int) ($atencion['id_hc'] ?? 0),
            'fecha_iso' => $fecha,
            'fecha' => self::fechaLegible($fecha),
            'tipo' => self::texto($atencion['tipo_atencion'] ?? null),
            'numero_atencion' => $atencion['numero_admision'] ?? null,
            'profesional' => $atencion['medico']['nombre'] ?? null,
            'diagnostico' => self::diagnostico($diagnostico),
            'estado' => (bool) ($atencion['firma_digital'] ?? false) ? 'Cerrada' : 'Abierta',
        ];
    }

    private static function diagnostico(?array $diagnostico): ?string
    {
        if ($diagnostico === null) {
            return null;
        }

        $codigo = trim((string) ($diagnostico['codigo_cie'] ?? ''));
        $nombre = trim((string) ($diagnostico['nombre'] ?? ''));

        return trim($codigo.' '.$nombre) ?: null;
    }

    private static function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }

    private static function fechaLegible(?string $fecha): string
    {
        if ($fecha === null || trim($fecha) === '') {
            return '--';
        }

        try {
            return CarbonImmutable::parse($fecha)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return '--';
        }
    }
}
