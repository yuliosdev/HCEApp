<?php

namespace App\Livewire\Catalogos;

use App\Enums\EstadoCatalogo;
use App\Services\Catalogos\CatalogosClinicosArchivoService;
use App\Support\Catalogos\CatalogosClinicos;
use BackedEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CatalogoClinicoPanel extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    public string $catalogo;

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
     * Valores editables del formulario dinamico del catalogo.
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

    public function mount(string $catalogo): void
    {
        $this->catalogo = $catalogo;
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
        $this->authorize('catalogos.gestionar');
        $this->resetFormulario();
        $this->modoFormulario = 'crear';
        $this->modalAbierto = true;
    }

    public function ver(int $registroId): void
    {
        $this->authorize('catalogos.ver');
        $this->cargarRegistro($registroId);
        $this->modoFormulario = 'ver';
        $this->modalAbierto = true;
    }

    public function editar(int $registroId): void
    {
        $this->authorize('catalogos.gestionar');
        $this->cargarRegistro($registroId);
        $this->modoFormulario = 'editar';
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $this->authorize('catalogos.gestionar');

        if ($this->modoFormulario === 'ver') {
            return;
        }

        $datos = $this->validate($this->reglas(), [], $this->atributos());
        $configuracion = $this->configuracion();
        $modelo = $configuracion['modelo'];

        $registro = $this->registroId
            ? $modelo::query()->findOrFail($this->registroId)
            : new $modelo(['registrado_por' => Auth::id()]);

        $registro->fill(array_merge($this->datosNormalizados($datos['form']), [
            'estado' => $datos['estadoRegistro'],
            'actualizado_por' => Auth::id(),
        ]));

        $registro->save();
        $this->cerrarModal();

        flash()->success($configuracion['singular'].' guardado correctamente.', [], 'Catalogos');
    }

    public function cambiarEstado(int $registroId): void
    {
        $this->authorize('catalogos.gestionar');

        $registro = $this->consultaRegistro($registroId);
        $nuevoEstado = $this->valorEstadoActual($registro) === EstadoCatalogo::Activo->value
            ? EstadoCatalogo::Inactivo->value
            : EstadoCatalogo::Activo->value;

        $registro->forceFill([
            'estado' => $nuevoEstado,
            'actualizado_por' => Auth::id(),
        ])->save();

        flash()->success('Estado actualizado correctamente.', [], 'Catalogos');
    }

    public function eliminar(int $registroId): void
    {
        $this->authorize('catalogos.gestionar');

        $configuracion = $this->configuracion();
        $registro = $this->consultaRegistro($registroId);

        if ($this->usoTotal($registro) > 0) {
            flash()->warning('No se puede eliminar este '.$configuracion['singular'].' porque ya tiene uso clinico.', [], 'Catalogos');

            return;
        }

        $registro->forceFill(['actualizado_por' => Auth::id()])->save();
        $registro->delete();

        flash()->success($configuracion['singular'].' eliminado correctamente.', [], 'Catalogos');
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda = '';
        $this->estado = 'Todos';
        $this->resetPage();
    }

    public function importarDesdeExcel(): void
    {
        $this->authorize('catalogos.gestionar');
        abort_unless($this->esAdministrador(), 403);

        $this->validate([
            'archivoImportacion' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [], [
            'archivoImportacion' => 'archivo de importacion',
        ]);

        $resultado = app(CatalogosClinicosArchivoService::class)
            ->importar($this->catalogo, $this->archivoImportacion, Auth::id());

        $this->archivoImportacion = null;
        $this->resetPage();

        if ($resultado['errores'] !== []) {
            flash()->warning(
                'Importacion terminada con observaciones: '.implode(' ', array_slice($resultado['errores'], 0, 3)),
                [],
                'Catalogos',
            );

            return;
        }

        flash()->success(
            'Importacion completada. Creados: '.$resultado['creados'].', actualizados: '.$resultado['actualizados'].', omitidos: '.$resultado['omitidos'].'.',
            [],
            'Catalogos',
        );
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

        return filled($valor) ? (string) $valor : '-';
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
        return view('livewire.catalogos.catalogo-clinico-panel', [
            'configuracion' => $this->configuracion(),
            'registros' => $this->registros(),
            'estadisticas' => $this->estadisticas(),
            'estados' => ['Todos', EstadoCatalogo::Activo->value, EstadoCatalogo::Inactivo->value],
            'esAdministrador' => $this->esAdministrador(),
            'opcionesPorPagina' => [5, 10, 15, 25, 50],
        ]);
    }

    /**
     * Reglas dinamicas de validacion segun el catalogo seleccionado.
     *
     * @return array<string, mixed>
     */
    private function reglas(): array
    {
        $reglas = [];
        $tabla = $this->configuracion()['tabla'];

        foreach ($this->configuracion()['campos'] as $campo) {
            $nombre = $campo['nombre'];
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

            if (($campo['unico'] ?? false) && filled($this->form[$nombre] ?? null)) {
                $reglaCampo[] = Rule::unique($tabla, $nombre)->ignore($this->registroId);
            }

            if (filled($campo['unico_compuesto'] ?? [])) {
                $reglaCampo[] = Rule::unique($tabla, $nombre)
                    ->where(fn ($consulta) => $consulta->where(
                        $campo['unico_compuesto'][1],
                        $this->form[$campo['unico_compuesto'][1]] ?: null,
                    ))
                    ->ignore($this->registroId);
            }

            $reglas['form.'.$nombre] = $reglaCampo;
        }

        $reglas['estadoRegistro'] = ['required', Rule::enum(EstadoCatalogo::class)];

        return $reglas;
    }

    /**
     * Atributos en espanol para mensajes de validacion.
     *
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
            ->orderBy($this->configuracion()['orden'])
            ->paginate($this->porPagina)
            ->withPath(route('catalogos.'.$this->catalogo.'.index'))
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
     * Obtiene indicadores resumidos del catalogo.
     *
     * @return array<string, int>
     */
    private function estadisticas(): array
    {
        $modelo = $this->configuracion()['modelo'];

        return [
            'total' => $modelo::query()->count(),
            'activos' => $modelo::query()->where('estado', EstadoCatalogo::Activo->value)->count(),
            'inactivos' => $modelo::query()->where('estado', EstadoCatalogo::Inactivo->value)->count(),
            'eliminados' => $this->usaEliminacionLogica($modelo) ? $modelo::onlyTrashed()->count() : 0,
        ];
    }

    private function consultaBase(): Builder
    {
        $modelo = $this->configuracion()['modelo'];

        return $modelo::query()
            ->when($this->estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado', $this->estado))
            ->when($this->busqueda !== '', function (Builder $consulta): void {
                $consulta->where(function (Builder $grupo): void {
                    foreach ($this->configuracion()['campos'] as $campo) {
                        $grupo->orWhere($campo['nombre'], 'like', "%{$this->busqueda}%");
                    }
                });
            });
    }

    private function consultaRegistro(int $registroId): mixed
    {
        $modelo = $this->configuracion()['modelo'];
        $consulta = $modelo::query();

        if ($this->configuracion()['relaciones_uso'] !== []) {
            $consulta->withCount($this->configuracion()['relaciones_uso']);
        }

        return $consulta->findOrFail($registroId);
    }

    private function cargarRegistro(int $registroId): void
    {
        $modelo = $this->configuracion()['modelo'];
        $registro = $modelo::query()
            ->with(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'])
            ->findOrFail($registroId);

        $this->registroId = $registro->id;

        foreach ($this->configuracion()['campos'] as $campo) {
            $this->form[$campo['nombre']] = $this->valorFormulario(data_get($registro, $campo['nombre']));
        }

        $this->estadoRegistro = $this->valorEstadoActual($registro);
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

            $this->form[$campo['nombre']] = ($campo['tipo'] ?? 'text') === 'select' && ($campo['requerido'] ?? false)
                ? ($campo['opciones'][0] ?? '')
                : '';
        }

        $this->resetValidation();
    }

    /**
     * Normaliza datos antes de persistirlos en la base.
     *
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

                if (($campo['tipo'] ?? 'text') === 'number' && $valor !== null && $valor !== '') {
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
        return CatalogosClinicos::obtener($this->catalogo);
    }

    private function valorFormulario(mixed $valor): mixed
    {
        return $valor instanceof BackedEnum ? $valor->value : ($valor ?? '');
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
