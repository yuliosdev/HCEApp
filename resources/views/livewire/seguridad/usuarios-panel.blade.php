<section class="security-page">
    <div class="security-heading panel-animate">
        <div>
            <p class="security-kicker">Fase 8 - Seguridad</p>
            <h1>Usuarios del sistema</h1>
            <p>Administra accesos, estado operativo y roles asignados a cada usuario.</p>
        </div>

        @can('usuarios.crear')
            <button type="button" class="security-primary-action" wire:click="crear">
                <iconify-icon icon="lucide:user-plus" aria-hidden="true"></iconify-icon>
                Nuevo usuario
            </button>
        @endcan
    </div>

    <div class="security-grid">
        <article class="security-panel panel-animate">
            <div class="security-panel-header">
                <div>
                    <h2>Listado de usuarios</h2>
                    <p>{{ $usuarios->total() }} usuarios registrados</p>
                </div>
                <div class="security-search">
                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                    <input type="search" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por nombre o correo">
                    @if ($busqueda !== '')
                        <button type="button" wire:click="limpiarBusqueda" aria-label="Limpiar busqueda">
                            <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                        </button>
                    @endif
                </div>
            </div>

            <div class="security-table-wrap">
                <table class="security-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Actualizado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usuarios as $usuario)
                            <tr wire:key="usuario-{{ $usuario->id }}">
                                <td>
                                    <strong>{{ $usuario->name }}</strong>
                                    <span>{{ $usuario->email }}</span>
                                </td>
                                <td>
                                    <div class="security-badges">
                                        @forelse ($usuario->roles as $rol)
                                            <span>{{ $rol->name }}</span>
                                        @empty
                                            <em>Sin rol</em>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span class="security-status {{ $usuario->activo ? 'is-active' : 'is-inactive' }}">
                                        {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>{{ optional($usuario->updated_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="security-actions">
                                        @can('usuarios.editar')
                                            <button type="button" wire:click="editar({{ $usuario->id }})" title="Editar usuario" aria-label="Editar usuario">
                                                <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                            </button>
                                        @endcan

                                        @can('usuarios.cambiar_estado')
                                            <button type="button" wire:click="alternarActivo({{ $usuario->id }})" title="Cambiar estado" aria-label="Cambiar estado">
                                                <iconify-icon icon="{{ $usuario->activo ? 'lucide:user-x' : 'lucide:user-check' }}" aria-hidden="true"></iconify-icon>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="security-empty">No se encontraron usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="security-pagination">
                {{ $usuarios->links() }}
            </div>
        </article>

        @if ($mostrarFormulario)
            <aside class="security-panel security-form-panel panel-animate">
                <div class="security-panel-header">
                    <div>
                        <h2>{{ $usuarioId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                        <p>Los roles determinan los permisos efectivos del usuario.</p>
                    </div>
                    <button type="button" class="security-close" wire:click="cancelar" aria-label="Cerrar formulario">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </div>

                <form wire:submit.prevent="guardar" class="security-form">
                    <label>
                        <span>Nombre</span>
                        <input type="text" wire:model.blur="name" autocomplete="name">
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Correo electronico</span>
                        <input type="email" wire:model.blur="email" autocomplete="email">
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>{{ $usuarioId ? 'Nueva contrasena' : 'Contrasena inicial' }}</span>
                        <input type="password" wire:model.blur="password" autocomplete="new-password">
                        @error('password') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="security-check">
                        <input type="checkbox" wire:model="activo">
                        <span>Usuario activo</span>
                    </label>

                    <div class="security-fieldset">
                        <span>Roles asignados</span>
                        <div class="security-check-grid">
                            @foreach ($roles as $rol)
                                <label wire:key="rol-disponible-{{ $rol->id }}">
                                    <input type="checkbox" wire:model="rolesSeleccionados" value="{{ $rol->name }}">
                                    <span>{{ $rol->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('rolesSeleccionados') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="security-form-actions">
                        <button type="button" wire:click="cancelar">Cancelar</button>
                        <button type="submit">
                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                            Guardar
                        </button>
                    </div>
                </form>
            </aside>
        @endif
    </div>
</section>
