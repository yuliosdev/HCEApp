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
                        @can(\App\Support\Seguridad\Permisos::OBSERVACIONES_HCE)
                            <th>Auditoría</th>
                        @endcan
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($atenciones->isEmpty())
                        <tr>
                            <td colspan="{{ auth()->user()?->can(\App\Support\Seguridad\Permisos::OBSERVACIONES_HCE) ? 11 : 10 }}" class="patient-history-empty">
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
                            @can(\App\Support\Seguridad\Permisos::OBSERVACIONES_HCE)
                                @php($auditoria = $atencion->auditoriaActual)
                                @php($estadoAuditoria = $auditoria?->estado ?: 'Pendiente')
                                <td>
                                    <button
                                        type="button"
                                        class="patient-history-audit-button is-{{ strtolower($estadoAuditoria) }}"
                                        wire:click="abrirAuditoria({{ $atencion->id }})"
                                        title="Auditar HCE ({{ $estadoAuditoria }})"
                                    >
                                        <iconify-icon icon="lucide:clipboard-check" aria-hidden="true"></iconify-icon>
                                        Auditar HCE <span>({{ $estadoAuditoria }})</span>
                                    </button>
                                </td>
                            @endcan
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

    @if ($modalAuditoriaAbierto && $auditoriaAtencionId)
        @php($atencionAuditoria = $atenciones->getCollection()->firstWhere('id', $auditoriaAtencionId) ?: \App\Models\HcEmergencia::with(['paciente', 'profesionalResponsable', 'profesionalEgreso'])->find($auditoriaAtencionId))
        <div class="catalog-modal-backdrop open-history-audit-backdrop" role="presentation">
            <section class="catalog-modal patient-history-modal open-history-audit-modal" role="dialog" aria-modal="true" aria-labelledby="audit-hce-title">
                <header>
                    <div>
                        <p>Control de calidad clínica</p>
                        <h2 id="audit-hce-title">
                            <iconify-icon icon="lucide:clipboard-check" aria-hidden="true"></iconify-icon>
                            Auditar HCE
                        </h2>
                    </div>
                    <button type="button" class="emergency-close-icon-action" wire:click="cerrarModalAuditoria" title="Cerrar" aria-label="Cerrar">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </header>

                @if ($atencionAuditoria)
                    <form class="open-history-audit-form" wire:submit.prevent="guardarAuditoria">
                        <div class="open-history-audit-scroll">
                            <div class="open-history-audit-accordions">
                            <details class="open-history-audit-section">
                                <summary>
                                    <span><strong>Datos de la historia</strong><small>{{ $atencionAuditoria->paciente?->nombre ?: ($atencionAuditoria->alias ?: 'Sin registro') }} · {{ $atencionAuditoria->tipo ?: 'Emergencia' }} · {{ $atencionAuditoria->estado_atencion?->value ?: $atencionAuditoria->estado_atencion }}</small></span>
                                    <iconify-icon icon="lucide:chevron-down" aria-hidden="true"></iconify-icon>
                                </summary>
                                <div class="open-history-audit-history-grid">
                                    @foreach ([
                                        'Edad' => $atencionAuditoria->paciente?->fecha_nacimiento?->age ?? '--',
                                        'Sexo' => $atencionAuditoria->paciente?->sexo ?: '--',
                                        'Tipo' => $atencionAuditoria->tipo ?: 'Emergencia',
                                        'N.º atención' => $atencionAuditoria->numero_atencion ?: '--',
                                        'ID externo' => $atencionAuditoria->paciente?->id_paciente_externo ?: '--',
                                        'Ingreso' => $atencionAuditoria->fecha_hora_atencion?->format('d/m/Y H:i') ?: '--',
                                        'Profesional inicio' => $atencionAuditoria->profesionalResponsable?->nombres ?: '--',
                                        'Egreso' => $atencionAuditoria->fecha_hora_egreso?->format('d/m/Y H:i') ?: '--',
                                        'Profesional egreso' => $atencionAuditoria->profesionalEgreso?->nombres ?: '--',
                                    ] as $label => $valor)
                                        <div><span>{{ $label }}</span><strong>{{ $valor }}</strong></div>
                                    @endforeach
                                </div>
                            </details>

                            <details class="open-history-audit-section" open>
                                <summary>
                                    <span><strong>Hallazgos</strong><small>{{ count($auditoriaForm['datos_incompletos'] ?? []) }} sección(es) incompleta(s)</small></span>
                                    <iconify-icon icon="lucide:chevron-down" aria-hidden="true"></iconify-icon>
                                </summary>
                                <div class="open-history-audit-section-body">
                                    <fieldset>
                                        <legend>Datos incompletos</legend>
                                        <div class="open-history-audit-selection">
                                            @forelse ($auditoriaForm['datos_incompletos'] ?? [] as $seleccionado)
                                                <button type="button" wire:click="quitarDatoIncompleto('{{ $seleccionado }}')" class="open-history-audit-chip" title="Quitar {{ $seleccionado }}">
                                                    {{ $seleccionado }} <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                                                </button>
                                            @empty
                                                <span class="open-history-audit-selection-placeholder">Selecciona las secciones incompletas</span>
                                            @endforelse
                                            <input type="search" wire:model.live.debounce.250ms="auditoriaBusqueda" placeholder="Buscar sección..." aria-label="Buscar sección incompleta">
                                        </div>
                                        <div class="open-history-audit-list" role="listbox" aria-label="Secciones incompletas" aria-multiselectable="true">
                                            @forelse ($itemsAuditoria as $itemAuditoria)
                                                @php($itemSeleccionado = in_array($itemAuditoria, $auditoriaForm['datos_incompletos'] ?? [], true))
                                                <button
                                                    type="button"
                                                    role="option"
                                                    aria-selected="{{ $itemSeleccionado ? 'true' : 'false' }}"
                                                    class="open-history-audit-list-item {{ $itemSeleccionado ? 'is-selected' : '' }}"
                                                    wire:key="auditoria-item-{{ md5($itemAuditoria) }}"
                                                    wire:click="alternarDatoIncompleto('{{ $itemAuditoria }}')"
                                                >
                                                    <span>{{ $itemSeleccionado ? '−' : '+' }} {{ $itemAuditoria }}</span>
                                                    @if ($itemSeleccionado)
                                                        <iconify-icon icon="lucide:check" aria-hidden="true"></iconify-icon>
                                                    @endif
                                                </button>
                                            @empty
                                                <p class="open-history-audit-no-results">No se encontraron secciones.</p>
                                            @endforelse
                                        </div>
                                        @error('auditoriaForm.datos_incompletos.*') <small>{{ $message }}</small> @enderror
                                    </fieldset>
                                    <label class="is-full">
                                        <span>Observaciones</span>
                                        <div class="open-history-audit-suggestions" role="group" aria-label="Frases rápidas">
                                            @foreach (['Falta firma del profesional', 'Registro ilegible', 'Sin hora de registro', 'Diagnóstico sin CIE-10'] as $frase)
                                                <button type="button" wire:click="agregarFraseAuditoria('{{ $frase }}')">+ {{ $frase }}</button>
                                            @endforeach
                                        </div>
                                        <textarea rows="4" wire:model="auditoriaForm.observaciones" placeholder="Ingrese las observaciones de la auditoría"></textarea>
                                        @error('auditoriaForm.observaciones') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            </details>

                            <details class="open-history-audit-section" open>
                                <summary>
                                    <span><strong>Cierre</strong><small>Estado: {{ $auditoriaForm['estado'] ?: 'Pendiente' }}</small></span>
                                    <iconify-icon icon="lucide:chevron-down" aria-hidden="true"></iconify-icon>
                                </summary>
                                <div class="open-history-audit-section-body">
                                    <div class="open-history-audit-grid">
                                        <label>
                                            <span>Fecha/Hora de Inicio Auditoría</span>
                                            <input type="datetime-local" wire:model="auditoriaForm.fecha_hora_inicio">
                                            @error('auditoriaForm.fecha_hora_inicio') <small>{{ $message }}</small> @enderror
                                        </label>
                                        <label>
                                            <span>Usuario</span>
                                            <select wire:model="auditoriaForm.usuario_id">
                                                <option value="">Seleccione</option>
                                                @foreach ($usuariosAuditoria as $usuarioAuditoria)
                                                    <option value="{{ $usuarioAuditoria->id }}">{{ $usuarioAuditoria->name }} · {{ $usuarioAuditoria->email }}</option>
                                                @endforeach
                                            </select>
                                            @error('auditoriaForm.usuario_id') <small>{{ $message }}</small> @enderror
                                        </label>
                                    </div>
                                    <fieldset class="open-history-audit-states">
                                        <legend>Estado <em>*</em></legend>
                                        @foreach ($estadosAuditoria as $estadoAuditoria)
                                            <label class="is-{{ Str::slug($estadoAuditoria) }}">
                                                <input type="radio" wire:model="auditoriaForm.estado" value="{{ $estadoAuditoria }}">
                                                <span><strong>{{ $estadoAuditoria }}</strong><small>{{ ['Pendiente' => 'Pendiente de revisión', 'Observada' => 'Requiere corrección', 'Completada' => 'Sin hallazgos pendientes'][$estadoAuditoria] }}</small></span>
                                            </label>
                                        @endforeach
                                        @error('auditoriaForm.estado') <small>{{ $message }}</small> @enderror
                                    </fieldset>
                                    <label>
                                        <span>Fecha/Hora de Fin Auditoría</span>
                                        <input type="datetime-local" wire:model="auditoriaForm.fecha_hora_fin">
                                        @error('auditoriaForm.fecha_hora_fin') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            </details>
                            </div>
                        </div>
                        <footer>
                            <button type="button" class="patient-history-secondary" wire:click="cerrarModalAuditoria">Cancelar</button>
                            <button type="submit" class="catalog-primary-action">
                                <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                Guardar auditoría
                            </button>
                        </footer>
                    </form>
                    @endif
            </section>
        </div>
    @endif
</section>
