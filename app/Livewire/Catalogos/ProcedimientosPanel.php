<?php

namespace App\Livewire\Catalogos;

use App\Enums\EstadoCatalogo;
use App\Models\Procedimiento;
use App\Services\Catalogos\ProcedimientosArchivoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProcedimientosPanel extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    #[Url(except: '')]
    public string $busqueda = '';

    #[Url(except: 'Todos')]
    public string $estado = 'Todos';

    #[Url(except: 5)]
    public int $porPagina = 5;

    public bool $modalAbierto = false;

    public string $modoFormulario = 'crear';

    public ?int $procedimientoId = null;

    public string $codigo = '';

    public string $denominacion = '';

    public string $estadoRegistro = 'Activo';

    public $archivoImportacion = null;

    /**
     * Datos de auditoria visibles solo para administradores.
     *
     * @var array<string, string>
     */
    public array $auditoria = [];

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

    public function ver(int $procedimientoId): void
    {
        $this->authorize('catalogos.ver');
        $this->cargarProcedimiento($procedimientoId);
        $this->modoFormulario = 'ver';
        $this->modalAbierto = true;
    }

    public function editar(int $procedimientoId): void
    {
        $this->authorize('catalogos.gestionar');
        $this->cargarProcedimiento($procedimientoId);
        $this->modoFormulario = 'editar';
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $this->authorize('catalogos.gestionar');

        if ($this->modoFormulario === 'ver') {
            return;
        }

        $datos = $this->validate($this->reglas(), [], [
            'codigo' => 'codigo',
            'denominacion' => 'denominacion',
            'estadoRegistro' => 'estado',
        ]);

        $procedimiento = $this->procedimientoId
            ? Procedimiento::query()->findOrFail($this->procedimientoId)
            : new Procedimiento(['registrado_por' => Auth::id()]);

        $procedimiento->fill([
            'codigo' => $datos['codigo'],
            'denominacion' => $datos['denominacion'],
            'estado' => $datos['estadoRegistro'],
            'actualizado_por' => Auth::id(),
        ]);
        $procedimiento->save();

        $this->cerrarModal();

        flash()->success('Procedimiento guardado correctamente.', [], 'Catalogos');
    }

    public function importarDesdeExcel(): void
    {
        abort_unless($this->esAdministrador(), 403);

        $this->validate([
            'archivoImportacion' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [], [
            'archivoImportacion' => 'archivo de importacion',
        ]);

        $resultado = app(ProcedimientosArchivoService::class)
            ->importar($this->archivoImportacion, Auth::id());

        $this->archivoImportacion = null;
        $this->resetPage();

        $mensaje = "Importacion finalizada. Creados: {$resultado['creados']}. Actualizados: {$resultado['actualizados']}. Omitidos: {$resultado['omitidos']}.";

        if ($resultado['errores'] !== []) {
            flash()->warning($mensaje.' Revisa el archivo: '.implode(' ', array_slice($resultado['errores'], 0, 3)), [], 'Catalogos');

            return;
        }

        flash()->success($mensaje, [], 'Catalogos');
    }

    public function cambiarEstado(int $procedimientoId): void
    {
        $this->authorize('catalogos.gestionar');

        $procedimiento = Procedimiento::query()->findOrFail($procedimientoId);
        $nuevoEstado = $procedimiento->estado === EstadoCatalogo::Activo->value
            ? EstadoCatalogo::Inactivo->value
            : EstadoCatalogo::Activo->value;

        $procedimiento->forceFill([
            'estado' => $nuevoEstado,
            'actualizado_por' => Auth::id(),
        ])->save();

        flash()->success('Estado del procedimiento actualizado.', [], 'Catalogos');
    }

    public function eliminar(int $procedimientoId): void
    {
        $this->authorize('catalogos.gestionar');

        $procedimiento = Procedimiento::query()->withCount('procedimientosRealizados')->findOrFail($procedimientoId);

        if ($procedimiento->procedimientos_realizados_count > 0) {
            flash()->warning('No se puede eliminar un procedimiento que ya fue usado en una atencion.', [], 'Catalogos');

            return;
        }

        $procedimiento->forceFill(['actualizado_por' => Auth::id()])->save();
        $procedimiento->delete();

        flash()->success('Procedimiento eliminado correctamente.', [], 'Catalogos');
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

    public function render()
    {
        return view('livewire.catalogos.procedimientos-panel', [
            'procedimientos' => $this->procedimientos(),
            'estadisticas' => $this->estadisticas(),
            'estados' => ['Todos', EstadoCatalogo::Activo->value, EstadoCatalogo::Inactivo->value],
            'esAdministrador' => $this->esAdministrador(),
            'opcionesPorPagina' => [5, 10, 15, 25, 50],
        ]);
    }

    /**
     * Reglas de validacion para el catalogo de procedimientos.
     *
     * @return array<string, mixed>
     */
    private function reglas(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('procedimientos', 'codigo')->ignore($this->procedimientoId),
            ],
            'denominacion' => ['required', 'string', 'max:255'],
            'estadoRegistro' => ['required', Rule::enum(EstadoCatalogo::class)],
        ];
    }

    private function procedimientos(): LengthAwarePaginator
    {
        return $this->consultaBase()
            ->withCount('procedimientosRealizados')
            ->orderBy('codigo')
            ->paginate($this->porPagina)
            ->withPath(route('catalogos.procedimientos.index'))
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
        return [
            'total' => Procedimiento::query()->count(),
            'activos' => Procedimiento::query()->where('estado', EstadoCatalogo::Activo->value)->count(),
            'inactivos' => Procedimiento::query()->where('estado', EstadoCatalogo::Inactivo->value)->count(),
            'eliminados' => Procedimiento::onlyTrashed()->count(),
        ];
    }

    private function consultaBase(): Builder
    {
        return Procedimiento::query()
            ->when($this->estado !== 'Todos', fn (Builder $consulta): Builder => $consulta->where('estado', $this->estado))
            ->when($this->busqueda !== '', function (Builder $consulta): void {
                $consulta->where(function (Builder $grupo): void {
                    $grupo
                        ->where('codigo', 'like', "%{$this->busqueda}%")
                        ->orWhere('denominacion', 'like', "%{$this->busqueda}%");
                });
            });
    }

    private function cargarProcedimiento(int $procedimientoId): void
    {
        $procedimiento = Procedimiento::query()
            ->with(['registradoPor:id,name,email', 'actualizadoPor:id,name,email'])
            ->findOrFail($procedimientoId);

        $this->procedimientoId = $procedimiento->id;
        $this->codigo = $procedimiento->codigo;
        $this->denominacion = $procedimiento->denominacion;
        $this->estadoRegistro = $procedimiento->estado;
        $this->auditoria = [
            'registrado_por' => optional($procedimiento->registradoPor)->name ?? 'No registrado',
            'registrado_email' => optional($procedimiento->registradoPor)->email ?? '',
            'fecha_registro' => optional($procedimiento->created_at)->format('d/m/Y H:i:s') ?? 'No registrado',
            'actualizado_por' => optional($procedimiento->actualizadoPor)->name ?? 'No registrado',
            'actualizado_email' => optional($procedimiento->actualizadoPor)->email ?? '',
            'fecha_actualizacion' => optional($procedimiento->updated_at)->format('d/m/Y H:i:s') ?? 'No registrado',
        ];
        $this->resetValidation();
    }

    private function resetFormulario(): void
    {
        $this->procedimientoId = null;
        $this->codigo = '';
        $this->denominacion = '';
        $this->estadoRegistro = EstadoCatalogo::Activo->value;
        $this->modoFormulario = 'crear';
        $this->auditoria = [];
        $this->resetValidation();
    }

    private function esAdministrador(): bool
    {
        return Auth::user()?->hasRole('Administrador') ?? false;
    }
}
