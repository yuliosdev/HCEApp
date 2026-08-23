<?php

namespace App\Livewire\Seguridad;

use App\Models\ProfesionalSalud;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsuariosPanel extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(except: '')]
    public string $busqueda = '';

    public bool $mostrarFormulario = false;

    public ?int $usuarioId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $activo = true;

    public string $tipoUsuario = 'Administrativo';

    public ?int $profesionalSaludId = null;

    public string $busquedaProfesional = '';

    public bool $mostrarSugerenciasProfesional = false;

    /** @var array<int, string> */
    public array $rolesSeleccionados = [];

    /**
     * Tipos de usuario definidos en el enum de la tabla users.
     *
     * @return array<int, string>
     */
    public function tiposUsuario(): array
    {
        return ['Supervisor', 'Administrativo', 'Médico', 'Profesional de Salud'];
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedTipoUsuario(): void
    {
        if (! $this->requiereProfesionalSalud()) {
            $this->profesionalSaludId = null;
            $this->busquedaProfesional = '';
            $this->mostrarSugerenciasProfesional = false;
        }
    }

    public function updatedBusquedaProfesional(): void
    {
        if (! $this->requiereProfesionalSalud()) {
            return;
        }

        $this->profesionalSaludId = null;
        $this->mostrarSugerenciasProfesional = trim($this->busquedaProfesional) !== '';
    }

    public function seleccionarProfesional(int $profesionalId): void
    {
        $profesional = ProfesionalSalud::query()
            ->with('especialidad')
            ->findOrFail($profesionalId);

        $this->profesionalSaludId = $profesional->id;
        $this->busquedaProfesional = $this->etiquetaProfesional($profesional);
        $this->mostrarSugerenciasProfesional = false;
        $this->resetValidation('profesionalSaludId');
    }

    public function crear(): void
    {
        $this->authorize('usuarios.crear');
        $this->resetFormulario();
        $this->mostrarFormulario = true;
    }

    public function editar(int $usuarioId): void
    {
        $this->authorize('usuarios.editar');

        $usuario = User::query()
            ->with(['profesionalSalud.especialidad', 'roles'])
            ->findOrFail($usuarioId);

        $this->usuarioId = $usuario->id;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->password = '';
        $this->activo = $usuario->activo;
        $this->tipoUsuario = $usuario->tipo_usuario ?: 'Administrativo';
        $this->profesionalSaludId = $usuario->profesional_salud_id;
        $this->busquedaProfesional = $usuario->profesionalSalud
            ? $this->etiquetaProfesional($usuario->profesionalSalud)
            : '';
        $this->mostrarSugerenciasProfesional = false;
        $this->rolesSeleccionados = $usuario->roles->pluck('name')->values()->all();
        $this->mostrarFormulario = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->authorize($this->usuarioId ? 'usuarios.editar' : 'usuarios.crear');

        $datos = $this->validate($this->reglas(), [], [
            'name' => 'nombre',
            'email' => 'correo electronico',
            'password' => 'contrasena',
            'tipoUsuario' => 'tipo de usuario',
            'profesionalSaludId' => 'profesional medico',
            'rolesSeleccionados' => 'roles',
        ]);

        $usuario = $this->usuarioId
            ? User::query()->findOrFail($this->usuarioId)
            : new User(['registrado_por' => Auth::id()]);

        $usuario->fill([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'tipo_usuario' => $datos['tipoUsuario'],
            'profesional_salud_id' => $this->requiereProfesionalSalud() ? $datos['profesionalSaludId'] : null,
            'activo' => $datos['activo'],
            'actualizado_por' => Auth::id(),
        ]);

        if (filled($datos['password'])) {
            $usuario->password = $datos['password'];
        }

        $usuario->save();
        $usuario->syncRoles($datos['rolesSeleccionados']);

        $this->mostrarFormulario = false;
        $this->resetFormulario();

        flash()->success('Usuario guardado correctamente.', [], 'Seguridad');
    }

    public function alternarActivo(int $usuarioId): void
    {
        $this->authorize('usuarios.cambiar_estado');

        $usuario = User::query()->findOrFail($usuarioId);

        if ($usuario->is(Auth::user())) {
            flash()->warning('No puedes inactivar tu propio usuario.', [], 'Seguridad');

            return;
        }

        if ($usuario->activo && $this->esUltimoAdministradorActivo($usuario)) {
            flash()->warning('Debe existir al menos un administrador activo.', [], 'Seguridad');

            return;
        }

        $usuario->forceFill([
            'activo' => ! $usuario->activo,
            'actualizado_por' => Auth::id(),
        ])->save();

        flash()->success('Estado del usuario actualizado.', [], 'Seguridad');
    }

    public function cancelar(): void
    {
        $this->mostrarFormulario = false;
        $this->resetFormulario();
    }

    public function limpiarBusqueda(): void
    {
        $this->busqueda = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.seguridad.usuarios-panel', [
            'usuarios' => $this->usuarios(),
            'profesionales' => $this->profesionalesDisponibles(),
            'requiereProfesionalSalud' => $this->requiereProfesionalSalud(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Reglas de validacion del formulario de usuarios.
     *
     * @return array<string, mixed>
     */
    private function reglas(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->usuarioId)],
            'password' => [$this->usuarioId ? 'nullable' : 'required', 'string', 'min:8'],
            'tipoUsuario' => ['required', Rule::in($this->tiposUsuario())],
            'profesionalSaludId' => [
                Rule::requiredIf($this->requiereProfesionalSalud()),
                'nullable',
                'integer',
                Rule::exists('profesionales_salud', 'id')->whereNull('deleted_at'),
            ],
            'activo' => ['boolean'],
            'rolesSeleccionados' => ['required', 'array', 'min:1'],
            'rolesSeleccionados.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    private function usuarios(): LengthAwarePaginator
    {
        return User::query()
            ->with(['profesionalSalud', 'roles'])
            ->when($this->busqueda !== '', function (Builder $consulta): void {
                $consulta->where(function (Builder $grupo): void {
                    $grupo
                        ->where('name', 'like', "%{$this->busqueda}%")
                        ->orWhere('email', 'like', "%{$this->busqueda}%");
                });
            })
            ->orderBy('name')
            ->paginate(8)
            ->withPath(route('seguridad.usuarios.index'))
            ->appends($this->parametrosPaginacion());
    }

    /**
     * Conserva los filtros activos en los enlaces del paginador.
     *
     * @return array<string, mixed>
     */
    private function parametrosPaginacion(): array
    {
        return collect([
            'busqueda' => $this->busqueda !== '' ? $this->busqueda : null,
        ])->filter(fn ($valor): bool => $valor !== null)->all();
    }

    private function resetFormulario(): void
    {
        $this->usuarioId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->activo = true;
        $this->tipoUsuario = 'Administrativo';
        $this->profesionalSaludId = null;
        $this->busquedaProfesional = '';
        $this->mostrarSugerenciasProfesional = false;
        $this->rolesSeleccionados = [];
        $this->resetValidation();
    }

    public function requiereProfesionalSalud(): bool
    {
        return in_array($this->tipoUsuario, ['Médico', 'Profesional de Salud'], true);
    }

    private function profesionalesDisponibles()
    {
        if (! $this->requiereProfesionalSalud() || ! $this->mostrarSugerenciasProfesional || trim($this->busquedaProfesional) === '') {
            return collect();
        }

        return ProfesionalSalud::query()
            ->with('especialidad')
            ->whereNull('deleted_at')
            ->where(function (Builder $consulta): void {
                $consulta->where(function (Builder $grupo): void {
                    $grupo
                        ->where('nombres', 'like', "%{$this->busquedaProfesional}%")
                        ->orWhere('cmp', 'like', "%{$this->busquedaProfesional}%")
                        ->orWhere('rne', 'like', "%{$this->busquedaProfesional}%")
                        ->orWhereHas('especialidad', function (Builder $especialidad): void {
                            $especialidad->where('nombre', 'like', "%{$this->busquedaProfesional}%");
                        });
                });
            })
            ->orderBy('nombres')
            ->limit(8)
            ->get(['id', 'nombres', 'cmp', 'rne', 'especialidad_id']);
    }

    private function etiquetaProfesional(ProfesionalSalud $profesional): string
    {
        return collect([
            $profesional->nombres,
            $profesional->cmp ? 'CMP '.$profesional->cmp : null,
            optional($profesional->especialidad)->nombre,
        ])->filter()->implode(' - ');
    }

    private function esUltimoAdministradorActivo(User $usuario): bool
    {
        if (! $usuario->hasRole('Administrador')) {
            return false;
        }

        return User::role('Administrador')
            ->where('activo', true)
            ->whereKeyNot($usuario->id)
            ->doesntExist();
    }
}
