<section class="catalog-page">
    <div class="catalog-heading panel-animate">
        <div>
            <p class="catalog-kicker">Catalogos clinicos</p>
            <h1>Procedimientos</h1>
            <p>Administra codigos y denominaciones usados en los procedimientos realizados durante la atencion de emergencia.</p>
        </div>

    </div>

    <div class="catalog-stats">
        <article class="catalog-stat-card panel-animate">
            <span><iconify-icon icon="lucide:list-checks" aria-hidden="true"></iconify-icon></span>
            <div>
                <strong data-count-target="{{ $estadisticas['total'] }}">{{ $estadisticas['total'] }}</strong>
                <p>Total registros</p>
            </div>
        </article>
        <article class="catalog-stat-card panel-animate">
            <span><iconify-icon icon="lucide:check-circle" aria-hidden="true"></iconify-icon></span>
            <div>
                <strong data-count-target="{{ $estadisticas['activos'] }}">{{ $estadisticas['activos'] }}</strong>
                <p>Activos</p>
            </div>
        </article>
        <article class="catalog-stat-card panel-animate">
            <span><iconify-icon icon="lucide:pause-circle" aria-hidden="true"></iconify-icon></span>
            <div>
                <strong data-count-target="{{ $estadisticas['inactivos'] }}">{{ $estadisticas['inactivos'] }}</strong>
                <p>Inactivos</p>
            </div>
        </article>
        <article class="catalog-stat-card panel-animate">
            <span><iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon></span>
            <div>
                <strong data-count-target="{{ $estadisticas['eliminados'] }}">{{ $estadisticas['eliminados'] }}</strong>
                <p>Eliminados</p>
            </div>
        </article>
    </div>

    @if ($esAdministrador)
        <article class="catalog-admin-tools panel-animate">
            <div class="catalog-admin-header">
                <div>
                    <p class="catalog-kicker">Herramientas administrativas</p>
                    <h2>Gestion masiva de procedimientos</h2>
                </div>

                @can('catalogos.gestionar')
                    <button type="button" class="catalog-primary-action" wire:click="abrirNuevo">
                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                        Nuevo registro
                    </button>
                @endcan
            </div>

            <div class="catalog-admin-inline">
                <a href="{{ route('catalogos.procedimientos.exportar.pdf', ['busqueda' => $busqueda, 'estado' => $estado]) }}" class="catalog-secondary-action">
                    <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                    PDF
                </a>
                <a href="{{ route('catalogos.procedimientos.exportar.xlsx', ['busqueda' => $busqueda, 'estado' => $estado]) }}" class="catalog-secondary-action">
                    <iconify-icon icon="lucide:file-spreadsheet" aria-hidden="true"></iconify-icon>
                    XLSX
                </a>
                <a href="{{ route('catalogos.procedimientos.plantilla') }}" class="catalog-secondary-action">
                    <iconify-icon icon="lucide:download" aria-hidden="true"></iconify-icon>
                    Plantilla
                </a>

                <form wire:submit.prevent="importarDesdeExcel" class="catalog-import-form">
                    <label>
                        <span class="sr-only">Archivo Excel</span>
                        <input type="file" wire:model="archivoImportacion" accept=".xlsx">
                        @error('archivoImportacion') <small>{{ $message }}</small> @enderror
                    </label>

                    <button type="submit" class="catalog-primary-action" wire:loading.attr="disabled" wire:target="archivoImportacion,importarDesdeExcel">
                        <iconify-icon icon="lucide:upload" aria-hidden="true"></iconify-icon>
                        Importar
                    </button>
                </form>
            </div>
        </article>
    @else
        @can('catalogos.gestionar')
            <div class="catalog-standalone-action panel-animate">
                <button type="button" class="catalog-primary-action" wire:click="abrirNuevo">
                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                    Nuevo registro
                </button>
            </div>
        @endcan
    @endif

    <article class="catalog-panel panel-animate">
        <div class="catalog-toolbar">
            <div class="catalog-search">
                <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                <input type="search" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por codigo o denominacion">
            </div>

            <label class="catalog-filter catalog-filter-compact">
                <span>Mostrar</span>
                <select wire:model.live="porPagina">
                    @foreach ($opcionesPorPagina as $opcionPorPagina)
                        <option value="{{ $opcionPorPagina }}">{{ $opcionPorPagina }} registros</option>
                    @endforeach
                </select>
            </label>

            <label class="catalog-filter">
                <span>Estado</span>
                <select wire:model.live="estado">
                    @foreach ($estados as $estadoDisponible)
                        <option value="{{ $estadoDisponible }}">{{ $estadoDisponible }}</option>
                    @endforeach
                </select>
            </label>

            <button type="button" class="catalog-secondary-action" wire:click="limpiarFiltros">
                <iconify-icon icon="lucide:rotate-ccw" aria-hidden="true"></iconify-icon>
                Limpiar
            </button>
        </div>

        <div class="catalog-table-wrap">
            <table class="catalog-table">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Denominacion</th>
                        <th>Estado</th>
                        <th>Uso</th>
                        <th>Actualizado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($procedimientos as $procedimiento)
                        <tr wire:key="procedimiento-{{ $procedimiento->id }}">
                            <td><strong>{{ $procedimiento->codigo }}</strong></td>
                            <td>{{ $procedimiento->denominacion }}</td>
                            <td>
                                <span class="catalog-status {{ $procedimiento->estado === 'Activo' ? 'is-active' : 'is-inactive' }}">
                                    {{ $procedimiento->estado }}
                                </span>
                            </td>
                            <td>{{ $procedimiento->procedimientos_realizados_count }} atenciones</td>
                            <td>{{ optional($procedimiento->updated_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="catalog-actions">
                                    <button type="button" wire:click="ver({{ $procedimiento->id }})" title="Ver" aria-label="Ver procedimiento">
                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                    </button>
                                    @can('catalogos.gestionar')
                                        <button type="button" wire:click="editar({{ $procedimiento->id }})" title="Editar" aria-label="Editar procedimiento">
                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                        </button>
                                        <button type="button" wire:click="cambiarEstado({{ $procedimiento->id }})" title="Cambiar estado" aria-label="Cambiar estado">
                                            <iconify-icon icon="{{ $procedimiento->estado === 'Activo' ? 'lucide:pause-circle' : 'lucide:check-circle' }}" aria-hidden="true"></iconify-icon>
                                        </button>
                                        <button
                                            type="button"
                                            data-confirm-delete-procedimiento
                                            data-procedimiento-id="{{ $procedimiento->id }}"
                                            data-procedimiento-nombre="{{ $procedimiento->denominacion }}"
                                            title="Eliminar"
                                            aria-label="Eliminar procedimiento"
                                        >
                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="catalog-empty">No se encontraron procedimientos con los filtros actuales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="catalog-pagination">
            {{ $procedimientos->links('pagination.catalogos') }}
        </div>
    </article>

    @if ($modalAbierto)
        <div class="catalog-modal-backdrop" wire:click.self="cerrarModal">
            <section class="catalog-modal" role="dialog" aria-modal="true" aria-labelledby="procedimiento-modal-title">
                <header>
                    <div>
                        <p>{{ $modoFormulario === 'crear' ? 'Nuevo registro' : ($modoFormulario === 'editar' ? 'Editar registro' : 'Vista de registro') }}</p>
                        <h2 id="procedimiento-modal-title">Procedimiento</h2>
                    </div>
                    <button type="button" wire:click="cerrarModal" aria-label="Cerrar modal">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </header>

                <form wire:submit.prevent="guardar" class="catalog-form">
                    <label>
                        <span>Codigo</span>
                        <input type="text" wire:model.blur="codigo" @readonly($modoFormulario === 'ver')>
                        @error('codigo') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Denominacion</span>
                        <textarea wire:model.blur="denominacion" rows="4" @readonly($modoFormulario === 'ver')></textarea>
                        @error('denominacion') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Estado</span>
                        <select wire:model="estadoRegistro" @disabled($modoFormulario === 'ver')>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                        @error('estadoRegistro') <small>{{ $message }}</small> @enderror
                    </label>

                    @if ($esAdministrador && $auditoria !== [])
                        <section class="catalog-audit-box">
                            <h3>Auditoria del registro</h3>
                            <dl>
                                <div>
                                    <dt>Registrado por</dt>
                                    <dd>{{ $auditoria['registrado_por'] }}</dd>
                                    @if ($auditoria['registrado_email'] !== '')
                                        <dd>{{ $auditoria['registrado_email'] }}</dd>
                                    @endif
                                    <dd>{{ $auditoria['fecha_registro'] }}</dd>
                                </div>
                                <div>
                                    <dt>Actualizado por</dt>
                                    <dd>{{ $auditoria['actualizado_por'] }}</dd>
                                    @if ($auditoria['actualizado_email'] !== '')
                                        <dd>{{ $auditoria['actualizado_email'] }}</dd>
                                    @endif
                                    <dd>{{ $auditoria['fecha_actualizacion'] }}</dd>
                                </div>
                            </dl>
                        </section>
                    @endif

                    <footer>
                        <button type="button" wire:click="cerrarModal">Cerrar</button>
                        @if ($modoFormulario !== 'ver')
                            <button type="submit">
                                <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                Guardar
                            </button>
                        @endif
                    </footer>
                </form>
            </section>
        </div>
    @endif
</section>
