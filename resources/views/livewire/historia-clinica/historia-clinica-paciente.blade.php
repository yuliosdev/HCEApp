<section class="patient-history-page">
    <header class="patient-history-header panel-animate">
        <div>
            <p class="patient-history-kicker">Historia clinica del paciente</p>
            <h1>{{ $pacienteActual->nombre }}</h1>

            <div class="patient-history-meta">
                <span class="patient-sex-badge {{ $datosPaciente['sexo_clase'] }}">
                    <iconify-icon icon="{{ $datosPaciente['sexo_icono'] }}" aria-hidden="true"></iconify-icon>
                    {{ $datosPaciente['sexo'] }}
                </span>
                <span><iconify-icon icon="lucide:cake" aria-hidden="true"></iconify-icon>{{ $datosPaciente['edad'] }}</span>
                <span><iconify-icon icon="lucide:droplets" aria-hidden="true"></iconify-icon>Grupo {{ $datosPaciente['grupo'] }}</span>
                <span><iconify-icon icon="lucide:badge-plus" aria-hidden="true"></iconify-icon>RH {{ $datosPaciente['factor'] }}</span>
            </div>
        </div>

        <nav class="patient-history-actions" aria-label="Acciones de historia clinica">
            <button type="button" class="patient-history-secondary" onclick="window.print()" title="Imprimir historia clinica">
                <iconify-icon icon="lucide:printer" aria-hidden="true"></iconify-icon>
                Imprimir
            </button>
            <button type="button" class="catalog-primary-action" wire:click="nuevoRegistro" title="Crear nuevo registro clinico">
                <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                Nuevo Registro
            </button>
        </nav>
    </header>

    <div class="patient-history-divider"></div>

    <section class="patient-history-summary" aria-label="Resumen clinico">
        @foreach (['alergias', 'reacciones', 'antecedentes'] as $clave)
            @php($card = $resumenClinico[$clave])
            <article class="patient-dashboard-card hce-card-motion patient-history-card {{ $card['clase'] }} panel-animate" style="--panel-delay: {{ $loop->index * 80 }}ms">
                <header>
                    <span>
                        <iconify-icon icon="{{ $card['icono'] }}" aria-hidden="true"></iconify-icon>
                    </span>
                    <h2>{{ $card['titulo'] }}</h2>
                </header>

                <ul>
                    @forelse ($card['items'] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li class="is-empty">{{ $card['vacio'] }}</li>
                    @endforelse
                </ul>

                @if ($card['total'] > 2)
                    <button type="button" wire:click="abrirModalResumen('{{ $clave }}')" class="patient-history-card-link" title="Ver todos los registros de {{ $card['titulo'] }}">
                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                        Ver todos
                    </button>
                @endif
            </article>
        @endforeach

        <article class="patient-dashboard-card hce-card-motion patient-history-card is-vitals panel-animate" style="--panel-delay: 240ms">
            <header>
                <span>
                    <iconify-icon icon="lucide:activity" aria-hidden="true"></iconify-icon>
                </span>
                <h2>Ultimos Signos</h2>
            </header>

            @if ($ultimosSignos['tiene_datos'])
                <div class="patient-history-vitals">
                    <div>
                        <span>PA</span>
                        <strong>{{ $ultimosSignos['pa'] }}</strong>
                    </div>
                    <div>
                        <span>FC</span>
                        <strong>{{ $ultimosSignos['fc'] }}</strong>
                    </div>
                </div>
            @else
                <p class="patient-history-empty-text">Sin signos vitales registrados</p>
            @endif
        </article>
    </section>

    <article class="patient-history-panel panel-animate" style="--panel-delay: 320ms">
        <header class="patient-history-panel-header">
            <div>
                <p>Registro cronologico</p>
                <h2>Historial de Atenciones</h2>
            </div>

            <label>
                Registros por pagina
                <select wire:model.live="porPagina">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>
        </header>

        <div class="patient-history-loading" wire:loading.delay>
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="patient-history-table-wrap">
            <table class="patient-history-table">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Tipo</th>
                        <th>Nro Atencion</th>
                        <th>Profesional</th>
                        <th>DX. Principal</th>
                        <th>Triaje</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($atenciones->isEmpty())
                        <tr>
                            <td colspan="8" class="patient-history-empty">
                                <iconify-icon icon="lucide:clipboard-x" aria-hidden="true"></iconify-icon>
                                No se encontraron atenciones para este paciente.
                            </td>
                        </tr>
                    @endif

                    @foreach ($atenciones as $atencion)
                        @php($diagnostico = $this->diagnosticoPrincipal($atencion))
                        @php($triaje = $this->datosTriaje($atencion))
                        @php($abierta = $this->estadoAbierto($atencion))
                        <tr wire:key="historia-atencion-{{ $atencion->id }}">
                            <td>
                                <strong>{{ $atencion->fecha_hora_atencion?->format('d/m/Y') ?? '--' }}</strong>
                                <span>{{ $atencion->fecha_hora_atencion?->format('h:i A') ?? '--' }}</span>
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
                                    <button type="button" wire:click="verAtencion({{ $atencion->id }})" aria-label="Ver atencion" title="Ver atencion">
                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                    </button>
                                    <a
                                        href="{{ route('historia-clinica.atenciones-paciente.emergencia.imprimir', [$pacienteActual, $atencion]) }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Imprimir historia clinica"
                                        title="Imprimir historia clinica"
                                    >
                                        <iconify-icon icon="lucide:printer" aria-hidden="true"></iconify-icon>
                                    </a>
                                    <a
                                        href="{{ route('historia-clinica.atenciones-paciente.emergencia.ticket', [$pacienteActual, $atencion]) }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Ticket de historia"
                                        title="Ticket de historia"
                                    >
                                        <iconify-icon icon="lucide:ticket-check" aria-hidden="true"></iconify-icon>
                                    </a>
                                    @if ($this->puedeEditarAtencion($atencion))
                                        <button type="button" wire:click="editarAtencion({{ $atencion->id }})" aria-label="Editar atencion" title="Editar atencion">
                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                        </button>
                                    @endif
                                    @if ($esAdministrador)
                                        <button
                                            type="button"
                                            wire:click="cambiarEstadoAtencion({{ $atencion->id }})"
                                            aria-label="Cambiar estado de historia"
                                            title="Cambiar estado de historia"
                                        >
                                            <iconify-icon icon="{{ $abierta ? 'lucide:lock' : 'lucide:lock-open' }}" aria-hidden="true"></iconify-icon>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="is-disabled"
                                            disabled
                                            aria-label="Cambiar estado de historia solo disponible para administradores"
                                            title="Solo Administrador puede cambiar el estado"
                                        >
                                            <iconify-icon icon="lucide:lock-keyhole" aria-hidden="true"></iconify-icon>
                                        </button>
                                    @endif
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

    @if ($modal['abierto'])
        <div class="catalog-modal-backdrop patient-history-modal-backdrop">
            <section class="catalog-modal patient-history-modal" role="dialog" aria-modal="true" aria-labelledby="historia-resumen-modal-title">
                <header>
                    <div>
                        <p>Resumen clinico</p>
                        <h2 id="historia-resumen-modal-title">
                            <iconify-icon icon="{{ $modal['icono'] }}" aria-hidden="true"></iconify-icon>
                            {{ $modal['titulo'] }}
                        </h2>
                    </div>
                    <button type="button" wire:click="cerrarModalResumen" aria-label="Cerrar modal">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </header>

                <div class="patient-history-modal-table-wrap">
                    <table class="patient-history-modal-table">
                        <thead>
                            <tr>
                                @if ($modal['tipo'] === 'alergias')
                                    <th>Alergia</th>
                                    <th>Agente</th>
                                    <th>Alergeno</th>
                                    <th>Periodo</th>
                                @elseif ($modal['tipo'] === 'reacciones')
                                    <th>Medicamento</th>
                                    <th>Descripcion</th>
                                    <th>Tipo</th>
                                    <th>Gravedad</th>
                                    <th>Periodo</th>
                                @else
                                    <th>Diagnostico / denominacion</th>
                                    <th>Origen</th>
                                    <th>Periodo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($modal['registros'] as $registro)
                                <tr>
                                    @if ($modal['tipo'] === 'alergias')
                                        <td>{{ $registro->alergia?->nombre ?? 'Alergia registrada' }}</td>
                                        <td>{{ $registro->agente_causal ?: '--' }}</td>
                                        <td>{{ $registro->alergeno ?: '--' }}</td>
                                        <td>{{ $registro->periodo ?: '--' }}</td>
                                    @elseif ($modal['tipo'] === 'reacciones')
                                        <td>{{ $registro->dci_medicamento ?: '--' }}</td>
                                        <td>{{ $registro->descripcion ?: '--' }}</td>
                                        <td>{{ $registro->tipo?->value ?? '--' }}</td>
                                        <td>{{ $registro->gravedad?->value ?? '--' }}</td>
                                        <td>{{ $registro->periodo ?: '--' }}</td>
                                    @else
                                        <td>{{ $registro->diagnosticoCie10?->codigo ? $registro->diagnosticoCie10->codigo.' - '.($registro->denominacion ?: $registro->diagnosticoCie10->nombre) : ($registro->denominacion ?: '--') }}</td>
                                        <td>{{ $registro->origen ?: '--' }}</td>
                                        <td>{{ $registro->periodo ?: '--' }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="patient-history-empty">No hay registros para mostrar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</section>
