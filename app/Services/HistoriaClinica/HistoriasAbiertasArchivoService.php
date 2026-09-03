<?php

namespace App\Services\HistoriaClinica;

use App\Enums\EstadoAtencion;
use App\Enums\TipoDiagnostico;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class HistoriasAbiertasArchivoService
{
    public function generarExcel(?string $desde, ?string $hasta, ?string $estado): string
    {
        $rutaArchivo = tempnam(sys_get_temp_dir(), 'historias_clinicas_').'.xlsx';
        $writer = new Writer;

        $writer->openToFile($rutaArchivo);
        $writer->addRow(Row::fromValues($this->encabezados()));

        $this->consulta($desde, $hasta, $estado)
            ->with([
                'paciente',
                'profesionalResponsable',
                'prioridad',
                'diagnosticosIngreso.diagnosticoCie10',
            ])
            ->orderByDesc('fecha_hora_atencion')
            ->orderByDesc('id')
            ->chunk(200, function (Collection $atenciones) use ($writer): void {
                foreach ($atenciones as $atencion) {
                    $writer->addRow(Row::fromValues($this->fila($atencion)));
                }
            });

        $writer->close();

        return $rutaArchivo;
    }

    public function consulta(?string $desde, ?string $hasta, ?string $estado): Builder
    {
        return HcEmergencia::query()
            ->when(filled($desde), fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '>=', $desde))
            ->when(filled($hasta), fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '<=', $hasta))
            ->when(filled($estado) && $estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado_atencion', $estado));
    }

    public function estadisticas(?string $desde, ?string $hasta): array
    {
        $base = HcEmergencia::query()
            ->when(filled($desde), fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '>=', $desde))
            ->when(filled($hasta), fn (Builder $consulta): Builder => $consulta->whereDate('fecha_hora_atencion', '<=', $hasta));

        return [
            'total' => (clone $base)->count(),
            'abiertas' => (clone $base)->where('estado_atencion', EstadoAtencion::Abierta->value)->count(),
            'cerradas' => (clone $base)->where('estado_atencion', EstadoAtencion::Cerrada->value)->count(),
            'triaje' => (clone $base)->where('triaje', true)->count(),
        ];
    }

    public function filtrosNormalizados(?string $desde, ?string $hasta, ?string $estado): array
    {
        $hoy = now()->toDateString();

        return [
            'desde' => $desde ?: $hoy,
            'hasta' => $hasta ?: $hoy,
            'estado' => $estado ?: EstadoAtencion::Abierta->value,
        ];
    }

    public function nombreArchivo(array $filtros): string
    {
        return 'historias_clinicas_'.$filtros['desde'].'_'.$filtros['hasta'].'_'.strtolower((string) $filtros['estado']);
    }

    public function diagnosticoPrincipal(HcEmergencia $atencion): ?IngresoDiagnostico
    {
        $diagnosticos = $atencion->diagnosticosIngreso;

        return $diagnosticos
            ->filter(fn (IngresoDiagnostico $diagnostico): bool => $diagnostico->tipo_diagnostico === TipoDiagnostico::Definitivo)
            ->sortByDesc(fn (IngresoDiagnostico $diagnostico): int => $diagnostico->fecha_hora?->timestamp ?? 0)
            ->first()
            ?: $diagnosticos
                ->sortByDesc(fn (IngresoDiagnostico $diagnostico): int => $diagnostico->fecha_hora?->timestamp ?? 0)
                ->first();
    }

    private function encabezados(): array
    {
        return [
            'Fecha y hora ingreso',
            'Nombre del paciente',
            'Tipo',
            'ID HCE',
            'Numero atencion',
            'Profesional',
            'Ambiente',
            'DX Principal',
            'Prioridad',
            'Triaje',
            'Estado',
        ];
    }

    private function fila(HcEmergencia $atencion): array
    {
        $diagnostico = $this->diagnosticoPrincipal($atencion);

        return [
            $atencion->fecha_hora_atencion?->format('d/m/Y H:i') ?? '',
            $atencion->paciente?->nombre ?? $atencion->alias ?? '',
            'Emergencia',
            $atencion->id,
            $atencion->numero_atencion ?? '',
            $atencion->profesionalResponsable?->nombres ?? '',
            $atencion->destino_topico ?: $atencion->ambiente ?: '',
            $diagnostico?->diagnosticoCie10 ? trim($diagnostico->diagnosticoCie10->nombre.' ('.$diagnostico->diagnosticoCie10->codigo.')') : '',
            $atencion->prioridad?->orden ? 'Nivel '.$atencion->prioridad->orden : '',
            $atencion->triaje ? 'Realizado' : 'Pendiente',
            $this->valorEnum($atencion->estado_atencion),
        ];
    }

    private function valorEnum(mixed $valor): string
    {
        return $valor instanceof BackedEnum ? $valor->value : (string) ($valor ?? '');
    }
}
