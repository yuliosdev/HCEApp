<section class="open-histories-page patient-history-page">
    <header class="patient-history-header open-histories-header panel-animate">
        <div>
            <p class="patient-history-kicker">Historia clinica</p>
            <h1>Historial de Atenciones</h1>
            <span>Seguimiento operativo de historias clinicas de emergencia abiertas.</span>
        </div>

        <nav class="patient-history-actions" aria-label="Acciones de historias clinicas">
            <a
                href="{{ route('historia-clinica.historias-abiertas.exportar.pdf', $this->parametrosExportacion()) }}"
                class="patient-history-secondary"
                target="_blank"
                rel="noopener"
                title="Exportar historial a PDF"
            >
                <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                PDF
            </a>
            <a
                href="{{ route('historia-clinica.historias-abiertas.exportar.xlsx', $this->parametrosExportacion()) }}"
                class="patient-history-secondary"
                title="Exportar historial a Excel"
            >
                <iconify-icon icon="lucide:file-spreadsheet" aria-hidden="true"></iconify-icon>
                Excel
            </a>
            <button type="button" class="catalog-primary-action" wire:click="crearNuevaHistoria" title="Crear nueva historia">
                <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                Crear Nueva Historia
            </button>
        </nav>
    </header>

    <section class="open-histories-filters panel-animate" style="--panel-delay: 80ms" aria-label="Filtros de historias clinicas">
        <label>
            <span>Fecha desde</span>
            <input type="date" wire:model.live="fechaDesde">
        </label>
        <label>
            <span>Fecha hasta</span>
            <input type="date" wire:model.live="fechaHasta">
        </label>
        <label>
            <span>Estado</span>
            <select wire:model.live="estado">
                @foreach ($opcionesEstado as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
        </label>
        <button type="button" class="patient-history-secondary" wire:click="limpiarFiltros">
            <iconify-icon icon="lucide:rotate-ccw" aria-hidden="true"></iconify-icon>
            Limpiar
        </button>
    </section>

    <section class="open-histories-stats" aria-label="Estadisticas de historias clinicas">
        <article class="patient-dashboard-card hce-card-motion open-history-stat is-total panel-animate" style="--panel-delay: 120ms">
            <span><iconify-icon icon="lucide:clipboard-list" aria-hidden="true"></iconify-icon></span>
            <div>
                <p>Total</p>
                <strong>{{ $estadisticas['total'] }}</strong>
            </div>
        </article>
        <article class="patient-dashboard-card hce-card-motion open-history-stat is-open panel-animate" style="--panel-delay: 160ms">
            <span><iconify-icon icon="lucide:activity" aria-hidden="true"></iconify-icon></span>
            <div>
                <p>Abiertas</p>
                <strong>{{ $estadisticas['abiertas'] }}</strong>
            </div>
        </article>
        <article class="patient-dashboard-card hce-card-motion open-history-stat is-closed panel-animate" style="--panel-delay: 200ms">
            <span><iconify-icon icon="lucide:lock" aria-hidden="true"></iconify-icon></span>
            <div>
                <p>Cerradas</p>
                <strong>{{ $estadisticas['cerradas'] }}</strong>
            </div>
        </article>
        <article class="patient-dashboard-card hce-card-motion open-history-stat is-triage panel-animate" style="--panel-delay: 240ms">
            <span><iconify-icon icon="lucide:badge-check" aria-hidden="true"></iconify-icon></span>
            <div>
                <p>Con triaje</p>
                <strong>{{ $estadisticas['triaje'] }}</strong>
            </div>
        </article>
    </section>

    <article class="patient-history-panel open-histories-panel panel-animate" style="--panel-delay: 280ms">
        <header class="patient-history-panel-header">
            <div>
                <p>Resultado de busqueda</p>
                <h2>Historias clinicas</h2>
            </div>

            <label>
                Registros por pagina
                <select wire:model.live="porPagina">
                    @foreach ($opcionesPorPagina as $opcion)
                        <option value="{{ $opcion }}">{{ $opcion }}</option>
                    @endforeach
                </select>
            </label>
        </header>

        <div class="patient-history-loading" wire:loading.delay>
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="patient-history-table-wrap">
            <table class="patient-history-table open-histories-table">
                <thead>
                    <tr>
                        <th>Fecha / Hora Ingreso</th>
                        <th>Paciente</th>
                        <th>Tipo</th>
                        <th>N° Atencion</th>
                        <th>Profesional</th>
                        <th>Ambiente</th>
                        <th>DX Principal</th>
                        <th>Triaje</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($atenciones->isEmpty())
                        <tr>
                            <td colspan="10" class="patient-history-empty">
                                <iconify-icon icon="lucide:clipboard-x" aria-hidden="true"></iconify-icon>
                                No se encontraron historias clinicas para los filtros seleccionados.
                            </td>
                        </tr>
                    @endif

                    @foreach ($atenciones as $atencion)
                        @php($diagnostico = $this->diagnosticoPrincipal($atencion))
                        @php($triaje = $this->datosTriaje($atencion))
                        @php($abierta = $this->estadoAbierto($atencion))
                        <tr wire:key="historia-abierta-{{ $atencion->id }}">
                            <td>
                                <strong>{{ $atencion->fecha_hora_atencion?->format('d/m/Y') ?? '--' }}</strong>
                                <span>{{ $atencion->fecha_hora_atencion?->format('h:i A') ?? '--' }}</span>
                            </td>
                            <td>
                                <div class="patient-history-patient-name">
                                    <strong>{{ $atencion->paciente?->nombre ?? $atencion->alias ?? '--' }}</strong>
                                    @if ($atencion->paciente_id === null && filled($atencion->alias))
                                        <span>Sin Registro</span>
                                    @endif
                                </div>
                                <span>{{ $atencion->paciente?->id_paciente_externo ? 'HC: '.$atencion->paciente->id_paciente_externo : 'HC: --' }}</span>
                            </td>
                            <td>
                                <span class="patient-history-type">Emergencia</span>
                            </td>
                            <td>
                                <div class="patient-history-attention-number">
                                    <strong class="patient-history-number">{{ $atencion->id }}</strong>
                                    <span>Adm: {{ $atencion->numero_atencion ?: '--' }}</span>
                                </div>
                            </td>
                            <td>
                                @if ($atencion->profesionalResponsable)
                                    <div class="patient-history-professional">
                                        <span>{{ collect(explode(' ', $atencion->profesionalResponsable->nombres))->filter()->take(2)->map(fn ($parte) => mb_substr($parte, 0, 1))->implode('') }}</span>
                                        <strong>{{ $atencion->profesionalResponsable->nombres }}</strong>
                                    </div>
                                @else
                                    <span class="patient-history-muted">--</span>
                                @endif
                            </td>
                            <td>
                                <span>{{ $atencion->destino_topico ?: $atencion->ambiente?->denominacion ?: '--' }}</span>
                            </td>
                            <td>
                                <span class="{{ $diagnostico ? '' : 'patient-history-muted is-italic' }}">
                                    {{ $this->textoDiagnostico($diagnostico) }}
                                </span>
                            </td>
                            <td>
                                <div class="patient-history-triage">
                                    <span class="patient-history-triage-badge" style="--triage-bg: {{ $triaje['fondo'] }}; --triage-text: {{ $triaje['color'] }}">
                                        {{ $triaje['texto'] }}
                                    </span>
                                    <span class="patient-history-check {{ $atencion->triaje ? 'is-checked' : '' }}" aria-label="{{ $atencion->triaje ? 'Triaje realizado' : 'Triaje pendiente' }}">
                                        @if ($atencion->triaje)
                                            <iconify-icon icon="lucide:check" aria-hidden="true"></iconify-icon>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="patient-history-status {{ $abierta ? 'is-open' : 'is-closed' }}">
                                    <i></i>
                                    {{ $abierta ? 'Abierta' : 'Cerrada' }}
                                </span>
                            </td>
                            <td>
                                <nav class="patient-history-row-actions" aria-label="Acciones de atencion {{ $atencion->numero_atencion ?: $atencion->id }}">
                                    @if ($this->puedeEditarAtencion($atencion))
                                        <button type="button" wire:click="editarAtencion({{ $atencion->id }})" aria-label="Editar atencion" title="Editar atencion">
                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="verAtencion({{ $atencion->id }})" aria-label="Ver atencion" title="Ver atencion">
                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                    </button>
                                    <a
                                        href="{{ $atencion->paciente ? route('historia-clinica.atenciones-paciente.emergencia.imprimir', [$atencion->paciente, $atencion]) : route('historia-clinica.emergencia.imprimir', $atencion) }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Imprimir historia clinica"
                                        title="Imprimir historia clinica"
                                    >
                                        <iconify-icon icon="lucide:printer" aria-hidden="true"></iconify-icon>
                                    </a>
                                    <a
                                        href="{{ $atencion->paciente ? route('historia-clinica.atenciones-paciente.emergencia.ticket', [$atencion->paciente, $atencion]) : route('historia-clinica.emergencia.ticket', $atencion) }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Ticket de historia"
                                        title="Ticket de historia"
                                    >
                                        <iconify-icon icon="lucide:ticket-check" aria-hidden="true"></iconify-icon>
                                    </a>
                                </nav>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="catalog-pagination patient-history-pagination">
            {{ $atenciones->links('pagination.catalogos') }}
        </div>
    </article>

    @if ($modalNuevaHistoriaAbierto)
        <div class="catalog-modal-backdrop open-history-create-backdrop" role="presentation">
            <section
                class="catalog-modal patient-history-modal open-history-create-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="open-history-create-title"
            >
                <header>
                    <div>
                        <p>Nueva historia clinica</p>
                        <h2 id="open-history-create-title">
                            <iconify-icon icon="lucide:clipboard-plus" aria-hidden="true"></iconify-icon>
                            Crear Historia
                        </h2>
                    </div>
                </header>

                <form class="open-history-create-form" wire:submit.prevent="crearHistoriaDesdeModal">
                    <label class="open-history-autocomplete">
                        <span>Profesional</span>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="profesionalBusqueda"
                            placeholder="Buscar profesional por nombre, especialidad o CMP"
                            autocomplete="off"
                        >
                        @if ($profesionalesNuevaHistoria->isNotEmpty())
                            <div class="open-history-autocomplete-list">
                                @foreach ($profesionalesNuevaHistoria as $profesional)
                                    <button type="button" wire:click="seleccionarProfesional({{ $profesional->id }})">
                                        <strong>{{ $profesional->nombres }}</strong>
                                        <span>{{ $profesional->especialidad?->nombre ?: 'Sin especialidad' }}{{ $profesional->cmp ? ' · CMP '.$profesional->cmp : '' }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @error('nuevaHistoriaForm.profesional_salud_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <div class="open-history-patient-fields">
                        <label class="open-history-autocomplete">
                            <span>Paciente</span>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="pacienteBusqueda"
                                placeholder="Buscar paciente por nombre, HC o documento"
                                autocomplete="off"
                            >
                            @if ($pacientesNuevaHistoria->isNotEmpty())
                                <div class="open-history-autocomplete-list">
                                    @foreach ($pacientesNuevaHistoria as $pacienteItem)
                                        <button type="button" wire:click="seleccionarPaciente({{ $pacienteItem->id }})">
                                            <strong>{{ $pacienteItem->nombre }}</strong>
                                            <span>HC: {{ $pacienteItem->numero_historia ?: '--' }} · Doc: {{ $pacienteItem->numero_documento ?: '--' }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('nuevaHistoriaForm.paciente_id') <small>{{ $message }}</small> @enderror
                        </label>

                        <label>
                            <span>Alias</span>
                            <input
                                type="text"
                                wire:model.blur="nuevaHistoriaForm.alias"
                                placeholder="Alias si aun no se asigno paciente"
                                maxlength="100"
                            >
                            @error('nuevaHistoriaForm.alias') <small>{{ $message }}</small> @enderror
                        </label>

                        <label>
                            <span>Tipo de emergencia</span>
                            <select wire:model="nuevaHistoriaForm.tipo">
                                @foreach ($tiposEmergencia as $tipoEmergencia)
                                    <option value="{{ $tipoEmergencia }}">{{ $tipoEmergencia }}</option>
                                @endforeach
                            </select>
                            @error('nuevaHistoriaForm.tipo') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>

                    <footer>
                        <button type="button" class="patient-history-secondary" wire:click="cerrarModalNuevaHistoria">
                            Cerrar
                        </button>
                        <button type="submit" class="catalog-primary-action">
                            <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                            Crear Historia
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    @endif
</section>
