<?php

namespace App\Services\Catalogos;

use App\Enums\EstadoCatalogo;
use App\Models\DiagnosticoCie10;
use App\Models\Medicamento;
use App\Models\User;
use App\Support\Catalogos\CatalogosClinicos;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Closure;

class CatalogosClinicosArchivoService
{
    /**
     * Columnas oficiales que se usan para importar el catalogo seleccionado.
     *
     * @return array<int, string>
     */
    public function columnasImportacion(string $catalogo): array
    {
        $columnas = collect($this->configuracion($catalogo)['campos'])->pluck('nombre');

        if ($this->usaEstado($catalogo)) {
            $columnas->push('estado');
        }

        return $columnas->values()->all();
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
    public function importar(string $catalogo, UploadedFile $archivo, int $usuarioId, ?Closure $progreso = null): array
    {
        $this->prepararImportacionMasiva();

        if ($catalogo === 'cie10') {
            return $this->importarCie10PorLotes($archivo, $usuarioId, $progreso);
        }

        if ($catalogo === 'medicamentos') {
            return $this->importarMedicamentosPorLotes($archivo, $usuarioId, $progreso);
        }

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

                    if (! $esNuevo && method_exists($registro, 'trashed') && $registro->trashed()) {
                        $registro->restore();
                    }

                    $datosRegistro = array_merge($this->datosPersistibles($catalogo, $datos), [
                        'registrado_por' => $registro->registrado_por ?: $usuarioId,
                        'actualizado_por' => $usuarioId,
                    ]);

                    if ($this->usaEstado($catalogo)) {
                        $datosRegistro['estado'] = $this->valorEstadoPersistible($catalogo, $datos['estado']);
                    }

                    $registro->fill($datosRegistro);
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

    /**
     * Importa CIE10 con upsert por lotes para evitar una consulta y un save por fila.
     *
     * @return array{creados: int, actualizados: int, omitidos: int, errores: array<int, string>}
     */
    private function importarCie10PorLotes(UploadedFile $archivo, int $usuarioId, ?Closure $progreso = null): array
    {
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
            $codigosExistentes = DiagnosticoCie10::query()
                ->withTrashed()
                ->whereIn('codigo', $codigos)
                ->pluck('registrado_por', 'codigo')
                ->all();

            $ahora = now();
            $filasUpsert = [];

            foreach ($loteFilas as $codigo => $datos) {
                if (! isset($codigosContados[$codigo])) {
                    array_key_exists($codigo, $codigosExistentes)
                        ? $resultado['actualizados']++
                        : $resultado['creados']++;

                    $codigosContados[$codigo] = true;
                }

                $filasUpsert[] = [
                    'codigo' => $codigo,
                    'nombre' => $datos['nombre'],
                    'genero' => $datos['genero'],
                    'estado' => $this->valorEstadoPersistible('cie10', $datos['estado']),
                    'registrado_por' => $codigosExistentes[$codigo] ?? $usuarioId,
                    'actualizado_por' => $usuarioId,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                    'deleted_at' => null,
                ];
            }

            DB::table('diagnosticos_cie10')->upsert(
                $filasUpsert,
                ['codigo'],
                ['nombre', 'genero', 'estado', 'actualizado_por', 'updated_at', 'deleted_at'],
            );

            $guardadas += count($filasUpsert);
            $loteFilas = [];

            $progreso?->__invoke([
                'mensaje' => 'Guardando diagnósticos CIE10...',
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
                        $faltantes = array_diff($this->columnasImportacion('cie10'), $encabezados);

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
                    $datos = $this->normalizarDatosImportacion('cie10', $this->mapearFila($encabezados, $valores));
                    $validacion = Validator::make($datos, $this->reglasImportacion('cie10'), [], $this->atributosImportacion('cie10'));

                    if ($validacion->fails()) {
                        $resultado['errores'][] = 'Fila '.$numeroFila.': '.$validacion->errors()->first();
                        $resultado['omitidos']++;

                        if ($progreso !== null && ($filasLeidas % 250 === 0)) {
                            $progreso([
                                'mensaje' => 'Validando filas del archivo...',
                                'procesadas' => $filasLeidas,
                                'omitidos' => $resultado['omitidos'],
                            ]);
                        }

                        continue;
                    }

                    // El codigo es la clave unica: si aparece dos veces en el mismo lote, prevalece la ultima fila.
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

            return $resultado;
        }

        return $resultado;
    }

    /**
     * Importa medicamentos por lotes para soportar plantillas extensas sin procesar fila por fila.
     *
     * @return array{creados: int, actualizados: int, omitidos: int, errores: array<int, string>}
     */
    private function importarMedicamentosPorLotes(UploadedFile $archivo, int $usuarioId, ?Closure $progreso = null): array
    {
        $resultado = [
            'creados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => [],
        ];
        $loteFilas = [];
        $descripcionesContadas = [];
        $guardadas = 0;
        $reader = new Reader;
        $reader->open($archivo->getRealPath());
        $filasLeidas = 0;
        $guardarLote = function () use (&$loteFilas, &$descripcionesContadas, &$guardadas, &$resultado, $usuarioId, $progreso, &$filasLeidas): void {
            if ($loteFilas === []) {
                return;
            }

            $descripciones = array_keys($loteFilas);
            $registrosExistentes = Medicamento::query()
                ->withTrashed()
                ->whereIn('descripcion', $descripciones)
                ->get(['id', 'descripcion', 'registrado_por'])
                ->keyBy('descripcion');

            $ahora = now();
            $filasNuevas = [];
            $filasExistentes = [];

            foreach ($loteFilas as $descripcion => $datos) {
                $registroExistente = $registrosExistentes->get($descripcion);

                if (! isset($descripcionesContadas[$descripcion])) {
                    $registroExistente === null
                        ? $resultado['creados']++
                        : $resultado['actualizados']++;

                    $descripcionesContadas[$descripcion] = true;
                }

                $fila = [
                    'descripcion' => $descripcion,
                    'presentacion' => $datos['presentacion'],
                    'generico' => $datos['generico'],
                    'stock' => $datos['stock'] ?? 0,
                    'estado' => $this->valorEstadoPersistible('medicamentos', $datos['estado']),
                    'id_producto_externo' => $datos['id_producto_externo'],
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
                DB::table('medicamentos')->insert($filasNuevas);
            }

            if ($filasExistentes !== []) {
                DB::table('medicamentos')->upsert(
                    $filasExistentes,
                    ['id'],
                    ['descripcion', 'presentacion', 'generico', 'stock', 'estado', 'id_producto_externo', 'actualizado_por', 'updated_at', 'deleted_at'],
                );
            }

            $guardadas += count($filasNuevas) + count($filasExistentes);
            $loteFilas = [];

            $progreso?->__invoke([
                'mensaje' => 'Guardando medicamentos...',
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
                        $faltantes = array_diff($this->columnasImportacion('medicamentos'), $encabezados);

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
                    $datos = $this->normalizarDatosImportacion('medicamentos', $this->mapearFila($encabezados, $valores));
                    $validacion = Validator::make($datos, $this->reglasImportacion('medicamentos'), [], $this->atributosImportacion('medicamentos'));

                    if ($validacion->fails()) {
                        $resultado['errores'][] = 'Fila '.$numeroFila.': '.$validacion->errors()->first();
                        $resultado['omitidos']++;

                        if ($progreso !== null && ($filasLeidas % 250 === 0)) {
                            $progreso([
                                'mensaje' => 'Validando filas del archivo...',
                                'procesadas' => $filasLeidas,
                                'omitidos' => $resultado['omitidos'],
                            ]);
                        }

                        continue;
                    }

                    // La descripcion es la clave funcional del catalogo: ante duplicados, prevalece la ultima fila.
                    $loteFilas[$datos['descripcion']] = $datos;

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

    public function consulta(string $catalogo, ?string $busqueda = null, ?string $estado = null): Builder
    {
        $configuracion = $this->configuracion($catalogo);
        $modelo = $configuracion['modelo'];

        return $modelo::query()
            ->when($this->usaEstado($catalogo) && $estado !== null && $estado !== '' && $estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado', $this->valorEstadoPersistible($catalogo, $estado)))
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
        $encabezados = collect($configuracion['campos'])->pluck('etiqueta');

        if ($this->usaEstadoPorConfiguracion($configuracion)) {
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
     * @param  array<string, mixed>  $configuracion
     * @return array<int, string>
     */
    private function filaExportacion(mixed $registro, array $configuracion): array
    {
        $fila = collect($configuracion['campos'])
            ->map(fn (array $campo): string => $this->valorExportable(data_get($registro, $campo['nombre'])))
            ->values();

        if ($this->usaEstadoPorConfiguracion($configuracion)) {
            $fila->push($this->valorEstadoExportable(data_get($registro, 'estado')));
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
     * @return array<int, string>
     */
    private function filaEjemplo(string $catalogo, int $numero): array
    {
        $valores = collect($this->configuracion($catalogo)['campos'])
            ->map(fn (array $campo): string|int|float => $this->valorEjemplo($campo, $numero));

        if ($this->usaEstado($catalogo)) {
            $valores->push($numero === 1 ? EstadoCatalogo::Activo->value : EstadoCatalogo::Inactivo->value);
        }

        $valores = $valores->values()->all();

        return $valores;
    }

    private function valorEjemplo(array $campo, int $numero): string|int|float
    {
        if (($campo['tipo'] ?? 'text') === 'number') {
            return $numero;
        }

        if (($campo['tipo'] ?? 'text') === 'decimal') {
            return $numero * 10.5;
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

            if (($campo['tipo'] ?? 'text') === 'decimal') {
                $valor = $valor === '' ? null : (float) $valor;
            }

            if (! ($campo['requerido'] ?? false) && $valor === '') {
                $valor = null;
            }

            $datos[$nombre] = $valor;
        }

        if ($this->usaEstado($catalogo)) {
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
    private function reglasImportacion(string $catalogo): array
    {
        $reglas = [];

        foreach ($this->configuracion($catalogo)['campos'] as $campo) {
            $reglaCampo = [($campo['requerido'] ?? false) ? 'required' : 'nullable'];

            if (($campo['tipo'] ?? 'text') === 'number') {
                $reglaCampo[] = 'integer';
                $reglaCampo[] = 'min:'.($campo['min'] ?? 0);
                if (array_key_exists('max_numero', $campo)) {
                    $reglaCampo[] = 'max:'.$campo['max_numero'];
                }
            } elseif (($campo['tipo'] ?? 'text') === 'decimal') {
                $reglaCampo[] = 'numeric';
                $reglaCampo[] = 'min:'.($campo['min'] ?? 0);
            } else {
                $reglaCampo[] = 'string';
                $reglaCampo[] = 'max:'.($campo['max'] ?? 255);
            }

            if (($campo['tipo'] ?? 'text') === 'select') {
                $reglaCampo[] = Rule::in($campo['opciones']);
            }

            $reglas[$campo['nombre']] = $reglaCampo;
        }

        if (isset($reglas['rango_final'])) {
            $reglas['rango_final'][] = 'gte:rango_inicial';
        }

        if ($this->usaEstado($catalogo)) {
            $reglas['estado'] = ['required', Rule::enum(EstadoCatalogo::class)];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    private function atributosImportacion(string $catalogo): array
    {
        return collect($this->configuracion($catalogo)['campos'])
            ->mapWithKeys(fn (array $campo): array => [$campo['nombre'] => mb_strtolower($campo['etiqueta'])])
            ->merge($this->usaEstado($catalogo) ? ['estado' => 'estado'] : [])
            ->all();
    }

    private function registroPorClaveImportacion(string $catalogo, array $datos): mixed
    {
        $configuracion = $this->configuracion($catalogo);
        $modelo = $configuracion['modelo'];
        $criterios = $this->criteriosClaveImportacion($configuracion, $datos);
        $consulta = $modelo::query();

        if ($this->usaEliminacionLogica($modelo)) {
            $consulta->withTrashed();
        }

        return $consulta->firstOrNew($criterios);
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

    private function usaEstado(string $catalogo): bool
    {
        $configuracion = $this->configuracion($catalogo);

        return $this->usaEstadoPorConfiguracion($configuracion);
    }

    /**
     * @param  array<string, mixed>  $configuracion
     */
    private function usaEstadoPorConfiguracion(array $configuracion): bool
    {
        return ($configuracion['maneja_estado'] ?? true)
            && Schema::hasColumn($configuracion['tabla'], 'estado');
    }

    private function valorEstadoPersistible(string $catalogo, string $estado): string|bool
    {
        if (($this->configuracion($catalogo)['estado_tipo'] ?? 'catalogo') === 'booleano') {
            return $estado === EstadoCatalogo::Activo->value;
        }

        return $estado;
    }

    private function valorEstadoExportable(mixed $estado): string
    {
        if (is_bool($estado) || is_int($estado)) {
            return (bool) $estado ? EstadoCatalogo::Activo->value : EstadoCatalogo::Inactivo->value;
        }

        return $this->valorExportable($estado);
    }

    private function prepararImportacionMasiva(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');
    }

    /**
     * @param  class-string  $modelo
     */
    private function usaEliminacionLogica(string $modelo): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelo), true);
    }
}
