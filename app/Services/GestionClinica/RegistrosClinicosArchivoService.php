<?php

namespace App\Services\GestionClinica;

use App\Enums\EstadoCatalogo;
use App\Models\User;
use App\Support\GestionClinica\RegistrosClinicos;
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

class RegistrosClinicosArchivoService
{
    /**
     * Columnas oficiales que se usan para importar la vista clinica seleccionada.
     *
     * @return array<int, string>
     */
    public function columnasImportacion(string $registroClinico): array
    {
        $columnas = collect($this->configuracion($registroClinico)['campos'])->pluck('nombre');

        if ($this->tieneEstado($registroClinico)) {
            $columnas->push('estado');
        }

        return $columnas->values()->all();
    }

    /**
     * Genera un archivo XLSX con los registros filtrados.
     */
    public function generarExcel(string $registroClinico, ?string $busqueda = null, ?string $estado = null): string
    {
        $configuracion = $this->configuracion($registroClinico);
        $rutaArchivo = tempnam(sys_get_temp_dir(), $registroClinico.'_').'.xlsx';
        $writer = new Writer;

        $writer->openToFile($rutaArchivo);
        $writer->addRow(Row::fromValues($this->encabezadosExportacion($registroClinico)));

        $this->consulta($registroClinico, $busqueda, $estado)
            ->with(array_merge(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'], $this->relacionesSelect($registroClinico)))
            ->orderBy($configuracion['orden'])
            ->chunk(200, function (Collection $registros) use ($writer, $registroClinico): void {
                foreach ($registros as $registro) {
                    $writer->addRow(Row::fromValues($this->filaExportacion($registroClinico, $registro)));
                }
            });

        $writer->close();

        return $rutaArchivo;
    }

    /**
     * Genera una plantilla XLSX oficial para importacion.
     */
    public function generarPlantilla(string $registroClinico): string
    {
        $rutaArchivo = tempnam(sys_get_temp_dir(), 'plantilla_'.$registroClinico.'_').'.xlsx';
        $writer = new Writer;

        $writer->openToFile($rutaArchivo);
        $writer->addRow(Row::fromValues($this->columnasImportacion($registroClinico)));
        $writer->addRow(Row::fromValues($this->filaEjemplo($registroClinico, 1)));
        $writer->addRow(Row::fromValues($this->filaEjemplo($registroClinico, 2)));
        $writer->close();

        return $rutaArchivo;
    }

    /**
     * Importa registros desde un XLSX.
     *
     * @return array{creados: int, actualizados: int, omitidos: int, errores: array<int, string>}
     */
    public function importar(string $registroClinico, UploadedFile $archivo, int $usuarioId): array
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
                    $valores = array_map(fn ($valor): string => trim((string) $valor), $row->toArray());

                    if ($numeroFila === 1) {
                        $encabezados = $this->normalizarEncabezados($valores);
                        $faltantes = array_diff($this->columnasImportacion($registroClinico), $encabezados);

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

                    $datos = $this->normalizarDatosImportacion($registroClinico, $this->mapearFila($encabezados, $valores));
                    $validacion = Validator::make($datos, $this->reglasImportacion($registroClinico), [], $this->atributosImportacion($registroClinico));

                    if ($validacion->fails()) {
                        $resultado['errores'][] = 'Fila '.$numeroFila.': '.$validacion->errors()->first();
                        $resultado['omitidos']++;

                        continue;
                    }

                    $registro = $this->registroPorClaveImportacion($registroClinico, $datos);
                    $esNuevo = ! $registro->exists;
                    $datosPersistibles = array_merge(
                        $this->datosPersistibles($registroClinico, $datos),
                        [
                            'registrado_por' => $registro->registrado_por ?: $usuarioId,
                            'actualizado_por' => $usuarioId,
                        ],
                    );

                    if ($this->tieneEstado($registroClinico)) {
                        $datosPersistibles['estado'] = $datos['estado'];
                    }

                    $registro->fill($datosPersistibles);
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

    public function consulta(string $registroClinico, ?string $busqueda = null, ?string $estado = null): Builder
    {
        $configuracion = $this->configuracion($registroClinico);
        $modelo = $configuracion['modelo'];

        return $modelo::query()
            ->when($this->tieneEstado($registroClinico) && $estado !== null && $estado !== '' && $estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado', $estado))
            ->when($busqueda !== null && $busqueda !== '', function (Builder $consulta) use ($busqueda, $configuracion): void {
                $consulta->where(function (Builder $grupo) use ($busqueda, $configuracion): void {
                    foreach ($configuracion['campos'] as $campo) {
                        if (($campo['tipo'] ?? 'text') === 'model_select') {
                            $grupo->orWhereHas($campo['relacion'], function (Builder $relacion) use ($busqueda, $campo): void {
                                $relacion->where($campo['relacion_campo'], 'like', "%{$busqueda}%");
                            });

                            continue;
                        }

                        $grupo->orWhere($campo['nombre'], 'like', "%{$busqueda}%");
                    }
                });
            });
    }

    public function nombreArchivo(string $registroClinico): string
    {
        return Str::slug($registroClinico, '_');
    }

    /**
     * @return array<string, mixed>
     */
    public function configuracion(string $registroClinico): array
    {
        return RegistrosClinicos::obtener($registroClinico);
    }

    public function tieneEstado(string $registroClinico): bool
    {
        return (bool) ($this->configuracion($registroClinico)['tiene_estado'] ?? false);
    }

    /**
     * @return array<int, string>
     */
    private function encabezadosExportacion(string $registroClinico): array
    {
        $encabezados = collect($this->configuracion($registroClinico)['campos'])->pluck('etiqueta');

        if ($this->tieneEstado($registroClinico)) {
            $encabezados->push('Estado');
        }

        return $encabezados
            ->push('Registrado por')
            ->push('Creado')
            ->push('Actualizado por')
            ->push('Actualizado')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function filaExportacion(string $registroClinico, mixed $registro): array
    {
        $fila = collect($this->configuracion($registroClinico)['campos'])
            ->map(fn (array $campo): string => $this->valorExportable($this->valorCampoExportable($registro, $campo)));

        if ($this->tieneEstado($registroClinico)) {
            $fila->push($this->valorExportable(data_get($registro, 'estado')));
        }

        return $fila
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
     * @return array<int, string|int>
     */
    private function filaEjemplo(string $registroClinico, int $numero): array
    {
        $fila = collect($this->configuracion($registroClinico)['campos'])
            ->map(fn (array $campo): string|int => $this->valorEjemplo($campo, $registroClinico, $numero));

        if ($this->tieneEstado($registroClinico)) {
            $fila->push($numero === 1 ? EstadoCatalogo::Activo->value : EstadoCatalogo::Inactivo->value);
        }

        return $fila->values()->all();
    }

    private function valorEjemplo(array $campo, string $registroClinico, int $numero): string|int
    {
        if (($campo['tipo'] ?? 'text') === 'model_select') {
            $modelo = $campo['modelo_opciones'];
            $valor = $modelo::query()->orderBy($campo['opcion_texto'])->value($campo['opcion_valor']);

            return $valor === null ? '' : (int) $valor;
        }

        if (($campo['tipo'] ?? 'text') === 'number') {
            return $numero;
        }

        if (($campo['tipo'] ?? 'text') === 'date') {
            return now()->subYears(20 + $numero)->format('Y-m-d');
        }

        if (($campo['tipo'] ?? 'text') === 'select') {
            return $campo['opciones'][0] ?? '';
        }

        return match ($campo['nombre']) {
            'numero_historia' => 'HC-2026-000'.$numero,
            'nombre' => 'Paciente ejemplo '.$numero,
            'nombres' => 'Profesional ejemplo '.$numero,
            'telefono' => '99900000'.$numero,
            'numero_documento' => '7000000'.$numero,
            'cmp' => '90000'.$numero,
            'rne' => '80000'.$numero,
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
    private function normalizarDatosImportacion(string $registroClinico, array $datos): array
    {
        foreach ($this->configuracion($registroClinico)['campos'] as $campo) {
            $nombre = $campo['nombre'];
            $valor = $datos[$nombre] ?? '';

            if (($campo['tipo'] ?? 'text') === 'select') {
                $valor = $this->normalizarOpcion($valor, $campo['opciones']);
            }

            if (($campo['tipo'] ?? 'text') === 'number' || ($campo['tipo'] ?? 'text') === 'model_select') {
                $valor = $valor === '' ? null : (int) $valor;
            }

            if (! ($campo['requerido'] ?? false) && $valor === '') {
                $valor = null;
            }

            $datos[$nombre] = $valor;
        }

        if ($this->tieneEstado($registroClinico)) {
            $datos['estado'] = $this->normalizarOpcion($datos['estado'] ?? '', EstadoCatalogo::cases());
        }

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
    private function reglasImportacion(string $registroClinico): array
    {
        $reglas = $this->reglasCampos($registroClinico);

        if ($this->tieneEstado($registroClinico)) {
            $reglas['estado'] = ['required', Rule::enum(EstadoCatalogo::class)];
        }

        return $reglas;
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasCampos(string $registroClinico): array
    {
        return collect($this->configuracion($registroClinico)['campos'])
            ->mapWithKeys(function (array $campo): array {
                $reglaCampo = [($campo['requerido'] ?? false) ? 'required' : 'nullable'];

                if (($campo['tipo'] ?? 'text') === 'number' || ($campo['tipo'] ?? 'text') === 'model_select') {
                    $reglaCampo[] = 'integer';
                    $reglaCampo[] = 'min:0';
                } elseif (($campo['tipo'] ?? 'text') === 'date') {
                    $reglaCampo[] = 'date';
                } else {
                    $reglaCampo[] = 'string';
                    $reglaCampo[] = 'max:'.($campo['max'] ?? 255);
                }

                if (($campo['tipo'] ?? 'text') === 'select') {
                    $reglaCampo[] = Rule::in($campo['opciones']);
                }

                if (($campo['tipo'] ?? 'text') === 'model_select') {
                    $modeloOpciones = $campo['modelo_opciones'];
                    $reglaCampo[] = Rule::exists((new $modeloOpciones)->getTable(), $campo['opcion_valor']);
                }

                return [$campo['nombre'] => $reglaCampo];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function atributosImportacion(string $registroClinico): array
    {
        return collect($this->configuracion($registroClinico)['campos'])
            ->mapWithKeys(fn (array $campo): array => [$campo['nombre'] => mb_strtolower($campo['etiqueta'])])
            ->merge(['estado' => 'estado'])
            ->all();
    }

    private function registroPorClaveImportacion(string $registroClinico, array $datos): mixed
    {
        $configuracion = $this->configuracion($registroClinico);
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
        $campoUnico = collect($configuracion['campos'])->first(fn (array $campo): bool => (bool) ($campo['unico'] ?? false) && filled($datos[$campo['nombre']] ?? null));

        if ($campoUnico !== null) {
            return [$campoUnico['nombre'] => $datos[$campoUnico['nombre']]];
        }

        $campoUnicoCompuesto = collect($configuracion['campos'])->first(fn (array $campo): bool => filled($campo['unico_compuesto'] ?? []));

        if ($campoUnicoCompuesto !== null && collect($campoUnicoCompuesto['unico_compuesto'])->every(fn (string $campo): bool => filled($datos[$campo] ?? null))) {
            return collect($campoUnicoCompuesto['unico_compuesto'])
                ->mapWithKeys(fn (string $campo): array => [$campo => $datos[$campo] ?? null])
                ->all();
        }

        $campoPrincipal = collect($configuracion['campos'])->first(fn (array $campo): bool => ($campo['requerido'] ?? false))['nombre']
            ?? $configuracion['campos'][0]['nombre'];

        return [$campoPrincipal => $datos[$campoPrincipal]];
    }

    /**
     * @return array<string, mixed>
     */
    private function datosPersistibles(string $registroClinico, array $datos): array
    {
        return collect($this->configuracion($registroClinico)['campos'])
            ->mapWithKeys(fn (array $campo): array => [$campo['nombre'] => $datos[$campo['nombre']] ?? null])
            ->all();
    }

    private function valorCampoExportable(mixed $registro, array $campo): mixed
    {
        if (($campo['tipo'] ?? 'text') === 'model_select') {
            return data_get($registro, $campo['relacion'].'.'.$campo['relacion_campo']);
        }

        return data_get($registro, $campo['nombre']);
    }

    /**
     * @return array<int, string>
     */
    private function relacionesSelect(string $registroClinico): array
    {
        return collect($this->configuracion($registroClinico)['campos'])
            ->filter(fn (array $campo): bool => ($campo['tipo'] ?? 'text') === 'model_select')
            ->pluck('relacion')
            ->values()
            ->all();
    }
}
