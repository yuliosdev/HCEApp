<?php

namespace App\Livewire\GestionClinica;

use App\Enums\EstadoCatalogo;
use App\Services\GestionClinica\RegistrosClinicosArchivoService;
use App\Support\GestionClinica\RegistrosClinicos;
use BackedEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class RegistroClinicoPanel extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    public string $registroClinico;

    #[Url(except: '')]
    public string $busqueda = '';

    #[Url(except: 'Todos')]
    public string $estado = 'Todos';

    #[Url(except: 5)]
    public int $porPagina = 5;

    public bool $modalAbierto = false;

    public string $modoFormulario = 'crear';

    public ?int $registroId = null;

    public string $estadoRegistro = 'Activo';

    public mixed $archivoImportacion = null;

    /**
     * Valores editables del formulario dinamico.
     *
     * @var array<string, mixed>
     */
    public array $form = [];

    /**
     * Datos de auditoria visibles solo para administradores.
     *
     * @var array<string, string>
     */
    public array $auditoria = [];

    public function mount(string $registroClinico): void
    {
        $this->registroClinico = $registroClinico;
        $this->authorize($this->configuracion()['permiso_ver']);
        $this->resetFormulario();
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    public function updatingPorPagina(): void
    {
        $this->resetPage();
    }

    public function abrirNuevo(): void
    {
        $this->authorize($this->configuracion()['permiso_crear']);
        $this->resetFormulario();
        $this->modoFormulario = 'crear';
        $this->modalAbierto = true;
    }

    public function ver(int $registroId): void
    {
        $this->authorize($this->configuracion()['permiso_ver']);
        $this->cargarRegistro($registroId);
        $this->modoFormulario = 'ver';
        $this->modalAbierto = true;
    }

    public function editar(int $registroId): void
    {
        $this->authorize($this->configuracion()['permiso_editar']);
        $this->cargarRegistro($registroId);
        $this->modoFormulario = 'editar';
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $this->authorize($this->modoFormulario === 'crear'
            ? $this->configuracion()['permiso_crear']
            : $this->configuracion()['permiso_editar']);

        if ($this->modoFormulario === 'ver') {
            return;
        }

        $datos = $this->validate($this->reglas(), [], $this->atributos());
        $configuracion = $this->configuracion();
        $modelo = $configuracion['modelo'];

        $registro = $this->registroId
            ? $modelo::query()->findOrFail($this->registroId)
            : new $modelo(['registrado_por' => Auth::id()]);

        $datosPersistibles = array_merge($this->datosNormalizados($datos['form']), [
            'actualizado_por' => Auth::id(),
        ]);

        if ($this->tieneEstado()) {
            $datosPersistibles['estado'] = $datos['estadoRegistro'];
        }

        $registro->fill($datosPersistibles);
        $registro->save();
        $this->cerrarModal();

        flash()->success($configuracion['singular'].' guardado correctamente.', [], 'Gestion clinica');
    }

    public function cambiarEstado(int $registroId): void
    {
        abort_unless($this->tieneEstado(), 404);
        $this->authorize($this->configuracion()['permiso_editar']);

        $registro = $this->consultaRegistro($registroId);
        $nuevoEstado = $this->valorEstadoActual($registro) === EstadoCatalogo::Activo->value
            ? EstadoCatalogo::Inactivo->value
            : EstadoCatalogo::Activo->value;

        $registro->forceFill([
            'estado' => $nuevoEstado,
            'actualizado_por' => Auth::id(),
        ])->save();

        flash()->success('Estado actualizado correctamente.', [], 'Gestion clinica');
    }

    public function eliminar(int $registroId): void
    {
        $this->authorize($this->configuracion()['permiso_editar']);

        $configuracion = $this->configuracion();
        $registro = $this->consultaRegistro($registroId);

        if ($this->usoTotal($registro) > 0) {
            flash()->warning('No se puede eliminar este '.$configuracion['singular'].' porque ya tiene uso clinico.', [], 'Gestion clinica');

            return;
        }

        $registro->forceFill(['actualizado_por' => Auth::id()])->save();
        $registro->delete();

        flash()->success($configuracion['singular'].' eliminado correctamente.', [], 'Gestion clinica');
    }

    public function importarDesdeExcel(): void
    {
        $this->authorize($this->configuracion()['permiso_editar']);
        abort_unless($this->esAdministrador(), 403);

        $this->validate([
            'archivoImportacion' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [], [
            'archivoImportacion' => 'archivo de importacion',
        ]);

        $resultado = app(RegistrosClinicosArchivoService::class)
            ->importar($this->registroClinico, $this->archivoImportacion, Auth::id());

        $this->archivoImportacion = null;
        $this->resetPage();

        if ($resultado['errores'] !== []) {
            flash()->warning(
                'Importacion terminada con observaciones: '.implode(' ', array_slice($resultado['errores'], 0, 3)),
                [],
                'Gestion clinica',
            );

            return;
        }

        flash()->success(
            'Importacion completada. Creados: '.$resultado['creados'].', actualizados: '.$resultado['actualizados'].', omitidos: '.$resultado['omitidos'].'.',
            [],
            'Gestion clinica',
        );
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda = '';
        $this->estado = 'Todos';
        $this->resetPage();
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetFormulario();
    }

    public function valorCampo(mixed $registro, string $campo): string
    {
        $valor = data_get($registro, $campo);

        if ($valor instanceof BackedEnum) {
            return $valor->value;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('d/m/Y');
        }

        return filled($valor) ? (string) $valor : '-';
    }

    public function valorCampoConfigurado(mixed $registro, array $campo): string
    {
        if (($campo['tipo'] ?? 'text') === 'model_select') {
            $valor = data_get($registro, $campo['relacion'].'.'.$campo['relacion_campo']);

            return filled($valor) ? (string) $valor : '-';
        }

        return $this->valorCampo($registro, $campo['nombre']);
    }

    /**
     * Devuelve opciones dinamicas para selectores relacionados con catalogos.
     *
     * @return array<int, array{valor: string, texto: string}>
     */
    public function opcionesCampo(array $campo): array
    {
        if (($campo['tipo'] ?? 'text') !== 'model_select') {
            return collect($campo['opciones'] ?? [])
                ->map(fn (string $opcion): array => ['valor' => $opcion, 'texto' => $opcion])
                ->values()
                ->all();
        }

        $modelo = $campo['modelo_opciones'];
        $valor = $campo['opcion_valor'];
        $texto = $campo['opcion_texto'];

        return $modelo::query()
            ->when(Schema::hasColumn((new $modelo)->getTable(), 'estado'), fn (Builder $consulta): Builder => $consulta->where('estado', EstadoCatalogo::Activo->value))
            ->orderBy($texto)
            ->get([$valor, $texto])
            ->map(fn ($opcion): array => ['valor' => (string) $opcion->{$valor}, 'texto' => (string) $opcion->{$texto}])
            ->values()
            ->all();
    }

    public function valorEstado(mixed $registro): string
    {
        return $this->valorEstadoActual($registro);
    }

    public function usoTotal(mixed $registro): int
    {
        return collect($this->configuracion()['relaciones_uso'])
            ->sum(fn (string $relacion): int => (int) data_get($registro, $relacion.'_count', 0));
    }

    public function etiquetaRegistro(mixed $registro): string
    {
        $campoPrincipal = $this->configuracion()['campos'][0]['nombre'];

        return $this->valorCampo($registro, $campoPrincipal);
    }

    public function render()
    {
        return view('livewire.gestion-clinica.registro-clinico-panel', [
            'configuracion' => $this->configuracion(),
            'registros' => $this->registros(),
            'estadisticas' => $this->estadisticas(),
            'estados' => ['Todos', EstadoCatalogo::Activo->value, EstadoCatalogo::Inactivo->value],
            'esAdministrador' => $this->esAdministrador(),
            'opcionesPorPagina' => [5, 10, 15, 25, 50],
            'tieneEstado' => $this->tieneEstado(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reglas(): array
    {
        $reglas = [];
        $tabla = $this->configuracion()['tabla'];

        foreach ($this->configuracion()['campos'] as $campo) {
            $nombre = $campo['nombre'];
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

            if (($campo['unico'] ?? false) && filled($this->form[$nombre] ?? null)) {
                $reglaCampo[] = Rule::unique($tabla, $nombre)->ignore($this->registroId);
            }

            if (filled($campo['unico_compuesto'] ?? []) && collect($campo['unico_compuesto'])->every(fn (string $campoUnico): bool => filled($this->form[$campoUnico] ?? null))) {
                $reglaCampo[] = Rule::unique($tabla, $nombre)
                    ->where(fn ($consulta) => $consulta->where(
                        $campo['unico_compuesto'][0],
                        $this->form[$campo['unico_compuesto'][0]],
                    ))
                    ->ignore($this->registroId);
            }

            $reglas['form.'.$nombre] = $reglaCampo;
        }

        if ($this->tieneEstado()) {
            $reglas['estadoRegistro'] = ['required', Rule::enum(EstadoCatalogo::class)];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    private function atributos(): array
    {
        return collect($this->configuracion()['campos'])
            ->mapWithKeys(fn (array $campo): array => ['form.'.$campo['nombre'] => mb_strtolower($campo['etiqueta'])])
            ->merge(['estadoRegistro' => 'estado'])
            ->all();
    }

    private function registros(): LengthAwarePaginator
    {
        $consulta = $this->consultaBase();

        if ($this->configuracion()['relaciones_uso'] !== []) {
            $consulta->withCount($this->configuracion()['relaciones_uso']);
        }

        return $consulta
            ->with($this->relacionesSelect())
            ->orderBy($this->configuracion()['orden'])
            ->paginate($this->porPagina)
            ->withPath(route('gestion-clinica.'.$this->registroClinico.'.index'))
            ->appends($this->parametrosPaginacion());
    }

    /**
     * Conserva filtros activos en los enlaces del paginador.
     *
     * @return array<string, mixed>
     */
    private function parametrosPaginacion(): array
    {
        return collect([
            'busqueda' => $this->busqueda !== '' ? $this->busqueda : null,
            'estado' => $this->estado !== 'Todos' ? $this->estado : null,
            'porPagina' => $this->porPagina !== 5 ? $this->porPagina : null,
        ])->filter(fn ($valor): bool => $valor !== null)->all();
    }

    /**
     * @return array<string, int>
     */
    private function estadisticas(): array
    {
        $modelo = $this->configuracion()['modelo'];

        if ($this->tieneEstado()) {
            return [
                'total' => $modelo::query()->count(),
                'activos' => $modelo::query()->where('estado', EstadoCatalogo::Activo->value)->count(),
                'inactivos' => $modelo::query()->where('estado', EstadoCatalogo::Inactivo->value)->count(),
                'eliminados' => $this->usaEliminacionLogica($modelo) ? $modelo::onlyTrashed()->count() : 0,
            ];
        }

        return [
            'total' => $modelo::query()->count(),
            'activos' => $modelo::query()->whereNotNull('numero_documento')->count(),
            'inactivos' => $modelo::query()->whereNull('numero_documento')->count(),
            'eliminados' => $this->usaEliminacionLogica($modelo) ? $modelo::onlyTrashed()->count() : 0,
        ];
    }

    private function consultaBase(): Builder
    {
        $modelo = $this->configuracion()['modelo'];

        return $modelo::query()
            ->when($this->tieneEstado() && $this->estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado', $this->estado))
            ->when($this->busqueda !== '', function (Builder $consulta): void {
                $consulta->where(function (Builder $grupo): void {
                    foreach ($this->configuracion()['campos'] as $campo) {
                        if (($campo['tipo'] ?? 'text') === 'model_select') {
                            $grupo->orWhereHas($campo['relacion'], function (Builder $relacion) use ($campo): void {
                                $relacion->where($campo['relacion_campo'], 'like', "%{$this->busqueda}%");
                            });

                            continue;
                        }

                        $grupo->orWhere($campo['nombre'], 'like', "%{$this->busqueda}%");
                    }
                });
            });
    }

    private function consultaRegistro(int $registroId): mixed
    {
        $modelo = $this->configuracion()['modelo'];
        $consulta = $modelo::query();

        $consulta->with($this->relacionesSelect());

        if ($this->configuracion()['relaciones_uso'] !== []) {
            $consulta->withCount($this->configuracion()['relaciones_uso']);
        }

        return $consulta->findOrFail($registroId);
    }

    private function cargarRegistro(int $registroId): void
    {
        $modelo = $this->configuracion()['modelo'];
        $registro = $modelo::query()
            ->with(array_merge(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'], $this->relacionesSelect()))
            ->findOrFail($registroId);

        $this->registroId = $registro->id;

        foreach ($this->configuracion()['campos'] as $campo) {
            $this->form[$campo['nombre']] = $this->valorFormulario(data_get($registro, $campo['nombre']));
        }

        if ($this->tieneEstado()) {
            $this->estadoRegistro = $this->valorEstadoActual($registro);
        }

        $this->auditoria = [
            'registrado_por' => optional($registro->registradoPor)->name ?? 'No registrado',
            'registrado_email' => optional($registro->registradoPor)->email ?? '',
            'fecha_registro' => optional($registro->created_at)->format('d/m/Y H:i:s') ?? 'No registrado',
            'actualizado_por' => optional($registro->actualizadoPor)->name ?? 'No registrado',
            'actualizado_email' => optional($registro->actualizadoPor)->email ?? '',
            'fecha_actualizacion' => optional($registro->updated_at)->format('d/m/Y H:i:s') ?? 'No registrado',
        ];
        $this->resetValidation();
    }

    private function resetFormulario(): void
    {
        $this->registroId = null;
        $this->modoFormulario = 'crear';
        $this->estadoRegistro = EstadoCatalogo::Activo->value;
        $this->auditoria = [];
        $this->archivoImportacion = null;
        $this->form = [];

        foreach ($this->configuracion()['campos'] as $campo) {
            if (array_key_exists('default', $campo)) {
                $this->form[$campo['nombre']] = $campo['default'];

                continue;
            }

            $this->form[$campo['nombre']] = in_array(($campo['tipo'] ?? 'text'), ['select', 'model_select'], true) && ($campo['requerido'] ?? false)
                ? ($campo['opciones'][0] ?? '')
                : '';
        }

        $this->resetValidation();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function datosNormalizados(array $datos): array
    {
        return collect($this->configuracion()['campos'])
            ->mapWithKeys(function (array $campo) use ($datos): array {
                $nombre = $campo['nombre'];
                $valor = $datos[$nombre] ?? null;

                if (! ($campo['requerido'] ?? false) && $valor === '') {
                    $valor = null;
                }

                if ((($campo['tipo'] ?? 'text') === 'number' || ($campo['tipo'] ?? 'text') === 'model_select') && $valor !== null && $valor !== '') {
                    $valor = (int) $valor;
                }

                return [$nombre => $valor];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function configuracion(): array
    {
        return RegistrosClinicos::obtener($this->registroClinico);
    }

    /**
     * @return array<int, string>
     */
    private function relacionesSelect(): array
    {
        return collect($this->configuracion()['campos'])
            ->filter(fn (array $campo): bool => ($campo['tipo'] ?? 'text') === 'model_select')
            ->pluck('relacion')
            ->values()
            ->all();
    }

    private function tieneEstado(): bool
    {
        return (bool) ($this->configuracion()['tiene_estado'] ?? false);
    }

    private function valorFormulario(mixed $valor): mixed
    {
        if ($valor instanceof BackedEnum) {
            return $valor->value;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        return $valor ?? '';
    }

    private function valorEstadoActual(mixed $registro): string
    {
        $estado = data_get($registro, 'estado');

        return $estado instanceof BackedEnum ? $estado->value : (string) $estado;
    }

    /**
     * @param  class-string  $modelo
     */
    private function usaEliminacionLogica(string $modelo): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelo), true);
    }

    private function esAdministrador(): bool
    {
        return Auth::user()?->hasRole('Administrador') ?? false;
    }
}
