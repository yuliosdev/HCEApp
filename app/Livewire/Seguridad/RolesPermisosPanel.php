<?php

namespace App\Livewire\Seguridad;

use App\Support\Seguridad\Permisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosPanel extends Component
{
    use AuthorizesRequests;

    public string $busqueda = '';

    public bool $mostrarFormulario = false;

    public ?int $rolId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $permisosSeleccionados = [];

    public function crear(): void
    {
        $this->authorize('roles.crear');
        $this->resetFormulario();
        $this->mostrarFormulario = true;
    }

    public function editar(int $rolId): void
    {
        $this->authorize('roles.editar');

        $rol = Role::query()->with('permissions')->findOrFail($rolId);

        $this->rolId = $rol->id;
        $this->name = $rol->name;
        $this->permisosSeleccionados = $rol->permissions->pluck('name')->values()->all();
        $this->mostrarFormulario = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->authorize($this->rolId ? 'roles.editar' : 'roles.crear');

        $datos = $this->validate($this->reglas(), [], [
            'name' => 'rol',
            'permisosSeleccionados' => 'permisos',
        ]);

        $rol = $this->rolId
            ? Role::query()->findOrFail($this->rolId)
            : new Role(['guard_name' => 'web']);

        $rol->name = $datos['name'];
        $rol->guard_name = 'web';
        $rol->save();

        $permisos = $rol->name === 'Administrador'
            ? Permisos::nombres()
            : $datos['permisosSeleccionados'];

        $rol->syncPermissions($permisos);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->mostrarFormulario = false;
        $this->resetFormulario();

        flash()->success('Rol guardado correctamente.', [], 'Seguridad');
    }

    public function cancelar(): void
    {
        $this->mostrarFormulario = false;
        $this->resetFormulario();
    }

    public function seleccionarGrupo(string $grupo): void
    {
        $permisosGrupo = collect(Permisos::definiciones())
            ->firstWhere('grupo', $grupo)['permisos'] ?? [];

        $nombres = collect($permisosGrupo)->pluck('nombre')->all();

        $this->permisosSeleccionados = collect($this->permisosSeleccionados)
            ->merge($nombres)
            ->unique()
            ->values()
            ->all();
    }

    public function limpiarPermisos(): void
    {
        $this->permisosSeleccionados = [];
    }

    public function render()
    {
        return view('livewire.seguridad.roles-permisos-panel', [
            'roles' => $this->roles(),
            'definiciones' => Permisos::definiciones(),
        ]);
    }

    /**
     * Reglas de validacion para roles y permisos.
     *
     * @return array<string, mixed>
     */
    private function reglas(): array
    {
        return [
            'name' => ['required', 'string', 'max:125', Rule::unique('roles', 'name')->ignore($this->rolId)],
            'permisosSeleccionados' => ['required', 'array', 'min:1'],
            'permisosSeleccionados.*' => ['string', Rule::in(Permisos::nombres())],
        ];
    }

    private function roles()
    {
        return Role::query()
            ->with('permissions')
            ->when($this->busqueda !== '', function (Builder $consulta): void {
                $consulta->where('name', 'like', "%{$this->busqueda}%");
            })
            ->orderBy('name')
            ->get();
    }

    private function resetFormulario(): void
    {
        $this->rolId = null;
        $this->name = '';
        $this->permisosSeleccionados = [];
        $this->resetValidation();
    }
}
