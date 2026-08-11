<?php

namespace App\Services\Catalogos;

use App\Enums\EstadoCatalogo;
use App\Models\User;
use App\Support\Catalogos\CatalogosClinicos;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

class CatalogosClinicosArchivoService
{
    /**
     * Columnas oficiales que se usan para importar el catalogo seleccionado.
     *
     * @return array<int, string>
     */
    public function columnasImportacion(string $catalogo): array
    {
        return collect($this->configuracion($catalogo)['campos'])
            ->pluck('nombre')
            ->push('estado')
            ->values()
            ->all();
    }

    /**
     * Genera un archivo XLSX con los registros filtrados del catalogo.
     */
    public function generarExcel(string $catalogo, ?string $busqueda = null, ?string $estado = null): string
    {
        $configuracion = $this->configuracion($catalogo);
        $rutaArchivo = tempnam(sys_get_temp_dir(), $catalogo.'_').'.xlsx';
        $writer = new Writer;

        $writer->openToFile($rutaArchivo);
        $writer->addRow(Row::fromValues($this->encabezadosExportacion($configuracion)));

        $this->consulta($catalogo, $busqueda, $estado)
            ->with(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'])
            ->orderBy($configuracion['orden'])
            ->chunk(200, function (Collection $registros) use ($writer, $configuracion): void {
                foreach ($registros as $registro) {
                    $writer->addRow(Row::fromValues($this->filaExportacion($registro, $configuracion)));
                }
            });

        $writer->close();

        return $rutaArchivo;
    }

    /**
     * Genera la plantilla XLSX oficial para importar el catalogo seleccionado.
     */
    public function generarPlantilla(string $catalogo): string
    {
        $rutaArchivo = tempnam(sys_get_temp_dir(), 'plantilla_'.$catalogo.'_').'.xlsx';
        $writer = new Writer;

        $writer->openToFile($rutaArchivo);
        $writer->addRow(Row::fromValues($this->columnasImportacion($catalogo)));
        $writer->addRow(Row::fromValues($this->filaEjemplo($catalogo, 1)));
        $writer->addRow(Row::fromValues($this->filaEjemplo($catalogo, 2)));
        $writer->close();

        return $rutaArchivo;
    }

    /**
     * Importa registros desde un XLSX usando la configuracion funcional del catalogo.
     *
     * @return array{creados: int, actualizados: int, omitidos: int, errores: array<int, string>}
     */
    public function importar(string $catalogo, UploadedFile $archivo, int $usuarioId): array
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
                        $faltantes = array_diff($this->columnasImportacion($catalogo), $encabezados);

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

                    $datos = $this->normalizarDatosImportacion($catalogo, $this->mapearFila($encabezados, $valores));
                    $validacion = Validator::make($datos, $this->reglasImportacion($catalogo), [], $this->atributosImportacion($catalogo));

                    if ($validacion->fails()) {
                        $resultado['errores'][] = 'Fila '.$numeroFila.': '.$validacion->errors()->first();
                        $resultado['omitidos']++;

                        continue;
                    }

                    $registro = $this->registroPorClaveImportacion($catalogo, $datos);
                    $esNuevo = ! $registro->exists;

                    $registro->fill(array_merge(
                        $this->datosPersistibles($catalogo, $datos),
                        [
                            'estado' => $datos['estado'],
                            'registrado_por' => $registro->registrado_por ?: $usuarioId,
                            'actualizado_por' => $usuarioId,
                        ],
                    ));
                    $registro->save();

                    $esNuevo ? $resultado['creados']++ : $resultado['actualizados']++;
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $resultado;
    }

    public function consulta(string $catalogo, ?string $busqueda = null, ?string $estado = null): Builder
    {
        $configuracion = $this->configuracion($catalogo);
        $modelo = $configuracion['modelo'];

        return $modelo::query()
            ->when($estado !== null && $estado !== '' && $estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado', $estado))
            ->when($busqueda !== null && $busqueda !== '', function (Builder $consulta) use ($busqueda, $configuracion): void {
                $consulta->where(function (Builder $grupo) use ($busqueda, $configuracion): void {
                    foreach ($configuracion['campos'] as $campo) {
                        $grupo->orWhere($campo['nombre'], 'like', "%{$busqueda}%");
                    }
                });
            });
    }

    public function nombreArchivo(string $catalogo): string
    {
        return Str::slug($catalogo, '_');
    }

    /**
     * @return array<string, mixed>
     */
    public function configuracion(string $catalogo): array
    {
        return CatalogosClinicos::obtener($catalogo);
    }

    /**
     * @param  array<string, mixed>  $configuracion
     * @return array<int, string>
     */
    private function encabezadosExportacion(array $configuracion): array
    {
        return collect($configuracion['campos'])
            ->pluck('etiqueta')
            ->push('Estado')
            ->push('Registrado por')
            ->push('Creado')
            ->push('Actualizado por')
            ->push('Actualizado')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $configuracion
     * @return array<int, string>
     */
    private function filaExportacion(mixed $registro, array $configuracion): array
    {
        return collect($configuracion['campos'])
            ->map(fn (array $campo): string => $this->valorExportable(data_get($registro, $campo['nombre'])))
            ->push($this->valorExportable(data_get($registro, 'estado')))
            ->push($this->nombreUsuario($registro->registradoPor))
            ->push(optional($registro->created_at)->format('d/m/Y H:i:s') ?? '')
            ->push($this->nombreUsuario($registro->actualizadoPor))
            ->push(optional($registro->updated_at)->format('d/m/Y H:i:s') ?? '')
            ->values()
            ->all();
    }

    private function valorExportable(mixed $valor): string
    {
        if ($valor instanceof BackedEnum) {
            return $valor->value;
        }

        return filled($valor) ? (string) $valor : '';
    }

    private function nombreUsuario(?User $usuario): string
    {
        if ($usuario === null) {
            return 'No registrado';
        }

        return $usuario->name.' <'.$usuario->email.'>';
    }

    /**
     * @return array<int, string>
     */
    private function filaEjemplo(string $catalogo, int $numero): array
    {
        $valores = collect($this->configuracion($catalogo)['campos'])
            ->map(fn (array $campo): string|int => $this->valorEjemplo($campo, $numero))
            ->push($numero === 1 ? EstadoCatalogo::Activo->value : EstadoCatalogo::Inactivo->value)
            ->values()
            ->all();

        return $valores;
    }

    private function valorEjemplo(array $campo, int $numero): string|int
    {
        if (($campo['tipo'] ?? 'text') === 'number') {
            return $numero;
        }

        if (($campo['tipo'] ?? 'text') === 'select') {
            return $campo['opciones'][0] ?? '';
        }

        return match ($campo['nombre']) {
            'codigo' => 'CAT-'.$numero,
            'nombre' => 'Nombre ejemplo '.$numero,
            'descripcion' => 'Descripcion ejemplo '.$numero,
            'denominacion' => 'Denominacion ejemplo '.$numero,
            'prioridad' => 'Prioridad ejemplo '.$numero,
            default => $campo['etiqueta'].' ejemplo '.$numero,
        };
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

        return array_combine($encabezados, $valoresAjustados);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizarDatosImportacion(string $catalogo, array $datos): array
    {
        foreach ($this->configuracion($catalogo)['campos'] as $campo) {
            $nombre = $campo['nombre'];
            $valor = $datos[$nombre] ?? '';

            if (($campo['tipo'] ?? 'text') === 'select') {
                $valor = $this->normalizarOpcion($valor, $campo['opciones']);
            }

            if (($campo['tipo'] ?? 'text') === 'number') {
                $valor = $valor === '' ? null : (int) $valor;
            }

            if (! ($campo['requerido'] ?? false) && $valor === '') {
                $valor = null;
            }

            $datos[$nombre] = $valor;
        }

        $datos['estado'] = $this->normalizarOpcion($datos['estado'] ?? '', EstadoCatalogo::cases());

        return $datos;
    }

    /**
     * @param  array<int, string|BackedEnum>  $opciones
     */
    private function normalizarOpcion(string $valor, array $opciones): string
    {
        $valorNormalizado = Str::of($valor)->ascii()->lower()->trim()->toString();

        foreach ($opciones as $opcion) {
            $valorOpcion = $opcion instanceof BackedEnum ? $opcion->value : $opcion;
            $opcionNormalizada = Str::of($valorOpcion)->ascii()->lower()->trim()->toString();

            if ($valorNormalizado === $opcionNormalizada) {
                return $valorOpcion;
            }
        }

        return $valor;
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasImportacion(string $catalogo): array
    {
        $reglas = [];

        foreach ($this->configuracion($catalogo)['campos'] as $campo) {
            $reglaCampo = [($campo['requerido'] ?? false) ? 'required' : 'nullable'];

            if (($campo['tipo'] ?? 'text') === 'number') {
                $reglaCampo[] = 'integer';
                $reglaCampo[] = 'min:0';
            } else {
                $reglaCampo[] = 'string';
                $reglaCampo[] = 'max:'.($campo['max'] ?? 255);
            }

            if (($campo['tipo'] ?? 'text') === 'select') {
                $reglaCampo[] = Rule::in($campo['opciones']);
            }

            $reglas[$campo['nombre']] = $reglaCampo;
        }

        $reglas['estado'] = ['required', Rule::enum(EstadoCatalogo::class)];

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    private function atributosImportacion(string $catalogo): array
    {
        return collect($this->configuracion($catalogo)['campos'])
            ->mapWithKeys(fn (array $campo): array => [$campo['nombre'] => mb_strtolower($campo['etiqueta'])])
            ->merge(['estado' => 'estado'])
            ->all();
    }

    private function registroPorClaveImportacion(string $catalogo, array $datos): mixed
    {
        $configuracion = $this->configuracion($catalogo);
        $modelo = $configuracion['modelo'];
        $criterios = $this->criteriosClaveImportacion($configuracion, $datos);

        return $modelo::query()->firstOrNew($criterios);
    }

    /**
     * @param  array<string, mixed>  $configuracion
     * @return array<string, mixed>
     */
    private function criteriosClaveImportacion(array $configuracion, array $datos): array
    {
        $campoUnico = collect($configuracion['campos'])->first(fn (array $campo): bool => (bool) ($campo['unico'] ?? false));

        if ($campoUnico !== null) {
            return [$campoUnico['nombre'] => $datos[$campoUnico['nombre']]];
        }

        $campoUnicoCompuesto = collect($configuracion['campos'])->first(fn (array $campo): bool => filled($campo['unico_compuesto'] ?? []));

        if ($campoUnicoCompuesto !== null) {
            return collect($campoUnicoCompuesto['unico_compuesto'])
                ->mapWithKeys(fn (string $campo): array => [$campo => $datos[$campo] ?? null])
                ->all();
        }

        $campoPrincipal = $configuracion['campos'][0]['nombre'];

        return [$campoPrincipal => $datos[$campoPrincipal]];
    }

    /**
     * @return array<string, mixed>
     */
    private function datosPersistibles(string $catalogo, array $datos): array
    {
        return collect($this->configuracion($catalogo)['campos'])
            ->mapWithKeys(fn (array $campo): array => [$campo['nombre'] => $datos[$campo['nombre']] ?? null])
            ->all();
    }
}
