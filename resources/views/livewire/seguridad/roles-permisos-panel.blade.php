<section class="security-page">
    <div class="security-heading panel-animate">
        <div>
            <p class="security-kicker">Fase 8 - Seguridad</p>
            <h1>Roles y permisos</h1>
            <p>Define que puede hacer cada perfil dentro de los modulos de HCEApp.</p>
        </div>

        @can('roles.crear')
            <button type="button" class="security-primary-action" wire:click="crear">
                <iconify-icon icon="lucide:shield-plus" aria-hidden="true"></iconify-icon>
                Nuevo rol
            </button>
        @endcan
    </div>

    <div class="security-grid">
        <article class="security-panel panel-animate">
            <div class="security-panel-header">
                <div>
                    <h2>Roles configurados</h2>
                    <p>{{ $roles->count() }} roles disponibles</p>
                </div>
                <div class="security-search">
                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                    <input type="search" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar rol">
                </div>
            </div>

            <div class="security-role-list">
                @forelse ($roles as $rol)
                    <button type="button" wire:key="rol-{{ $rol->id }}" wire:click="editar({{ $rol->id }})" class="{{ $rolId === $rol->id ? 'is-selected' : '' }}">
                        <span>
                            <strong>{{ $rol->name }}</strong>
                            <em>{{ $rol->permissions->count() }} permisos asignados</em>
                        </span>
                        <iconify-icon icon="lucide:chevron-right" aria-hidden="true"></iconify-icon>
                    </button>
                @empty
                    <p class="security-empty">No se encontraron roles.</p>
                @endforelse
            </div>
        </article>

        @if ($mostrarFormulario)
            <aside class="security-panel security-form-panel panel-animate">
                <div class="security-panel-header">
                    <div>
                        <h2>{{ $rolId ? 'Editar rol' : 'Nuevo rol' }}</h2>
                        <p>Los permisos marcados se aplican a todos los usuarios con este rol.</p>
                    </div>
                    <button type="button" class="security-close" wire:click="cancelar" aria-label="Cerrar formulario">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </div>

                <form wire:submit.prevent="guardar" class="security-form">
                    <label>
                        <span>Nombre del rol</span>
                        <input type="text" wire:model.blur="name">
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>

                    <div class="security-permission-groups">
                        @foreach ($definiciones as $grupo)
                            <section wire:key="grupo-permisos-{{ str($grupo['grupo'])->slug() }}">
                                <div>
                                    <h3>{{ $grupo['grupo'] }}</h3>
                                    <button type="button" wire:click="seleccionarGrupo('{{ $grupo['grupo'] }}')">Marcar grupo</button>
                                </div>

                                <div class="security-check-grid">
                                    @foreach ($grupo['permisos'] as $permiso)
                                        <label wire:key="permiso-{{ $permiso['nombre'] }}">
                                            <input type="checkbox" wire:model="permisosSeleccionados" value="{{ $permiso['nombre'] }}" @disabled($name === 'Administrador')>
                                            <span>
                                                <strong>{{ $permiso['descripcion'] }}</strong>
                                                <em>{{ $permiso['nombre'] }}</em>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    @error('permisosSeleccionados') <small>{{ $message }}</small> @enderror

                    <div class="security-form-actions">
                        <button type="button" wire:click="limpiarPermisos" @disabled($name === 'Administrador')>Limpiar</button>
                        <button type="submit">
                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                            Guardar
                        </button>
                    </div>
                </form>
            </aside>
        @else
            <aside class="security-panel security-guide panel-animate">
                <iconify-icon icon="lucide:shield-check" aria-hidden="true"></iconify-icon>
                <h2>Selecciona un rol</h2>
                <p>Desde aqui podras revisar y ajustar los permisos asignados a cada perfil operativo.</p>
            </aside>
        @endif
    </div>
</section>
