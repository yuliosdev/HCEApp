<?php

namespace App\Services\Catalogos;

use App\Enums\EstadoCatalogo;
use App\Models\Procedimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

class ProcedimientosArchivoService
{
    /**
     * Columnas oficiales que se usan para exportar e importar procedimientos.
     *
     * @return array<int, string>
     */
    public function columnasImportacion(): array
    {
        return ['codigo', 'denominacion', 'estado'];
    }

    /**
     * Genera un archivo XLSX con los registros del catalogo.
     */
    public function generarExcel(?string $busqueda = null, ?string $estado = null): string
    {
        $rutaArchivo = tempnam(sys_get_temp_dir(), 'procedimientos_').'.xlsx';
        $writer = new Writer;

        $writer->openToFile($rutaArchivo);
        $writer->addRow(Row::fromValues([
            'Codigo',
            'Denominacion',
            'Estado',
            'Registrado por',
            'Creado',
            'Actualizado por',
            'Actualizado',
        ]));

        $this->consulta($busqueda, $estado)
            ->with(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'])
            ->orderBy('codigo')
            ->chunk(200, function (Collection $procedimientos) use ($writer): void {
                foreach ($procedimientos as $procedimiento) {
                    $writer->addRow(Row::fromValues([
                        $procedimiento->codigo,
                        $procedimiento->denominacion,
                        $procedimiento->estado,
                        $this->nombreUsuario($procedimiento->registradoPor),
                        optional($procedimiento->created_at)->format('d/m/Y H:i:s'),
                        $this->nombreUsuario($procedimiento->actualizadoPor),
                        optional($procedimiento->updated_at)->format('d/m/Y H:i:s'),
                    ]));
                }
            });

        $writer->close();

        return $rutaArchivo;
    }

    /**
     * Genera la plantilla XLSX oficial para importar procedimientos.
     */
    public function generarPlantilla(): string
    {
        $rutaArchivo = tempnam(sys_get_temp_dir(), 'plantilla_procedimientos_').'.xlsx';
        $writer = new Writer;

        $writer->openToFile($rutaArchivo);
        $writer->addRow(Row::fromValues($this->columnasImportacion()));
        $writer->addRow(Row::fromValues([
            'PROC-001',
            'Curacion de herida simple',
            EstadoCatalogo::Activo->value,
        ]));
        $writer->addRow(Row::fromValues([
            'PROC-002',
            'Sutura de herida',
            EstadoCatalogo::Inactivo->value,
        ]));
        $writer->close();

        return $rutaArchivo;
    }

    /**
     * Importa procedimientos desde un XLSX.
     *
     * @return array{creados: int, actualizados: int, omitidos: int, errores: array<int, string>}
     */
    public function importar(UploadedFile $archivo, int $usuarioId): array
    {
        $resultado = [
            'creados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => [],
        ];

        $reader = new Reader;
        $reader->open($archivo->getRealPath());

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $encabezados = [];

                foreach ($sheet->getRowIterator() as $numeroFila => $row) {
                    $valores = array_map(
                        fn ($valor): string => trim((string) $valor),
                        $row->toArray()
                    );

                    if ($numeroFila === 1) {
                        $encabezados = $this->normalizarEncabezados($valores);
                        $faltantes = array_diff($this->columnasImportacion(), $encabezados);

                        if ($faltantes !== []) {
                            $resultado['errores'][] = 'La plantilla no contiene las columnas obligatorias: '.implode(', ', $faltantes).'.';

                            return $resultado;
                        }

                        continue;
                    }

                    if ($this->filaVacia($valores)) {
                        $resultado['omitidos']++;

                        continue;
                    }

                    $datos = $this->mapearFila($encabezados, $valores);
                    $datos['estado'] = $this->normalizarEstado($datos['estado']);
                    $validacion = Validator::make($datos, [
                        'codigo' => ['required', 'string', 'max:30'],
                        'denominacion' => ['required', 'string', 'max:255'],
                        'estado' => ['required', Rule::enum(EstadoCatalogo::class)],
                    ], [], [
                        'codigo' => 'codigo',
                        'denominacion' => 'denominacion',
                        'estado' => 'estado',
                    ]);

                    if ($validacion->fails()) {
                        $resultado['errores'][] = 'Fila '.$numeroFila.': '.$validacion->errors()->first();
                        $resultado['omitidos']++;

                        continue;
                    }

                    $procedimiento = Procedimiento::query()->firstOrNew(['codigo' => $datos['codigo']]);
                    $esNuevo = ! $procedimiento->exists;

                    $procedimiento->fill([
                        'denominacion' => $datos['denominacion'],
                        'estado' => $datos['estado'],
                        'registrado_por' => $procedimiento->registrado_por ?: $usuarioId,
                        'actualizado_por' => $usuarioId,
                    ]);
                    $procedimiento->save();

                    $esNuevo ? $resultado['creados']++ : $resultado['actualizados']++;
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $resultado;
    }

    public function consulta(?string $busqueda = null, ?string $estado = null): Builder
    {
        return Procedimiento::query()
            ->when($estado !== null && $estado !== '' && $estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado', $estado))
            ->when($busqueda !== null && $busqueda !== '', function (Builder $consulta) use ($busqueda): void {
                $consulta->where(function (Builder $grupo) use ($busqueda): void {
                    $grupo
                        ->where('codigo', 'like', "%{$busqueda}%")
                        ->orWhere('denominacion', 'like', "%{$busqueda}%");
                });
            });
    }

    private function nombreUsuario(?User $usuario): string
    {
        if ($usuario === null) {
            return 'No registrado';
        }

        return $usuario->name.' <'.$usuario->email.'>';
    }

    /**
     * @param  array<int, string>  $encabezados
     * @return array<int, string>
     */
    private function normalizarEncabezados(array $encabezados): array
    {
        return array_map(fn (string $encabezado): string => Str::of($encabezado)
            ->ascii()
            ->lower()
            ->replace([' ', '-'], '_')
            ->trim()
            ->toString(), $encabezados);
    }

    /**
     * @param  array<int, string>  $valores
     */
    private function filaVacia(array $valores): bool
    {
        return collect($valores)->every(fn (string $valor): bool => $valor === '');
    }

    /**
     * @param  array<int, string>  $encabezados
     * @param  array<int, string>  $valores
     * @return array<string, string>
     */
    private function mapearFila(array $encabezados, array $valores): array
    {
        $valoresAjustados = array_slice(array_pad($valores, count($encabezados), ''), 0, count($encabezados));
        $datos = array_combine($encabezados, $valoresAjustados);

        return [
            'codigo' => $datos['codigo'] ?? '',
            'denominacion' => $datos['denominacion'] ?? '',
            'estado' => $datos['estado'] ?? '',
        ];
    }

    private function normalizarEstado(string $estado): string
    {
        $estadoNormalizado = Str::of($estado)->ascii()->lower()->trim()->toString();

        return match ($estadoNormalizado) {
            'activo' => EstadoCatalogo::Activo->value,
            'inactivo' => EstadoCatalogo::Inactivo->value,
            default => $estado,
        };
    }
}
