<section class="catalog-page">
    <div class="catalog-heading panel-animate">
        <div>
            <p class="catalog-kicker">{{ $configuracion['kicker'] }}</p>
            <h1>{{ $configuracion['titulo'] }}</h1>
            <p>{{ $configuracion['descripcion'] }}</p>
        </div>
    </div>

    <div class="catalog-stats">
        <article class="catalog-stat-card hce-card-motion panel-animate">
            <span><iconify-icon icon="lucide:list-checks" aria-hidden="true"></iconify-icon></span>
            <div>
                <strong data-count-target="{{ $estadisticas['total'] }}">{{ $estadisticas['total'] }}</strong>
                <p>Total registros</p>
            </div>
        </article>
        <article class="catalog-stat-card hce-card-motion panel-animate">
            <span><iconify-icon icon="lucide:check-circle" aria-hidden="true"></iconify-icon></span>
            <div>
                <strong data-count-target="{{ $estadisticas['activos'] }}">{{ $estadisticas['activos'] }}</strong>
                <p>Activos</p>
            </div>
        </article>
        <article class="catalog-stat-card hce-card-motion panel-animate">
            <span><iconify-icon icon="lucide:pause-circle" aria-hidden="true"></iconify-icon></span>
            <div>
                <strong data-count-target="{{ $estadisticas['inactivos'] }}">{{ $estadisticas['inactivos'] }}</strong>
                <p>Inactivos</p>
            </div>
        </article>
        <article class="catalog-stat-card hce-card-motion panel-animate">
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
                    <h2>Gestion masiva de {{ mb_strtolower($configuracion['titulo']) }}</h2>
                </div>

                @can('catalogos.gestionar')
                    <button type="button" class="catalog-primary-action" wire:click="abrirNuevo">
                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                        Nuevo registro
                    </button>
                @endcan
            </div>

            <div class="catalog-admin-inline">
                <a href="{{ route('catalogos.'.$catalogo.'.exportar.pdf', ['busqueda' => $busqueda, 'estado' => $estado]) }}" class="catalog-secondary-action">
                    <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                    PDF
                </a>
                <a href="{{ route('catalogos.'.$catalogo.'.exportar.xlsx', ['busqueda' => $busqueda, 'estado' => $estado]) }}" class="catalog-secondary-action">
                    <iconify-icon icon="lucide:file-spreadsheet" aria-hidden="true"></iconify-icon>
                    XLSX
                </a>
                <a href="{{ route('catalogos.'.$catalogo.'.plantilla') }}" class="catalog-secondary-action">
                    <iconify-icon icon="lucide:download" aria-hidden="true"></iconify-icon>
                    Plantilla
                </a>

                <form wire:submit.prevent="importarDesdeExcel" class="catalog-import-form">
                    <label>
                        <span class="sr-only">Archivo Excel</span>
                        <input type="file" wire:model="archivoImportacion" accept=".xlsx" wire:loading.attr="disabled" wire:target="archivoImportacion,importarDesdeExcel">
                        @error('archivoImportacion') <small>{{ $message }}</small> @enderror
                    </label>

                    <button type="submit" class="catalog-primary-action" wire:loading.attr="disabled" wire:target="archivoImportacion,importarDesdeExcel">
                        <iconify-icon icon="lucide:upload" aria-hidden="true"></iconify-icon>
                        <span wire:loading.remove wire:target="archivoImportacion,importarDesdeExcel">Importar</span>
                        <span wire:loading wire:target="archivoImportacion,importarDesdeExcel">Importando...</span>
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
                <input type="search" wire:model.live.debounce.300ms="busqueda" placeholder="{{ $configuracion['placeholder'] }}">
            </div>

            <label class="catalog-filter catalog-filter-compact">
                <span>Mostrar</span>
                <select wire:model.live="porPagina">
                    @foreach ($opcionesPorPagina as $opcionPorPagina)
                        <option value="{{ $opcionPorPagina }}">{{ $opcionPorPagina }} registros</option>
                    @endforeach
                </select>
            </label>

            @if ($usaEstado)
                <label class="catalog-filter">
                    <span>Estado</span>
                    <select wire:model.live="estado">
                        @foreach ($estados as $estadoDisponible)
                            <option value="{{ $estadoDisponible }}">{{ $estadoDisponible }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <button type="button" class="catalog-secondary-action" wire:click="limpiarFiltros">
                <iconify-icon icon="lucide:rotate-ccw" aria-hidden="true"></iconify-icon>
                Limpiar
            </button>
        </div>

        <div class="catalog-table-wrap">
            <table class="catalog-table">
                <thead>
                    <tr>
                        @foreach ($configuracion['campos'] as $campo)
                            <th>{{ $campo['etiqueta'] }}</th>
                        @endforeach
                        @if ($usaEstado)
                            <th>Estado</th>
                        @endif
                        <th>Uso</th>
                        <th>Actualizado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr wire:key="{{ $catalogo }}-{{ $registro->id }}">
                            @foreach ($configuracion['campos'] as $campo)
                                <td>
                                    @if (($campo['nombre'] ?? '') === 'favorito')
                                        @php
                                            $esFavorito = filter_var(data_get($registro, 'favorito'), FILTER_VALIDATE_BOOLEAN);
                                        @endphp
                                        <span class="catalog-favorite-icon {{ $esFavorito ? 'is-favorite' : 'is-not-favorite' }}" title="{{ $esFavorito ? 'Favorito' : 'No favorito' }}" aria-label="{{ $esFavorito ? 'Favorito' : 'No favorito' }}">
                                            <iconify-icon
                                                icon="{{ $esFavorito ? 'material-symbols:star-rounded' : 'material-symbols:star-outline-rounded' }}"
                                                width="24"
                                                height="24"
                                                style="color: {{ $esFavorito ? '#facc15' : 'currentColor' }}; font-size: 24px; width: 24px; height: 24px;"
                                                aria-hidden="true"
                                            ></iconify-icon>
                                        </span>
                                    @elseif ($loop->first)
                                        <strong>{{ \Illuminate\Support\Str::limit($this->valorCampo($registro, $campo['nombre']), 70) }}</strong>
                                    @else
                                        {{ \Illuminate\Support\Str::limit($this->valorCampo($registro, $campo['nombre']), 70) }}
                                    @endif
                                </td>
                            @endforeach
                            @if ($usaEstado)
                                <td>
                                    <span class="catalog-status {{ $this->valorEstado($registro) === 'Activo' ? 'is-active' : 'is-inactive' }}">
                                        {{ $this->valorEstado($registro) }}
                                    </span>
                                </td>
                            @endif
                            <td>{{ $this->usoTotal($registro) }} {{ $configuracion['etiqueta_uso'] }}</td>
                            <td>{{ optional($registro->updated_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="catalog-actions">
                                    <button type="button" wire:click="ver({{ $registro->id }})" title="Ver" aria-label="Ver {{ $configuracion['singular'] }}">
                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                    </button>
                                    @can('catalogos.gestionar')
                                        <button type="button" wire:click="editar({{ $registro->id }})" title="Editar" aria-label="Editar {{ $configuracion['singular'] }}">
                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                        </button>
                                        @if ($usaEstado)
                                            <button type="button" wire:click="cambiarEstado({{ $registro->id }})" title="Cambiar estado" aria-label="Cambiar estado">
                                                <iconify-icon icon="{{ $this->valorEstado($registro) === 'Activo' ? 'lucide:pause-circle' : 'lucide:check-circle' }}" aria-hidden="true"></iconify-icon>
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            data-confirm-delete-catalogo
                                            data-registro-id="{{ $registro->id }}"
                                            data-registro-nombre="{{ $this->etiquetaRegistro($registro) }}"
                                            data-catalogo-singular="{{ $configuracion['singular'] }}"
                                            title="Eliminar"
                                            aria-label="Eliminar {{ $configuracion['singular'] }}"
                                        >
                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($configuracion['campos']) + ($usaEstado ? 4 : 3) }}" class="catalog-empty">
                                No se encontraron registros con los filtros actuales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="catalog-pagination">
            {{ $registros->links('pagination.catalogos') }}
        </div>
    </article>

    @if ($modalAbierto)
        <div class="catalog-modal-backdrop">
            <section class="catalog-modal {{ $catalogo === 'signos-vitales' ? 'catalog-vital-signs-modal' : '' }}" role="dialog" aria-modal="true" aria-labelledby="catalogo-modal-title">
                <header>
                    <div>
                        <p>{{ $modoFormulario === 'crear' ? 'Nuevo registro' : ($modoFormulario === 'editar' ? 'Editar registro' : 'Vista de registro') }}</p>
                        <h2 id="catalogo-modal-title">{{ $configuracion['titulo'] }}</h2>
                    </div>
                    <button type="button" wire:click="cerrarModal" aria-label="Cerrar modal">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </header>

                <form wire:submit.prevent="guardar" class="catalog-form">
                    @php
                        $seccionesFormulario = collect($configuracion['secciones_formulario'] ?? []);
                        $camposEnSecciones = $seccionesFormulario->flatMap(fn (array $seccion): array => $seccion['campos'])->all();
                    @endphp

                    @if ($catalogo === 'signos-vitales')
                        <div class="catalog-vital-signs-grid">
                            <section class="catalog-form-section catalog-vital-signs-card">
                                <div class="catalog-form-section-header">
                                    <span>
                                        <iconify-icon icon="lucide:clipboard-list" aria-hidden="true"></iconify-icon>
                                    </span>
                                    <div>
                                        <h3>Informacion General</h3>
                                    </div>
                                </div>

                                <div class="catalog-form-section-fields">
                                    @foreach (collect($configuracion['campos'])->whereIn('nombre', ['descripcion', 'unidad', 'favorito']) as $campo)
                                        @include('livewire.catalogos.partials.campo-formulario', ['campo' => $campo, 'modoFormulario' => $modoFormulario])
                                    @endforeach

                                    @if ($usaEstado)
                                        <label>
                                            <span>Estado</span>
                                            <select wire:model="estadoRegistro" @disabled($modoFormulario === 'ver')>
                                                <option value="Activo">Activo</option>
                                                <option value="Inactivo">Inactivo</option>
                                            </select>
                                            @error('estadoRegistro') <small>{{ $message }}</small> @enderror
                                        </label>
                                    @endif
                                </div>
                            </section>

                            <section class="catalog-form-section catalog-vital-signs-card">
                                <div class="catalog-form-section-header">
                                    <span>
                                        <iconify-icon icon="lucide:sliders-horizontal" aria-hidden="true"></iconify-icon>
                                    </span>
                                    <div>
                                        <h3>Configuracion de Rangos</h3>
                                        <p>Define el limite inicial y final que se usara como referencia clinica del signo vital.</p>
                                    </div>
                                </div>

                                <div class="catalog-form-section-fields">
                                    @foreach (collect($configuracion['campos'])->whereIn('nombre', ['rango_inicial', 'rango_final']) as $campo)
                                        @include('livewire.catalogos.partials.campo-formulario', ['campo' => $campo, 'modoFormulario' => $modoFormulario])
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    @else
                        @foreach (collect($configuracion['campos'])->reject(fn (array $campo): bool => in_array($campo['nombre'], $camposEnSecciones, true)) as $campo)
                            @include('livewire.catalogos.partials.campo-formulario', ['campo' => $campo, 'modoFormulario' => $modoFormulario])
                        @endforeach

                        @foreach ($seccionesFormulario as $seccion)
                            <section class="catalog-form-section">
                                <div class="catalog-form-section-header">
                                    <span>
                                        <iconify-icon icon="{{ $seccion['icono'] ?? 'lucide:sliders-horizontal' }}" aria-hidden="true"></iconify-icon>
                                    </span>
                                    <div>
                                        <h3>{{ $seccion['titulo'] }}</h3>
                                        <p>{{ $seccion['descripcion'] }}</p>
                                    </div>
                                </div>

                                <div class="catalog-form-section-grid">
                                    @foreach (collect($configuracion['campos'])->whereIn('nombre', $seccion['campos']) as $campo)
                                        @include('livewire.catalogos.partials.campo-formulario', ['campo' => $campo, 'modoFormulario' => $modoFormulario])
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    @endif

                    @if ($usaEstado && $catalogo !== 'signos-vitales')
                        <label>
                            <span>Estado</span>
                            <select wire:model="estadoRegistro" @disabled($modoFormulario === 'ver')>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                            @error('estadoRegistro') <small>{{ $message }}</small> @enderror
                        </label>
                    @endif

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
