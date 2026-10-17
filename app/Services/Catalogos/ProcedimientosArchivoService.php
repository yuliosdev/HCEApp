<?php

namespace App\Services\Catalogos;

use App\Enums\EstadoCatalogo;
use App\Models\Procedimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Closure;

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
    public function importar(UploadedFile $archivo, int $usuarioId, ?Closure $progreso = null): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');

        $resultado = [
            'creados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => [],
        ];
        $loteFilas = [];
        $codigosContados = [];
        $guardadas = 0;

        $reader = new Reader;
        $reader->open($archivo->getRealPath());
        $filasLeidas = 0;
        $guardarLote = function () use (&$loteFilas, &$codigosContados, &$guardadas, &$resultado, $usuarioId, $progreso, &$filasLeidas): void {
            if ($loteFilas === []) {
                return;
            }

            $codigos = array_keys($loteFilas);
            $registrosExistentes = Procedimiento::query()
                ->withTrashed()
                ->whereIn('codigo', $codigos)
                ->get(['id', 'codigo', 'registrado_por'])
                ->keyBy('codigo');

            $ahora = now();
            $filasNuevas = [];
            $filasExistentes = [];

            foreach ($loteFilas as $codigo => $datos) {
                $registroExistente = $registrosExistentes->get($codigo);

                if (! isset($codigosContados[$codigo])) {
                    $registroExistente === null
                        ? $resultado['creados']++
                        : $resultado['actualizados']++;

                    $codigosContados[$codigo] = true;
                }

                $fila = [
                    'codigo' => $codigo,
                    'denominacion' => $datos['denominacion'],
                    'estado' => $datos['estado'],
                    'actualizado_por' => $usuarioId,
                    'updated_at' => $ahora,
                    'deleted_at' => null,
                ];

                if ($registroExistente === null) {
                    $filasNuevas[] = array_merge($fila, [
                        'registrado_por' => $usuarioId,
                        'created_at' => $ahora,
                    ]);

                    continue;
                }

                $filasExistentes[] = array_merge($fila, [
                    'id' => $registroExistente->id,
                ]);
            }

            if ($filasNuevas !== []) {
                DB::table('procedimientos')->insert($filasNuevas);
            }

            if ($filasExistentes !== []) {
                DB::table('procedimientos')->upsert(
                    $filasExistentes,
                    ['id'],
                    ['codigo', 'denominacion', 'estado', 'actualizado_por', 'updated_at', 'deleted_at'],
                );
            }

            $guardadas += count($filasNuevas) + count($filasExistentes);
            $loteFilas = [];

            $progreso?->__invoke([
                'mensaje' => 'Guardando procedimientos...',
                'procesadas' => $filasLeidas,
                'guardadas' => $guardadas,
                'omitidos' => $resultado['omitidos'],
            ]);
        };

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

                    $filasLeidas++;
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

                    // El codigo es la clave unica: ante duplicados prevalece la ultima fila.
                    $loteFilas[$datos['codigo']] = $datos;

                    if (count($loteFilas) >= 500) {
                        $guardarLote();
                    }

                    if ($progreso !== null && ($filasLeidas % 250 === 0)) {
                        $progreso([
                            'mensaje' => 'Validando filas del archivo...',
                            'procesadas' => $filasLeidas,
                            'omitidos' => $resultado['omitidos'],
                        ]);
                    }
                }

                break;
            }

            $guardarLote();
        } finally {
            $reader->close();
        }

        if ($guardadas === 0) {
            $progreso?->__invoke([
                'mensaje' => 'No se encontraron filas válidas para importar.',
                'procesadas' => $filasLeidas,
                'omitidos' => $resultado['omitidos'],
            ]);
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
