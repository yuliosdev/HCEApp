<section class="emergency-record-page" data-emergency-unsaved-changes="{{ $hayCambiosSinGuardar ? 'true' : 'false' }}">
    <header class="emergency-record-header panel-animate">
        <div class="emergency-patient-block">
            <span class="emergency-patient-avatar">
                <iconify-icon icon="lucide:user-round" aria-hidden="true"></iconify-icon>
            </span>

            <div>
                <div class="emergency-patient-heading">
                    <button
                        type="button"
                        class="emergency-back-link"
                        title="Volver a Listado de HCE"
                        aria-label="Volver a Listado de HCE"
                        wire:click="solicitarSalidaDelPanel"
                    >
                        <iconify-icon icon="lucide:arrow-left" aria-hidden="true"></iconify-icon>
                    </button>

                    <h1>{{ $datosPaciente['nombre'] }}</h1>
                </div>
                <div class="emergency-patient-meta">
                    <span>{{ $datosPaciente['sexo'] }}</span>
                    <i></i>
                    <span>{{ $datosPaciente['edad'] }}</span>
                </div>
                <div class="emergency-patient-codes">
                    <span><strong>HC:</strong> {{ $datosPaciente['historia'] }}</span>
                    <span><strong>Reg HC:</strong> {{ $atencionId ?: 'Nuevo' }}</span>
                    <span><strong>AT:</strong> {{ $form['numero_atencion'] ?: '--' }}</span>
                    <span><iconify-icon icon="lucide:phone" aria-hidden="true"></iconify-icon>{{ $datosPaciente['telefono'] }}</span>
                </div>
                <label class="emergency-patient-alias">
                    <span>Alias</span>
                    <input
                        type="text"
                        wire:model.blur="form.alias"
                        placeholder="Alias temporal del paciente"
                        maxlength="100"
                        @readonly($soloLectura)
                    >
                    @error('form.alias') <small>{{ $message }}</small> @enderror
                </label>
            </div>
        </div>

        <div class="emergency-header-actions">
            <div class="emergency-vitals-summary" aria-label="Signos vitales">
                <article>
                    <span>FC</span>
                    <strong>{{ $signos['fc'] }}</strong>
                    <em>lpm</em>
                </article>
                <article>
                    <span>PA</span>
                    <strong>{{ $signos['pa'] }}</strong>
                </article>
                <article>
                    <span>SpO2</span>
                    <strong>{{ $signos['spo2'] }}</strong>
                    <em>%</em>
                </article>
                @php($prioridadResumen = $prioridades->firstWhere('id', (int) $form['prioridad_id']))
                <article class="is-priority priority-level-{{ $prioridadResumen?->orden ?: 'none' }}">
                    <span>Prioridad</span>
                    @if ($soloLectura)
                        <strong>{{ $prioridadResumen?->prioridad ?? 'Sin prioridad' }}</strong>
                    @else
                        <select
                            wire:key="prioridad-encabezado"
                            wire:model.live="form.prioridad_id"
                            wire:change="limpiarErrorDeCampo('prioridad_id')"
                            aria-label="Prioridad de atencion"
                        >
                            <option value="">Sin prioridad</option>
                            @foreach ($prioridades as $prioridad)
                                <option value="{{ $prioridad->id }}">{{ $prioridad->orden ? 'Nivel '.$prioridad->orden : $prioridad->prioridad }}</option>
                            @endforeach
                        </select>
                    @endif
                </article>
            </div>

            <div class="emergency-doctor-row">
                <div class="emergency-record-actions">
                    @if (! $soloLectura)
                        <button type="button" class="emergency-close-action" wire:click="cerrarAtencion">
                            Cerrar Atención
                        </button>
                        <button type="button" class="catalog-primary-action emergency-save-action" wire:click="guardar">
                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                            Guardar
                        </button>
                    @endif
                </div>

                <span class="emergency-status {{ $esAtencionAbierta ? 'is-open' : 'is-closed' }}">
                    <i></i>
                    Atencion {{ $form['estado_atencion'] }}
                </span>

                <div class="emergency-doctor-card">
                    <div>
                        <strong>{{ $datosMedico['nombre'] }}</strong>
                        <span>{{ $datosMedico['especialidad'] }}</span>
                        <em>CMP: {{ $datosMedico['cmp'] }} · RNE: {{ $datosMedico['rne'] }}</em>
                    </div>
                    @if ($puedeCambiarProfesionalTratante)
                        <button
                            type="button"
                            class="emergency-doctor-icon-button"
                            wire:click="abrirModalCambiarProfesionalTratante"
                            title="Cambiar Profesional Tratante"
                            aria-label="Cambiar Profesional Tratante"
                        >
                            <iconify-icon icon="lucide:stethoscope" aria-hidden="true"></iconify-icon>
                        </button>
                    @else
                        <span>
                            <iconify-icon icon="lucide:stethoscope" aria-hidden="true"></iconify-icon>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <div class="emergency-tabs-stack">
        <nav class="emergency-tabs" aria-label="Secciones de historia clinica de emergencia">
            @foreach ($tabs as $tab)
                <button
                    type="button"
                    wire:click="cambiarTab('{{ $tab['clave'] }}')"
                    class="{{ $tabActiva === $tab['clave'] ? 'is-active' : '' }}"
                    aria-current="{{ $tabActiva === $tab['clave'] ? 'page' : 'false' }}"
                >
                    <iconify-icon icon="{{ $tab['icono'] }}" aria-hidden="true"></iconify-icon>
                    <span>{{ $tab['titulo'] }}</span>
                </button>
            @endforeach
        </nav>

        <main class="emergency-tab-panel">
            @if ($tabActiva === 'atencion')
                <section class="emergency-attention-grid">
                    <div class="emergency-attention-sections">
                        <article class="emergency-attention-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:info" aria-hidden="true"></iconify-icon>
                                    Datos de la Atencion
                                </h2>
                                <p class="sr-only">Atencion de emergencia</p>
                            </header>

                            <div class="emergency-attention-form is-two-columns">
                                <label>
                                    <span>Fecha/Hora Episodio</span>
                                    <input type="datetime-local" wire:model.blur="form.fecha_hora_inicio_episodio" @readonly($soloLectura)>
                                    @error('form.fecha_hora_inicio_episodio') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Fecha/Hora Ingreso</span>
                                    <input type="datetime-local" wire:model.blur="form.fecha_hora_atencion" @readonly($soloLectura)>
                                    @error('form.fecha_hora_atencion') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Topico</span>
                                    <select wire:model="form.topico" wire:change="limpiarErrorDeCampo('topico')" @disabled($soloLectura)>
                                        <option value="">Seleccione</option>
                                        @foreach ($topicos as $topico)
                                            <option value="{{ $topico->value }}">{{ $topico->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.topico') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Ambiente</span>
                                    <select wire:model="form.ambiente_id" wire:change="limpiarErrorDeCampo('ambiente_id')" @disabled($soloLectura)>
                                        <option value="">Seleccione</option>
                                        @foreach ($ambientesCatalogo as $ambiente)
                                            <option value="{{ $ambiente->id }}">{{ $ambiente->denominacion }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.ambiente_id') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Tipo</span>
                                    <select wire:model="form.tipo" wire:change="limpiarErrorDeCampo('tipo')" @disabled($soloLectura)>
                                        @foreach ($tiposAtencion as $tipoAtencion)
                                            <option value="{{ $tipoAtencion }}">{{ $tipoAtencion }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.tipo') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </article>

                        <article class="emergency-attention-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:users-round" aria-hidden="true"></iconify-icon>
                                    Datos de Familiar / Acompanante
                                </h2>
                            </header>

                            <div class="emergency-attention-form is-three-columns">
                                <label>
                                    <span>Acompanante</span>
                                    <input type="text" wire:model.blur="form.acompanante" placeholder="Nombre del acompanante" @readonly($soloLectura)>
                                    @error('form.acompanante') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Parentesco</span>
                                    <input type="text" wire:model.blur="form.parentesco" placeholder="Ej. Hijo" @readonly($soloLectura)>
                                    @error('form.parentesco') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Telefono</span>
                                    <input type="tel" wire:model.blur="form.telefono" placeholder="Ej. 999888777" @readonly($soloLectura)>
                                    @error('form.telefono') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </article>

                        <article class="emergency-attention-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:ambulance" aria-hidden="true"></iconify-icon>
                                    Datos Pre Hospitalarios
                                </h2>
                            </header>

                            <div class="emergency-attention-form is-two-columns">
                                <label>
                                    <span>Fuente</span>
                                    <select wire:model="form.fuente" @disabled($soloLectura)>
                                        <option value="">Seleccione</option>
                                        @foreach ($fuentesAtencion as $fuente)
                                            <option value="{{ $fuente->value }}">{{ $fuente->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.fuente') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Lugar del Evento</span>
                                    <input type="text" wire:model.blur="form.lugar_evento" placeholder="Ej. Domicilio" @readonly($soloLectura)>
                                    @error('form.lugar_evento') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Tipo de Lesion</span>
                                    <input type="text" wire:model.blur="form.tipo_lesion_evento" placeholder="Ej. Traumatica" @readonly($soloLectura)>
                                    @error('form.tipo_lesion_evento') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Fecha/Hora Recepcion</span>
                                    <input type="datetime-local" wire:model.blur="form.fecha_hora_recepcion" @readonly($soloLectura)>
                                    @error('form.fecha_hora_recepcion') <small>{{ $message }}</small> @enderror
                                </label>
                                <label class="is-full">
                                    <span>
                                        Signos Vitales Pre-Hospitalarios
                                        <em>Texto libre</em>
                                    </span>
                                    <textarea rows="6" wire:model.blur="form.signos_vitales_transmitidos" @readonly($soloLectura)></textarea>
                                    @error('form.signos_vitales_transmitidos') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </article>
                    </div>

                    <aside class="emergency-timeline-card" aria-label="Eventos del episodio">
                        <header>
                            <h2>
                                <iconify-icon icon="lucide:history" aria-hidden="true"></iconify-icon>
                                Eventos del Episodio
                            </h2>
                        </header>

                        <ol class="emergency-episode-timeline">
                            @forelse ($eventosEpisodio as $evento)
                                <li>
                                    <span></span>
                                    <div>
                                        <time>{{ $evento['fecha'] }}</time>
                                        <strong>{{ $evento['titulo'] }}</strong>
                                        <p>{{ $evento['detalle'] }}</p>
                                    </div>
                                </li>
                            @empty
                                <li class="is-empty">
                                    <span></span>
                                    <div>
                                        <strong>Sin eventos registrados</strong>
                                        <p>Los eventos se mostraran conforme se registren fechas y ocurrencias del episodio.</p>
                                    </div>
                                </li>
                            @endforelse
                        </ol>
                    </aside>
                </section>
            @elseif ($tabActiva === 'triaje')
                <section class="emergency-triage-grid">
                    <div class="emergency-triage-left">
                        <article class="emergency-attention-card emergency-triage-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:clipboard-plus" aria-hidden="true"></iconify-icon>
                                    Triaje
                                </h2>
                            </header>

                            <div class="emergency-attention-form is-two-columns">
                                <label>
                                    <span>Fecha / Hora</span>
                                    <input type="datetime-local" wire:model.live="form.fecha_hora_triaje" @readonly($soloLectura)>
                                    @error('form.fecha_hora_triaje') <small>{{ $message }}</small> @enderror
                                </label>

                                <label class="emergency-combobox">
                                    <span>Profesional</span>
                                    <div class="emergency-combobox-field">
                                        <input
                                            type="search"
                                            wire:model.live.debounce.250ms="profesionalTriajeBusqueda"
                                            placeholder="Buscar profesional por nombre, CMP o RNE"
                                            @readonly($soloLectura)
                                        >
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if (! $soloLectura && $profesionalTriajeBusqueda !== '' && blank($form['profesional_triaje_id']))
                                        <div class="emergency-combobox-list">
                                            @forelse ($profesionalesTriaje as $profesional)
                                                <button type="button" wire:click="seleccionarProfesionalTriaje({{ $profesional->id }})">
                                                    <strong>{{ $profesional->nombres }}</strong>
                                                    <span>{{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }} · {{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron profesionales.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('form.profesional_triaje_id') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Nivel de Prioridad</span>
                                    <select
                                        wire:key="prioridad-triaje"
                                        wire:model.live="form.prioridad_id"
                                        wire:change="limpiarErrorDeCampo('prioridad_id')"
                                        @disabled($soloLectura)
                                    >
                                        <option value="">Seleccione</option>
                                        @foreach ($prioridades as $prioridad)
                                            <option value="{{ $prioridad->id }}">
                                                {{ $prioridad->orden ? 'Nivel '.$prioridad->orden.' - ' : '' }}{{ $prioridad->prioridad }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form.prioridad_id') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Tiempo de espera</span>
                                    <input type="text" wire:model="form.tiempo_espera_calculado" readonly>
                                    @error('form.tiempo_espera_calculado') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Reevaluacion Programada</span>
                                    <input type="datetime-local" wire:model="form.reevaluacion_programada" readonly>
                                    @error('form.reevaluacion_programada') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </article>

                        <article class="emergency-attention-card emergency-vital-signs-card">
                            <header class="emergency-section-toolbar">
                                <h2>
                                    <iconify-icon icon="lucide:activity" aria-hidden="true"></iconify-icon>
                                    Signos Vitales
                                </h2>

                                @if (! $soloLectura)
                                    <div class="emergency-section-actions">
                                        <button type="button" class="emergency-inline-action" wire:click="cargarSignosVitalesFavoritos">
                                            <iconify-icon icon="lucide:star" aria-hidden="true"></iconify-icon>
                                            Cargar Signos Vitales
                                        </button>
                                        <button type="button" class="emergency-inline-action" wire:click="abrirNuevoSignoVital">
                                            <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                            Nuevo
                                        </button>
                                    </div>
                                @endif
                            </header>

                            <div class="emergency-vitals-table-wrap">
                                <table class="emergency-vitals-table">
                                    <thead>
                                        <tr>
                                            <th>Funcion Vital</th>
                                            <th>Valor</th>
                                            <th>Unidad</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($signosVitalesTriaje as $signoTriaje)
                                            <tr wire:key="triaje-signo-{{ $signoTriaje['id'] }}">
                                                <td>{{ $signoTriaje['funcion'] }}</td>
                                                <td><strong>{{ $signoTriaje['valor'] }}</strong></td>
                                                <td>{{ $signoTriaje['unidad'] }}</td>
                                                <td>
                                                    <span class="emergency-vital-state {{ $signoTriaje['estado']['clase'] }}" title="{{ $signoTriaje['estado']['texto'] }}">
                                                        <iconify-icon icon="{{ $signoTriaje['estado']['icono'] }}" aria-hidden="true"></iconify-icon>
                                                        <span class="sr-only">{{ $signoTriaje['estado']['texto'] }}</span>
                                                    </span>
                                                </td>
                                                <td>
                                                    @if (! $soloLectura)
                                                        <div class="emergency-row-actions">
                                                            <button type="button" wire:click="editarSignoVital({{ $signoTriaje['id'] }})" title="Editar signo vital">
                                                                <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                            </button>
                                                            <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $signoTriaje['id'] }}" data-registro-nombre="{{ $signoTriaje['funcion'] }}" data-catalogo-singular="signo vital" data-delete-method="eliminarSignoVital" data-confirm-title="Eliminar signo vital" data-confirm-message="Deseas eliminar el signo vital {{ $signoTriaje['funcion'] }}?" title="Eliminar signo vital">
                                                                <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span>-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="emergency-empty-cell">Sin signos vitales registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($formularioSignoVitalAbierto)
                                <div class="emergency-vital-form">
                                    <label>
                                        <span>Funcion vital</span>
                                        <select wire:model="signoVitalForm.signo_vital_id">
                                            <option value="">Seleccione</option>
                                            @foreach ($catalogoSignosVitales as $signoVital)
                                                <option value="{{ $signoVital->id }}">{{ $signoVital->descripcion }}{{ $signoVital->unidad ? ' ('.$signoVital->unidad.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                        @error('signoVitalForm.signo_vital_id') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Valor</span>
                                        <input type="text" wire:model.blur="signoVitalForm.valor" placeholder="Ej. 82">
                                        @error('signoVitalForm.valor') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <div>
                                        <button type="button" class="emergency-close-action" wire:click="$set('formularioSignoVitalAbierto', false)">Cancelar</button>
                                        <button type="button" class="catalog-primary-action" wire:click="guardarSignoVital">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <label class="emergency-triage-check">
                                <input type="checkbox" wire:model.live="form.triaje" @disabled($soloLectura)>
                                <span>Triaje Realizado</span>
                            </label>
                            @error('form.triaje') <small>{{ $message }}</small> @enderror
                        </article>
                    </div>

                    <div class="emergency-triage-reserved">
                        <article class="emergency-attention-card emergency-consultation-reason-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:message-circle-question" aria-hidden="true"></iconify-icon>
                                    Motivo de Consulta
                                </h2>
                            </header>

                            <div class="emergency-attention-form emergency-consultation-reason-form">
                                <label class="is-full">
                                    <span>Motivo de Consulta</span>
                                    <textarea rows="7" wire:model.blur="form.motivo_consulta" @readonly($soloLectura)></textarea>
                                    @error('form.motivo_consulta') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </article>

                        <article class="emergency-attention-card emergency-medical-history-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:clipboard-clock" aria-hidden="true"></iconify-icon>
                                    Antecedentes Médicos
                                </h2>
                            </header>

                            <div class="emergency-medical-history-grid">
                                @foreach ($antecedentesMedicosItems as $item)
                                    <article class="emergency-medical-history-item hce-card-motion" style="--history-accent: {{ $item['acento'] }}">
                                        <div class="emergency-medical-history-heading">
                                            <span>
                                                <iconify-icon icon="{{ $item['icono'] }}" aria-hidden="true"></iconify-icon>
                                            </span>
                                            <strong>{{ $item['titulo'] }}</strong>
                                            <button
                                                type="button"
                                                wire:click="abrirDrawerAntecedente('{{ $item['clave'] }}')"
                                                title="Agregar o editar {{ $item['titulo'] }}"
                                                aria-label="Agregar o editar {{ $item['titulo'] }}"
                                                @disabled($item['deshabilitado'])
                                            >
                                                <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                            </button>
                                        </div>

                                        <ul>
                                            @forelse ($item['items'] as $detalle)
                                                <li>{{ $detalle }}</li>
                                            @empty
                                                <li class="is-empty">{{ $item['vacio'] }}</li>
                                            @endforelse
                                        </ul>
                                    </article>
                                @endforeach
                            </div>
                        </article>
                    </div>
                </section>
            @elseif ($tabActiva === 'anamnesis')
                <section class="emergency-anamnesis-tab-grid" style="padding-top: 18px;">
                    <article class="emergency-attention-card emergency-anamnesis-card">
                        <header>
                            <h2>
                                <iconify-icon icon="lucide:message-square-text" aria-hidden="true"></iconify-icon>
                                Anamnesis
                            </h2>
                        </header>

                        <div class="emergency-attention-form emergency-anamnesis-form">
                            <label>
                                <span>Fecha / Hora</span>
                                <input type="datetime-local" wire:model.blur="form.fecha_hora_anamnesis" @readonly($soloLectura)>
                                @error('form.fecha_hora_anamnesis') <small>{{ $message }}</small> @enderror
                            </label>

                            <label>
                                <span>Tiempo de enf.</span>
                                <input type="number" min="0" wire:model.blur="form.tiempo_enfermedad" @readonly($soloLectura)>
                                @error('form.tiempo_enfermedad') <small>{{ $message }}</small> @enderror
                            </label>

                            <label>
                                <span>Unidad</span>
                                <select wire:model="form.unidad_tiempo_enfermedad" @disabled($soloLectura)>
                                    <option value="">Seleccione</option>
                                    @foreach ($unidadesTiempoEnfermedad as $unidad)
                                        <option value="{{ $unidad->value }}">{{ $unidad->value }}</option>
                                    @endforeach
                                </select>
                                @error('form.unidad_tiempo_enfermedad') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="is-full">
                                <span>Signos y Sintomas Principales</span>
                                <textarea rows="3" wire:model.blur="form.signos_sintomas" @readonly($soloLectura)></textarea>
                                @error('form.signos_sintomas') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="is-full">
                                <span>Desarrollo Cronologico</span>
                                <textarea rows="10" wire:model.blur="form.desarrollo_cronologico" @readonly($soloLectura)></textarea>
                                @error('form.desarrollo_cronologico') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>
                    </article>
                </section>
            @elseif ($tabActiva === 'examenes')
                <section class="emergency-exams-stack">
                    <article class="emergency-attention-card emergency-glasgow-card">
                        <header>
                            <h2>
                                <iconify-icon icon="lucide:brain" aria-hidden="true"></iconify-icon>
                                Escala de Glasgow
                            </h2>
                        </header>

                        <div class="emergency-glasgow-grid">
                            @foreach ($glasgowResumen as $glasgow)
                                <label class="emergency-glasgow-score-card">
                                    <span>{{ $glasgow['titulo'] }}</span>
                                    <strong>{{ $glasgow['valor'] ?? '--' }}</strong>
                                    <em>{{ $glasgow['descripcion'] }}</em>
                                    <select wire:model.live="form.{{ $glasgow['campo'] }}" @disabled($soloLectura)>
                                        <option value="">Seleccione</option>
                                        @foreach (($glasgowOpciones[$glasgow['tipo']] ?? collect()) as $opcionGlasgow)
                                            <option value="{{ $opcionGlasgow->puntaje }}">
                                                {{ $opcionGlasgow->puntaje }} - {{ $opcionGlasgow->descripcion }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form.'.$glasgow['campo']) <small>{{ $message }}</small> @enderror
                                </label>
                            @endforeach

                            <article class="emergency-glasgow-score-card is-total {{ $glasgowClasificacion['clase'] }}">
                                <span>Puntuacion Total</span>
                                <strong>{{ $form['eg_total'] ?? '--' }}</strong>
                                <em>{{ $glasgowClasificacion['texto'] }}</em>
                            </article>
                        </div>
                    </article>

                    <section class="emergency-exams-grid">
                        <article class="emergency-attention-card emergency-general-exam-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:accessibility" aria-hidden="true"></iconify-icon>
                                    Evaluacion General
                                </h2>
                            </header>

                            <div class="emergency-attention-form emergency-general-exam-form">
                                <label class="is-full">
                                    <span>Estado General</span>
                                    <textarea rows="3" wire:model.blur="form.estado_general" @readonly($soloLectura)></textarea>
                                    @error('form.estado_general') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Hidratacion</span>
                                    <textarea rows="3" wire:model.blur="form.hidratacion" @readonly($soloLectura)></textarea>
                                    @error('form.hidratacion') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Nutricion</span>
                                    <textarea rows="3" wire:model.blur="form.nutricion" @readonly($soloLectura)></textarea>
                                    @error('form.nutricion') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Estado de conciencia</span>
                                    <textarea rows="3" wire:model.blur="form.estado_conciencia" @readonly($soloLectura)></textarea>
                                    @error('form.estado_conciencia') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Piel y Anexos</span>
                                    <textarea rows="3" wire:model.blur="form.piel_anexos" @readonly($soloLectura)></textarea>
                                    @error('form.piel_anexos') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </article>

                        <article class="emergency-attention-card emergency-regional-exam-card">
                            <header>
                                <h2>
                                    <iconify-icon icon="lucide:clipboard-pen-line" aria-hidden="true"></iconify-icon>
                                    Examen Regional
                                </h2>
                            </header>

                            <div class="emergency-attention-form emergency-regional-exam-form">
                                <label>
                                    <span>Cabeza y Cuello</span>
                                    <textarea rows="3" wire:model.blur="form.cabeza_cuello" @readonly($soloLectura)></textarea>
                                    @error('form.cabeza_cuello') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Torax Respiratorio</span>
                                    <textarea rows="3" wire:model.blur="form.torax_respiratorio" @readonly($soloLectura)></textarea>
                                    @error('form.torax_respiratorio') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Cardiovascular</span>
                                    <textarea rows="3" wire:model.blur="form.cardiovascular" @readonly($soloLectura)></textarea>
                                    @error('form.cardiovascular') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Abdomen</span>
                                    <textarea rows="3" wire:model.blur="form.abdomen" @readonly($soloLectura)></textarea>
                                    @error('form.abdomen') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Neurologico</span>
                                    <textarea rows="3" wire:model.blur="form.neurologico" @readonly($soloLectura)></textarea>
                                    @error('form.neurologico') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Musculoesqueletico / Extremidades</span>
                                    <textarea rows="3" wire:model.blur="form.musculoesqueletico_extremidades" @readonly($soloLectura)></textarea>
                                    @error('form.musculoesqueletico_extremidades') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Genitourinario / Ginecoobstetrico</span>
                                    <textarea rows="3" wire:model.blur="form.genitourinario_ginecoobstetrico" @readonly($soloLectura)></textarea>
                                    @error('form.genitourinario_ginecoobstetrico') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Otros</span>
                                    <textarea rows="3" wire:model.blur="form.otros" @readonly($soloLectura)></textarea>
                                    @error('form.otros') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </article>
                    </section>
                </section>
            @elseif ($tabActiva === 'diagnosticos')
                <section class="emergency-diagnosis-stack">
                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:badge-plus" aria-hidden="true"></iconify-icon>
                                Diagnosticos de Ingreso
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevoDiagnosticoIngreso">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nuevo
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table">
                                <thead>
                                    <tr>
                                        <th>CIE10</th>
                                        <th>Denominacion</th>
                                        <th>Tipo</th>
                                        <th>Observacion</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($diagnosticosIngreso as $diagnosticoIngreso)
                                        <tr wire:key="diagnostico-ingreso-{{ $diagnosticoIngreso['id'] }}">
                                            <td><span class="emergency-code-chip">{{ $diagnosticoIngreso['codigo'] }}</span></td>
                                            <td>{{ $diagnosticoIngreso['denominacion'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $diagnosticoIngreso['tipo'] }}</span></td>
                                            <td>{{ $diagnosticoIngreso['observacion'] }}</td>
                                            <td>
                                                @if (! $soloLectura)
                                                    <div class="emergency-row-actions">
                                                        <button type="button" wire:click="editarDiagnosticoIngreso({{ $diagnosticoIngreso['id'] }})" title="Editar diagnostico de ingreso">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $diagnosticoIngreso['id'] }}" data-registro-nombre="{{ $diagnosticoIngreso['codigo'] }}" data-catalogo-singular="diagnóstico de ingreso" data-delete-method="eliminarDiagnosticoIngreso" data-confirm-title="Eliminar diagnóstico de ingreso" data-confirm-message="Deseas eliminar el diagnóstico de ingreso {{ $diagnosticoIngreso['codigo'] }}?" title="Eliminar diagnostico de ingreso">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado diagnosticos de ingreso.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioDiagnosticoIngresoAbierto)
                            <div class="emergency-vital-form emergency-diagnosis-form">
                                <label class="emergency-combobox emergency-cie10-field">
                                    <span>CIE10</span>
                                    <div class="emergency-combobox-field">
                                        <input
                                            type="search"
                                            wire:model.live.debounce.250ms="diagnosticoIngresoCie10Busqueda"
                                            placeholder="Buscar por codigo o denominacion"
                                        >
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if ($diagnosticoIngresoCie10Busqueda !== '' && blank($diagnosticoIngresoForm['diagnostico_cie10_id']))
                                        <div class="emergency-combobox-list emergency-cie10-list">
                                            @forelse ($diagnosticosCie10Ingreso as $diagnosticoCie10)
                                                <button type="button" wire:click="seleccionarDiagnosticoIngresoCie10({{ $diagnosticoCie10->id }})">
                                                    <strong>{{ $diagnosticoCie10->codigo }}</strong>
                                                    <span>{{ $diagnosticoCie10->nombre }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron diagnosticos CIE10.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('diagnosticoIngresoForm.diagnostico_cie10_id') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Tipo</span>
                                    <select wire:model="diagnosticoIngresoForm.tipo_diagnostico">
                                        <option value="">Seleccione</option>
                                        @foreach ($tiposDiagnostico as $tipoDiagnostico)
                                            <option value="{{ $tipoDiagnostico->value }}">{{ $tipoDiagnostico->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('diagnosticoIngresoForm.tipo_diagnostico') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Observacion</span>
                                    <input type="text" wire:model.blur="diagnosticoIngresoForm.observacion" placeholder="Ej. Controlar evolucion clinica">
                                    @error('diagnosticoIngresoForm.observacion') <small>{{ $message }}</small> @enderror
                                </label>
                                <div>
                                    <button type="button" class="emergency-close-action" wire:click="$set('formularioDiagnosticoIngresoAbierto', false)">Cancelar</button>
                                    <button type="button" class="catalog-primary-action" wire:click="guardarDiagnosticoIngreso">
                                        <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endif
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:list-filter" aria-hidden="true"></iconify-icon>
                                Evoluciones Clinicas
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevaEvolucion">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nueva
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-evolutions-table">
                                <thead>
                                    <tr>
                                        <th>Fecha / Hora</th>
                                        <th>Profesional de la Salud</th>
                                        <th>Detalle Clinico</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($evolucionesClinicas as $evolucion)
                                        <tr wire:key="evolucion-clinica-{{ $evolucion['id'] }}">
                                            <td>{{ $evolucion['fecha_hora'] }}</td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $evolucion['iniciales'] }}</strong>
                                                    {{ $evolucion['profesional'] }}
                                                </span>
                                            </td>
                                            <td>{{ $evolucion['detalle_clinico'] }}</td>
                                            <td>
                                                @if (! $soloLectura)
                                                    <div class="emergency-row-actions">
                                                        <button type="button" wire:click="editarEvolucion({{ $evolucion['id'] }})" title="Editar evolucion clinica">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $evolucion['id'] }}" data-registro-nombre="{{ $evolucion['fecha_hora'] }}" data-catalogo-singular="evolución clínica" data-delete-method="eliminarEvolucion" data-confirm-title="Eliminar evolución clínica" data-confirm-message="Deseas eliminar esta evolución clínica?" title="Eliminar evolucion clinica">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:clipboard-list" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado evoluciones clinicas.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioEvolucionAbierto)
                            <div class="emergency-vital-form emergency-evolution-form">
                                <div class="emergency-evolution-form-row">
                                    <label>
                                        <span>Fecha / Hora</span>
                                        <input type="datetime-local" wire:model.blur="evolucionForm.fecha_hora">
                                        @error('evolucionForm.fecha_hora') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label class="emergency-combobox emergency-evolution-professional-field">
                                        <span>Profesional</span>
                                        <div class="emergency-combobox-field">
                                            <input
                                                type="search"
                                                wire:model.live.debounce.250ms="profesionalEvolucionBusqueda"
                                                placeholder="Buscar por nombre, especialidad o CMP"
                                            >
                                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                        </div>
                                        @if ($profesionalEvolucionBusqueda !== '' && blank($evolucionForm['profesional_salud_id']))
                                            <div class="emergency-combobox-list emergency-evolution-professional-list">
                                                @forelse ($profesionalesEvolucion as $profesional)
                                                    <button type="button" wire:click="seleccionarProfesionalEvolucion({{ $profesional->id }})">
                                                        <strong>{{ $profesional->nombres }}</strong>
                                                        <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                                    </button>
                                                @empty
                                                    <p>No se encontraron profesionales.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                        @error('evolucionForm.profesional_salud_id') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <div class="emergency-evolution-form-actions">
                                        <button type="button" class="emergency-close-action" wire:click="$set('formularioEvolucionAbierto', false)">Cancelar</button>
                                        <button type="button" class="catalog-primary-action" wire:click="guardarEvolucion">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                                <label class="is-full">
                                    <span>Detalle Clinico</span>
                                    <textarea rows="4" wire:model.blur="evolucionForm.detalle_clinico" placeholder="Describa la evolucion clinica del paciente"></textarea>
                                    @error('evolucionForm.detalle_clinico') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        @endif
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:log-out" aria-hidden="true"></iconify-icon>
                                Diagnosticos de Egreso
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevoDiagnosticoEgreso">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nuevo
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table">
                                <thead>
                                    <tr>
                                        <th>CIE10</th>
                                        <th>Denominacion</th>
                                        <th>Clasificacion</th>
                                        <th>Tipo</th>
                                        <th>Observacion</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($diagnosticosEgreso as $diagnosticoEgreso)
                                        <tr wire:key="diagnostico-egreso-{{ $diagnosticoEgreso['id'] }}">
                                            <td><span class="emergency-code-chip">{{ $diagnosticoEgreso['codigo'] }}</span></td>
                                            <td>{{ $diagnosticoEgreso['denominacion'] }}</td>
                                            <td>{{ $diagnosticoEgreso['clasificacion'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $diagnosticoEgreso['tipo'] }}</span></td>
                                            <td>{{ $diagnosticoEgreso['observacion'] }}</td>
                                            <td>
                                                @if (! $soloLectura)
                                                    <div class="emergency-row-actions">
                                                        <button type="button" wire:click="editarDiagnosticoEgreso({{ $diagnosticoEgreso['id'] }})" title="Editar diagnostico de egreso">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $diagnosticoEgreso['id'] }}" data-registro-nombre="{{ $diagnosticoEgreso['codigo'] }}" data-catalogo-singular="diagnóstico de egreso" data-delete-method="eliminarDiagnosticoEgreso" data-confirm-title="Eliminar diagnóstico de egreso" data-confirm-message="Deseas eliminar el diagnóstico de egreso {{ $diagnosticoEgreso['codigo'] }}?" title="Eliminar diagnostico de egreso">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:file-x-2" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado diagnosticos de egreso.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioDiagnosticoEgresoAbierto)
                            <div class="emergency-vital-form emergency-discharge-diagnosis-form">
                                <label class="emergency-combobox emergency-cie10-field">
                                    <span>CIE10</span>
                                    <div class="emergency-combobox-field">
                                        <input
                                            type="search"
                                            wire:model.live.debounce.250ms="diagnosticoEgresoCie10Busqueda"
                                            placeholder="Buscar por codigo o denominacion"
                                        >
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if ($diagnosticoEgresoCie10Busqueda !== '' && blank($diagnosticoEgresoForm['diagnostico_cie10_id']))
                                        <div class="emergency-combobox-list emergency-cie10-list">
                                            @forelse ($diagnosticosCie10Egreso as $diagnosticoCie10)
                                                <button type="button" wire:click="seleccionarDiagnosticoEgresoCie10({{ $diagnosticoCie10->id }})">
                                                    <strong>{{ $diagnosticoCie10->codigo }}</strong>
                                                    <span>{{ $diagnosticoCie10->nombre }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron diagnosticos CIE10.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('diagnosticoEgresoForm.diagnostico_cie10_id') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Clasificacion</span>
                                    <select wire:model="diagnosticoEgresoForm.clasificacion">
                                        <option value="">Seleccione</option>
                                        @foreach ($clasificacionesDiagnosticoEgreso as $clasificacion)
                                            <option value="{{ $clasificacion->value }}">{{ $clasificacion->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('diagnosticoEgresoForm.clasificacion') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Tipo</span>
                                    <select wire:model="diagnosticoEgresoForm.tipo_diagnostico">
                                        <option value="">Seleccione</option>
                                        @foreach ($tiposDiagnostico as $tipoDiagnostico)
                                            <option value="{{ $tipoDiagnostico->value }}">{{ $tipoDiagnostico->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('diagnosticoEgresoForm.tipo_diagnostico') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Observacion</span>
                                    <input type="text" wire:model.blur="diagnosticoEgresoForm.observaciones" placeholder="Ej. Alta con indicaciones">
                                    @error('diagnosticoEgresoForm.observaciones') <small>{{ $message }}</small> @enderror
                                </label>
                                <div>
                                    <button type="button" class="emergency-close-action" wire:click="$set('formularioDiagnosticoEgresoAbierto', false)">Cancelar</button>
                                    <button type="button" class="catalog-primary-action" wire:click="guardarDiagnosticoEgreso">
                                        <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endif
                    </article>
                </section>
            @elseif ($tabActiva === 'ordenes')
                <section class="emergency-diagnosis-stack emergency-orders-stack">
                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:clipboard-plus" aria-hidden="true"></iconify-icon>
                                Ordenes
                            </h2>

                            <div class="emergency-section-actions">
                                @if ($atencion)
                                    <a
                                        href="{{ route('historia-clinica.emergencia.ordenes.pdf', $atencion) }}"
                                        class="emergency-inline-action"
                                        target="_blank"
                                        rel="noopener"
                                        title="Genera Receta"
                                    >
                                        <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                        Genera Receta
                                    </a>
                                @else
                                    <button type="button" class="emergency-inline-action" title="Guarde la atención para generar receta" disabled>
                                        <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                        Genera Receta
                                    </button>
                                @endif

                                @if (! $soloLectura)
                                    <button type="button" class="emergency-inline-action" wire:click="abrirNuevaOrden">
                                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                        Nuevo
                                    </button>
                                @endif
                            </div>
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-orders-table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Detalle</th>
                                        <th>Prioridad</th>
                                        <th>Fecha / Hora</th>
                                        <th>Resultado</th>
                                        <th>Estado</th>
                                        <th>Profesional</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ordenesClinicas as $orden)
                                        <tr wire:key="orden-clinica-{{ $orden['id'] }}">
                                            <td>{{ $orden['tipo'] }}</td>
                                            <td>{{ $orden['detalle'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $orden['prioridad'] }}</span></td>
                                            <td>{{ $orden['fecha_hora'] }}</td>
                                            <td>{{ $orden['resultado'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $orden['estado'] }}</span></td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $orden['iniciales'] }}</strong>
                                                    {{ $orden['profesional'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="emergency-row-actions">
                                                    <button type="button" wire:click="abrirModalDocumentosOrden({{ $orden['id'] }})" title="Cargar archivos de la orden">
                                                        <iconify-icon icon="lucide:file-up" aria-hidden="true"></iconify-icon>
                                                    </button>
                                                    @if (! $soloLectura)
                                                        <button type="button" wire:click="editarOrden({{ $orden['id'] }})" title="Editar orden">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $orden['id'] }}" data-registro-nombre="{{ $orden['detalle'] }}" data-catalogo-singular="orden" data-delete-method="eliminarOrden" data-confirm-title="Eliminar orden" data-confirm-message="Deseas eliminar esta orden?" title="Eliminar orden">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:clipboard-x" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado ordenes.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioOrdenAbierto)
                            <div class="emergency-vital-form emergency-order-form">
                                <div class="emergency-order-form-row">
                                    <label>
                                        <span>Tipo</span>
                                        <select wire:model="ordenForm.tipo_orden_id">
                                            <option value="">Seleccione</option>
                                            @foreach ($catalogoTiposOrdenes as $tipoOrden)
                                                <option value="{{ $tipoOrden->id }}">{{ $tipoOrden->descripcion }}</option>
                                            @endforeach
                                        </select>
                                        @error('ordenForm.tipo_orden_id') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label class="emergency-combobox emergency-order-detail-field">
                                        <span>Detalle</span>
                                        <div class="emergency-combobox-field">
                                            <input type="search" wire:model.live.debounce.250ms="ordenProcedimientoBusqueda" placeholder="Buscar o escribir procedimiento / servicio">
                                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                        </div>
                                        @if ($ordenProcedimientoBusqueda !== '' && blank($ordenForm['procedimiento_id']))
                                            <div class="emergency-combobox-list emergency-order-procedure-list">
                                                @forelse ($procedimientosOrden as $procedimientoOrden)
                                                    <button type="button" wire:click="seleccionarProcedimientoOrden({{ $procedimientoOrden->id }})">
                                                        <strong>{{ $procedimientoOrden->codigo }}</strong>
                                                        <span>{{ $procedimientoOrden->denominacion }}</span>
                                                    </button>
                                                @empty
                                                    <p>No se encontraron procedimientos. Se registrará como nuevo servicio.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                        @error('ordenForm.detalle') <small>{{ $message }}</small> @enderror
                                        @error('ordenForm.procedimiento_id') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Prioridad</span>
                                        <select wire:model="ordenForm.prioridad">
                                            <option value="">Seleccione</option>
                                            @foreach ($prioridadesOrden as $prioridadOrden)
                                                <option value="{{ $prioridadOrden->value }}">{{ $prioridadOrden->value }}</option>
                                            @endforeach
                                        </select>
                                        @error('ordenForm.prioridad') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Fecha / Hora</span>
                                        <input type="datetime-local" wire:model.blur="ordenForm.fecha_hora_solicitud">
                                        @error('ordenForm.fecha_hora_solicitud') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label>
                                        <span>Estado</span>
                                        <select wire:model="ordenForm.estado">
                                            <option value="">Seleccione</option>
                                            @foreach ($estadosSolicitudExamen as $estadoExamen)
                                                <option value="{{ $estadoExamen->value }}">{{ $estadoExamen->value }}</option>
                                            @endforeach
                                        </select>
                                        @error('ordenForm.estado') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <label class="emergency-combobox emergency-order-professional-field">
                                        <span>Profesional</span>
                                        <div class="emergency-combobox-field">
                                            <input type="search" wire:model.live.debounce.250ms="ordenProfesionalBusqueda" placeholder="Buscar profesional por nombre, especialidad o CMP">
                                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                        </div>
                                        @if ($ordenProfesionalBusqueda !== '' && blank($ordenForm['profesional_salud_id']))
                                            <div class="emergency-combobox-list emergency-order-professional-list">
                                                @forelse ($profesionalesOrden as $profesional)
                                                    <button type="button" wire:click="seleccionarProfesionalOrden({{ $profesional->id }})">
                                                        <strong>{{ $profesional->nombres }}</strong>
                                                        <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                                    </button>
                                                @empty
                                                    <p>No se encontraron profesionales.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                        @error('ordenForm.profesional_salud_id') <small>{{ $message }}</small> @enderror
                                    </label>
                                    <div class="emergency-order-form-actions">
                                        <button type="button" class="emergency-close-action" wire:click="$set('formularioOrdenAbierto', false)">Cancelar</button>
                                        <button type="button" class="catalog-primary-action" wire:click="guardarOrden">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                                <label class="is-full">
                                    <span>Resultado</span>
                                    <textarea rows="2" wire:model.blur="ordenForm.resultado" placeholder="Ej. Pendiente"></textarea>
                                    @error('ordenForm.resultado') <small>{{ $message }}</small> @enderror
                                </label>
                            </div>
                        @endif
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:microscope" aria-hidden="true"></iconify-icon>
                                Examenes de Laboratorio
                            </h2>

                            <div class="emergency-section-actions">
                                @if ($atencion)
                                    <a
                                        href="{{ route('historia-clinica.emergencia.examenes.pdf', $atencion) }}"
                                        class="emergency-inline-action"
                                        target="_blank"
                                        rel="noopener"
                                        title="Genera Receta"
                                    >
                                        <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                        Genera Receta
                                    </a>
                                    <a
                                        href="{{ route('historia-clinica.emergencia.laboratorio.pdf', $atencion) }}"
                                        class="emergency-inline-action emergency-lab-pdf-action"
                                        target="_blank"
                                        rel="noopener"
                                        title="Ver exámenes"
                                        data-emergency-lab-pdf
                                    >
                                        <iconify-icon class="emergency-lab-pdf-icon" icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                        <span class="emergency-lab-pdf-spinner" aria-hidden="true"></span>
                                        <span class="emergency-lab-pdf-label">Ver exámenes</span>
                                    </a>
                                @else
                                    <button type="button" class="emergency-inline-action" title="Guarde la atención para generar receta" disabled>
                                        <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                        Genera Receta
                                    </button>
                                    <button type="button" class="emergency-inline-action" title="Guarde la atención para ver exámenes" disabled>
                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                        Ver exámenes
                                    </button>
                                @endif

                                @if (! $soloLectura)
                                    <button type="button" class="emergency-inline-action" wire:click="abrirNuevaSolicitudExamen">
                                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                        Nuevo
                                    </button>
                                @endif
                            </div>
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-lab-table">
                                <thead>
                                    <tr>
                                        <th>Examen</th>
                                        <th>Prioridad</th>
                                        <th>Fecha / Hora</th>
                                        <th>Resultado</th>
                                        <th>Estado</th>
                                        <th>Profesional</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($solicitudesExamenes as $solicitud)
                                        <tr wire:key="solicitud-examen-{{ $solicitud['id'] }}">
                                            <td>{{ $solicitud['examen'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $solicitud['prioridad'] }}</span></td>
                                            <td>{{ $solicitud['fecha_hora'] }}</td>
                                            <td>{{ $solicitud['resultado'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $solicitud['estado'] }}</span></td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $solicitud['iniciales'] }}</strong>
                                                    {{ $solicitud['profesional'] }}
                                                </span>
                                            </td>
                                            <td>
                                                @if (! $soloLectura)
                                                    <div class="emergency-row-actions">
                                                        <button type="button" wire:click="editarSolicitudExamen({{ $solicitud['id'] }})" title="Editar examen de laboratorio">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $solicitud['id'] }}" data-registro-nombre="{{ $solicitud['examen'] }}" data-catalogo-singular="examen de laboratorio" data-delete-method="eliminarSolicitudExamen" data-confirm-title="Eliminar examen de laboratorio" data-confirm-message="Deseas eliminar el examen de laboratorio {{ $solicitud['examen'] }}?" title="Eliminar examen de laboratorio">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:file-search" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado examenes de laboratorio.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioSolicitudExamenAbierto)
                            <div class="emergency-vital-form emergency-lab-form">
                                <div class="emergency-lab-form-row is-primary">
                                    <label class="emergency-combobox emergency-lab-exam-field">
                                        <span>Examen</span>
                                        <div class="emergency-combobox-field">
                                            <input type="search" wire:model.live.debounce.250ms="solicitudExamenBusqueda" placeholder="Seleccione">
                                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                        </div>
                                        @if ($solicitudExamenBusqueda !== '' && blank($solicitudExamenForm['examen_id']))
                                            <div class="emergency-combobox-list emergency-lab-exam-list">
                                                @forelse ($examenesLaboratorio as $examen)
                                                    <button type="button" wire:click="seleccionarExamenLaboratorio({{ $examen->id }})">
                                                        <strong>{{ $examen->descripcion }}</strong>
                                                    </button>
                                                @empty
                                                    <p>No se encontraron examenes.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                        @error('solicitudExamenForm.examen_id') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Fecha / Hora</span>
                                        <input type="datetime-local" wire:model.blur="solicitudExamenForm.fecha_hora_solicitud">
                                        @error('solicitudExamenForm.fecha_hora_solicitud') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label class="emergency-combobox emergency-lab-professional-field">
                                        <span>Profesional</span>
                                        <div class="emergency-combobox-field">
                                            <input type="search" wire:model.live.debounce.250ms="solicitudExamenProfesionalBusqueda" placeholder="Buscar profesional por nombre, especialidad o CMP">
                                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                        </div>
                                        @if ($solicitudExamenProfesionalBusqueda !== '' && blank($solicitudExamenForm['profesional_salud_id']))
                                            <div class="emergency-combobox-list emergency-lab-professional-list">
                                                @forelse ($profesionalesSolicitudExamen as $profesional)
                                                    <button type="button" wire:click="seleccionarProfesionalSolicitudExamen({{ $profesional->id }})">
                                                        <strong>{{ $profesional->nombres }}</strong>
                                                        <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                                    </button>
                                                @empty
                                                    <p>No se encontraron profesionales.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                        @error('solicitudExamenForm.profesional_salud_id') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <div class="emergency-lab-form-actions">
                                        <button type="button" class="emergency-close-action" wire:click="$set('formularioSolicitudExamenAbierto', false)">Cancelar</button>
                                        <button type="button" class="catalog-primary-action" wire:click="guardarSolicitudExamen">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    </div>
                                </div>

                                <div class="emergency-lab-form-row is-secondary">
                                    <label>
                                        <span>Prioridad</span>
                                        <select wire:model="solicitudExamenForm.prioridad">
                                            <option value="">Seleccione</option>
                                            @foreach ($prioridadesSolicitudExamen as $prioridadExamen)
                                                <option value="{{ $prioridadExamen->value }}">{{ $prioridadExamen->value }}</option>
                                            @endforeach
                                        </select>
                                        @error('solicitudExamenForm.prioridad') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Estado</span>
                                        <select wire:model="solicitudExamenForm.estado">
                                            <option value="">Seleccione</option>
                                            @foreach ($estadosSolicitudExamen as $estadoExamen)
                                                <option value="{{ $estadoExamen->value }}">{{ $estadoExamen->value }}</option>
                                            @endforeach
                                        </select>
                                        @error('solicitudExamenForm.estado') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label class="is-result">
                                        <span>Resultado</span>
                                        <textarea rows="3" wire:model.blur="solicitudExamenForm.resultado" placeholder="Ej. Pendiente"></textarea>
                                        @error('solicitudExamenForm.resultado') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            </div>
                        @endif
                    </article>
                </section>
            @elseif ($tabActiva === 'medicamentos')
                <section class="emergency-diagnosis-stack emergency-medications-stack">
                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="healthicons:medicines-outline" aria-hidden="true"></iconify-icon>
                                Prescripción de Medicamentos
                            </h2>

                            <div class="emergency-section-actions">
                                @if ($atencion)
                                    <a
                                        href="{{ route('historia-clinica.emergencia.medicamentos.pdf', $atencion) }}"
                                        class="emergency-inline-action"
                                        target="_blank"
                                        rel="noopener"
                                        title="Genera Receta"
                                    >
                                        <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                        Genera Receta
                                    </a>
                                @else
                                    <button type="button" class="emergency-inline-action" title="Guarde la atención para generar receta" disabled>
                                        <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                        Genera Receta
                                    </button>
                                @endif

                                @if (! $soloLectura)
                                    <button type="button" class="emergency-inline-action" wire:click="abrirNuevaPrescripcion">
                                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                        Nuevo
                                    </button>
                                @endif
                            </div>
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-medications-table">
                                <thead>
                                    <tr>
                                        <th>Medicamento</th>
                                        <th>Presentación</th>
                                        <th>Dosis</th>
                                        <th>Vía</th>
                                        <th>Duración</th>
                                        <th>Cantidad</th>
                                        <th>Fecha / Hora</th>
                                        <th>Estado</th>
                                        <th>Profesional</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($prescripcionesMedicamentos as $prescripcion)
                                        <tr wire:key="prescripcion-{{ $prescripcion['id'] }}">
                                            <td>
                                                <div class="emergency-main-cell">
                                                    <strong>{{ $prescripcion['medicamento'] }}</strong>
                                                    <span>{{ $prescripcion['origen'] }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $prescripcion['presentacion'] }}</td>
                                            <td>{{ $prescripcion['dosis'] }}</td>
                                            <td>{{ $prescripcion['via'] }}</td>
                                            <td>{{ $prescripcion['duracion'] }}</td>
                                            <td>{{ $prescripcion['cantidad'] }}</td>
                                            <td>{{ $prescripcion['fecha_hora'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $prescripcion['estado'] }}</span></td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $prescripcion['iniciales'] }}</strong>
                                                    {{ $prescripcion['profesional'] }}
                                                </span>
                                            </td>
                                            <td>
                                                @if (! $soloLectura)
                                                    <div class="emergency-row-actions">
                                                        <button type="button" wire:click="editarPrescripcion({{ $prescripcion['id'] }})" title="Editar prescripción">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $prescripcion['id'] }}" data-registro-nombre="{{ $prescripcion['medicamento'] }}" data-catalogo-singular="prescripción" data-delete-method="eliminarPrescripcion" data-confirm-title="Eliminar prescripción" data-confirm-message="Deseas eliminar la prescripción de {{ $prescripcion['medicamento'] }}?" title="Eliminar prescripción">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:pill" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado prescripciones de medicamentos.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioPrescripcionAbierto)
                            <div class="emergency-vital-form emergency-medication-form">
                                <div class="emergency-medication-form-row is-primary">
                                    <label class="emergency-combobox emergency-medication-name-field">
                                        <span>Medicamento</span>
                                        <div class="emergency-combobox-field">
                                            <input type="search" wire:model.live.debounce.250ms="prescripcionMedicamentoBusqueda" placeholder="Buscar o escribir medicamento">
                                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                        </div>
                                        @if ($prescripcionMedicamentoBusqueda !== '' && blank($prescripcionForm['medicamento_id']))
                                            <div class="emergency-combobox-list emergency-medication-list">
                                                @forelse ($medicamentosPrescripcion as $medicamento)
                                                    <button type="button" wire:click="seleccionarMedicamentoPrescripcion({{ $medicamento->id }})">
                                                        <strong>{{ $medicamento->descripcion }}</strong>
                                                        <span>{{ collect([$medicamento->presentacion, $medicamento->generico, 'Stock: '.$medicamento->stock])->filter()->implode(' · ') }}</span>
                                                    </button>
                                                @empty
                                                    <p>No se encontraron medicamentos. Se guardará como texto libre.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                        @error('prescripcionForm.medicamento') <small>{{ $message }}</small> @enderror
                                        @error('prescripcionForm.medicamento_id') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Presentación</span>
                                        <input type="text" wire:model.blur="prescripcionForm.presentacion" placeholder="Ej. Vial 2 g">
                                        @error('prescripcionForm.presentacion') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Dosis</span>
                                        <input type="text" wire:model.blur="prescripcionForm.dosis" placeholder="Ej. 1 g c/12 h">
                                        @error('prescripcionForm.dosis') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Vía</span>
                                        <input type="text" wire:model.blur="prescripcionForm.via" placeholder="Ej. EV">
                                        @error('prescripcionForm.via') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Duración</span>
                                        <input type="text" wire:model.blur="prescripcionForm.duracion" placeholder="Ej. 7 días">
                                        @error('prescripcionForm.duracion') <small>{{ $message }}</small> @enderror
                                    </label>
                                </div>

                                <div class="emergency-medication-form-row is-secondary">
                                    <label>
                                        <span>Cantidad</span>
                                        <input type="number" min="1" wire:model.blur="prescripcionForm.cantidad" placeholder="Ej. 2">
                                        @error('prescripcionForm.cantidad') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Fecha / Hora</span>
                                        <input type="datetime-local" wire:model.blur="prescripcionForm.fecha_hora">
                                        @error('prescripcionForm.fecha_hora') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label>
                                        <span>Estado</span>
                                        <select wire:model="prescripcionForm.estado">
                                            <option value="">Seleccione</option>
                                            @foreach ($estadosSolicitudExamen as $estadoExamen)
                                                <option value="{{ $estadoExamen->value }}">{{ $estadoExamen->value }}</option>
                                            @endforeach
                                        </select>
                                        @error('prescripcionForm.estado') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <label class="emergency-combobox emergency-medication-professional-field">
                                        <span>Profesional</span>
                                        <div class="emergency-combobox-field">
                                            <input type="search" wire:model.live.debounce.250ms="prescripcionProfesionalBusqueda" placeholder="Buscar profesional por nombre, especialidad o CMP">
                                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                        </div>
                                        @if ($prescripcionProfesionalBusqueda !== '' && blank($prescripcionForm['profesional_salud_id']))
                                            <div class="emergency-combobox-list emergency-medication-professional-list">
                                                @forelse ($profesionalesPrescripcion as $profesional)
                                                    <button type="button" wire:click="seleccionarProfesionalPrescripcion({{ $profesional->id }})">
                                                        <strong>{{ $profesional->nombres }}</strong>
                                                        <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                                    </button>
                                                @empty
                                                    <p>No se encontraron profesionales.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                        @error('prescripcionForm.profesional_salud_id') <small>{{ $message }}</small> @enderror
                                    </label>

                                    <div class="emergency-medication-form-actions">
                                        <button type="button" class="emergency-close-action" wire:click="$set('formularioPrescripcionAbierto', false)">Cancelar</button>
                                        <button type="button" class="catalog-primary-action" wire:click="guardarPrescripcion">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:clipboard-check" aria-hidden="true"></iconify-icon>
                                Procedimientos Realizados
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevoProcedimiento">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nuevo
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-procedures-table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Procedimiento</th>
                                        <th>Sesiones</th>
                                        <th>Fecha / Hora</th>
                                        <th>Estado</th>
                                        <th>Origen</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($procedimientosRealizados as $procedimiento)
                                        <tr wire:key="procedimiento-realizado-{{ $procedimiento['id'] }}">
                                            <td><span class="emergency-code-chip">{{ $procedimiento['codigo'] }}</span></td>
                                            <td>{{ $procedimiento['denominacion'] }}</td>
                                            <td>{{ $procedimiento['sesiones'] }}</td>
                                            <td>{{ $procedimiento['fecha_hora'] }}</td>
                                            <td><span class="emergency-table-badge">{{ $procedimiento['estado'] }}</span></td>
                                            <td><span class="emergency-table-badge">{{ $procedimiento['origen'] }}</span></td>
                                            <td>
                                                @if (! $soloLectura)
                                                    <div class="emergency-row-actions">
                                                        <button type="button" wire:click="editarProcedimiento({{ $procedimiento['id'] }})" title="Editar procedimiento">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $procedimiento['id'] }}" data-registro-nombre="{{ $procedimiento['denominacion'] }}" data-catalogo-singular="procedimiento" data-delete-method="eliminarProcedimiento" data-confirm-title="Eliminar procedimiento" data-confirm-message="Deseas eliminar el procedimiento {{ $procedimiento['denominacion'] }}?" title="Eliminar procedimiento">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:clipboard-x" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado procedimientos realizados.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioProcedimientoAbierto)
                            <div class="emergency-vital-form emergency-procedure-form">
                                <label class="emergency-combobox emergency-procedure-name-field">
                                    <span>Procedimiento</span>
                                    <div class="emergency-combobox-field">
                                        <input type="search" wire:model.live.debounce.250ms="procedimientoBusqueda" placeholder="Buscar o escribir procedimiento">
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if ($procedimientoBusqueda !== '' && blank($procedimientoForm['procedimiento_id']))
                                        <div class="emergency-combobox-list emergency-procedure-list">
                                            @forelse ($procedimientosCatalogo as $procedimientoCatalogo)
                                                <button type="button" wire:click="seleccionarProcedimientoRealizado({{ $procedimientoCatalogo->id }})">
                                                    <strong>{{ $procedimientoCatalogo->codigo }}</strong>
                                                    <span>{{ $procedimientoCatalogo->denominacion }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron procedimientos. Se guardará como texto libre.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('procedimientoForm.denominacion') <small>{{ $message }}</small> @enderror
                                    @error('procedimientoForm.procedimiento_id') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Sesiones</span>
                                    <input type="number" min="1" wire:model.blur="procedimientoForm.sesiones" placeholder="Ej. 1">
                                    @error('procedimientoForm.sesiones') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Fecha / Hora</span>
                                    <input type="datetime-local" wire:model.blur="procedimientoForm.fecha_hora">
                                    @error('procedimientoForm.fecha_hora') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Estado</span>
                                    <select wire:model="procedimientoForm.estado">
                                        <option value="">Seleccione</option>
                                        @foreach ($estadosSolicitudExamen as $estadoExamen)
                                            <option value="{{ $estadoExamen->value }}">{{ $estadoExamen->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('procedimientoForm.estado') <small>{{ $message }}</small> @enderror
                                </label>

                                <div class="emergency-procedure-form-actions">
                                    <button type="button" class="emergency-close-action" wire:click="$set('formularioProcedimientoAbierto', false)">Cancelar</button>
                                    <button type="button" class="catalog-primary-action" wire:click="guardarProcedimiento">
                                        <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endif
                    </article>
                </section>
            @elseif ($tabActiva === 'enfermeria')
                <section class="emergency-nursing-grid">
                    <article class="emergency-attention-card emergency-clinical-table-card emergency-nursing-notes-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:list-checks" aria-hidden="true"></iconify-icon>
                                Notas de Enfermería
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevaNotaEnfermeria">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nuevo
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-nursing-table">
                                <thead>
                                    <tr>
                                        <th>Fecha / Hora</th>
                                        <th>Profesional</th>
                                        <th>Nota</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($notasEnfermeria as $notaEnfermeria)
                                        <tr wire:key="nota-enfermeria-{{ $notaEnfermeria['id'] }}">
                                            <td>
                                                <div class="emergency-date-cell">
                                                    <strong>{{ $notaEnfermeria['fecha'] }}</strong>
                                                    <span>{{ $notaEnfermeria['hora'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $notaEnfermeria['iniciales'] }}</strong>
                                                    {{ $notaEnfermeria['profesional'] }}
                                                </span>
                                            </td>
                                            <td>{{ $notaEnfermeria['nota'] }}</td>
                                            <td>
                                                <div class="emergency-row-actions">
                                                    @if (! $soloLectura)
                                                        <button type="button" wire:click="editarNotaEnfermeria({{ $notaEnfermeria['id'] }})" title="Editar nota de enfermería">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $notaEnfermeria['id'] }}" data-registro-nombre="{{ $notaEnfermeria['fecha'] }} {{ $notaEnfermeria['hora'] }}" data-catalogo-singular="nota de enfermería" data-delete-method="eliminarNotaEnfermeria" data-confirm-title="Eliminar nota de enfermería" data-confirm-message="Deseas eliminar esta nota de enfermería?" title="Eliminar nota de enfermería">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    @endif
                                                    <button type="button" wire:click="verNotaEnfermeria({{ $notaEnfermeria['id'] }})" title="Ver nota de enfermería">
                                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:file-heart" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado notas de enfermería.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioNotaEnfermeriaAbierto)
                            <div class="emergency-vital-form emergency-nursing-form">
                                <label>
                                    <span>Fecha / Hora</span>
                                    <input type="datetime-local" wire:model.blur="notaEnfermeriaForm.fecha_hora" @readonly($notaEnfermeriaSoloLectura)>
                                    @error('notaEnfermeriaForm.fecha_hora') <small>{{ $message }}</small> @enderror
                                </label>

                                <label class="emergency-combobox emergency-nursing-professional-field">
                                    <span>Profesional</span>
                                    <div class="emergency-combobox-field">
                                        <input
                                            type="search"
                                            wire:model.live.debounce.250ms="profesionalEnfermeriaBusqueda"
                                            placeholder="Buscar por nombre, especialidad o CMP"
                                            @readonly($notaEnfermeriaSoloLectura)
                                        >
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if (! $notaEnfermeriaSoloLectura && $profesionalEnfermeriaBusqueda !== '' && blank($notaEnfermeriaForm['profesional_salud_id']))
                                        <div class="emergency-combobox-list emergency-nursing-professional-list">
                                            @forelse ($profesionalesEnfermeria as $profesional)
                                                <button type="button" wire:click="seleccionarProfesionalEnfermeria({{ $profesional->id }})">
                                                    <strong>{{ $profesional->nombres }}</strong>
                                                    <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron profesionales.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('notaEnfermeriaForm.profesional_salud_id') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Firma</span>
                                    <input type="text" wire:model.blur="notaEnfermeriaForm.firma" placeholder="Ej. Lic. Martha" @readonly($notaEnfermeriaSoloLectura)>
                                    @error('notaEnfermeriaForm.firma') <small>{{ $message }}</small> @enderror
                                </label>

                                <label class="is-full">
                                    <span>Nota</span>
                                    <textarea rows="4" wire:model.blur="notaEnfermeriaForm.nota" placeholder="Describa la nota de enfermería" @readonly($notaEnfermeriaSoloLectura)></textarea>
                                    @error('notaEnfermeriaForm.nota') <small>{{ $message }}</small> @enderror
                                </label>

                                <div class="emergency-nursing-form-actions">
                                    <button type="button" class="emergency-close-action" wire:click="$set('formularioNotaEnfermeriaAbierto', false)">Cerrar</button>
                                    @if (! $notaEnfermeriaSoloLectura)
                                        <button type="button" class="catalog-primary-action" wire:click="guardarNotaEnfermeria">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </article>

                    <article class="emergency-attention-card emergency-water-balance-card">
                        <header>
                            <h2>
                                <iconify-icon icon="lucide:hourglass" aria-hidden="true"></iconify-icon>
                                Balance Hídrico
                            </h2>
                        </header>

                        <div class="emergency-water-balance-fields">
                            <label class="is-income">
                                <span>
                                    <iconify-icon icon="lucide:arrow-down" aria-hidden="true"></iconify-icon>
                                    Ingresos Totales
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="form.ingresos" placeholder="1500 ml" @readonly($soloLectura)>
                                @error('form.ingresos') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="is-output">
                                <span>
                                    <iconify-icon icon="lucide:arrow-up" aria-hidden="true"></iconify-icon>
                                    Egresos Totales
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="form.egresos" placeholder="1200 ml" @readonly($soloLectura)>
                                @error('form.egresos') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>

                        <div class="emergency-water-balance-result {{ $balanceHidrico['clase'] }}">
                            <span>Balance Calculado</span>
                            <strong>{{ $balanceHidrico['balance'] }}</strong>
                            <em>{{ $balanceHidrico['estado'] }}</em>
                        </div>
                    </article>
                </section>
            @elseif ($tabActiva === 'interconsultas')
                <section class="emergency-diagnosis-stack emergency-interconsultations-stack">
                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:messages-square" aria-hidden="true"></iconify-icon>
                                Registro de interconsultas
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevaInterconsulta">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nuevo
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-interconsultations-table">
                                <thead>
                                    <tr>
                                        <th>Fecha / Hora</th>
                                        <th>Profesional</th>
                                        <th>Nota</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($interconsultasClinicas as $interconsulta)
                                        <tr wire:key="interconsulta-{{ $interconsulta['id'] }}">
                                            <td>
                                                <div class="emergency-date-cell">
                                                    <strong>{{ $interconsulta['fecha'] }}</strong>
                                                    <span>{{ $interconsulta['hora'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $interconsulta['iniciales'] }}</strong>
                                                    <span class="emergency-professional-detail">
                                                        <b>{{ $interconsulta['profesional'] }}</b>
                                                        <em>{{ $interconsulta['especialidad'] }}</em>
                                                    </span>
                                                </span>
                                            </td>
                                            <td>{{ $interconsulta['nota'] }}</td>
                                            <td>
                                                <div class="emergency-row-actions">
                                                    <button type="button" wire:click="verInterconsulta({{ $interconsulta['id'] }})" title="Ver interconsulta">
                                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                                    </button>
                                                    @if (! $soloLectura)
                                                        <button type="button" wire:click="editarInterconsulta({{ $interconsulta['id'] }})" title="Editar interconsulta">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:message-square-x" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado interconsultas.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioInterconsultaAbierto)
                            <div class="emergency-vital-form emergency-interconsultation-form">
                                <label>
                                    <span>Fecha / Hora</span>
                                    <input type="datetime-local" wire:model.blur="interconsultaForm.fecha_hora" @readonly($interconsultaSoloLectura)>
                                    @error('interconsultaForm.fecha_hora') <small>{{ $message }}</small> @enderror
                                </label>

                                <label class="emergency-combobox emergency-interconsultation-professional-field">
                                    <span>Profesional</span>
                                    <div class="emergency-combobox-field">
                                        <input
                                            type="search"
                                            wire:model.live.debounce.250ms="profesionalInterconsultaBusqueda"
                                            placeholder="Buscar por nombre, especialidad o CMP"
                                            @readonly($interconsultaSoloLectura)
                                        >
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if (! $interconsultaSoloLectura && $profesionalInterconsultaBusqueda !== '' && blank($interconsultaForm['profesional_salud_id']))
                                        <div class="emergency-combobox-list emergency-interconsultation-professional-list">
                                            @forelse ($profesionalesInterconsulta as $profesional)
                                                <button type="button" wire:click="seleccionarProfesionalInterconsulta({{ $profesional->id }})">
                                                    <strong>{{ $profesional->nombres }}</strong>
                                                    <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron profesionales.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('interconsultaForm.profesional_salud_id') <small>{{ $message }}</small> @enderror
                                </label>

                                <label class="is-full">
                                    <span>Nota</span>
                                    <textarea rows="4" wire:model.blur="interconsultaForm.nota" placeholder="Describa la indicación o nota de interconsulta" @readonly($interconsultaSoloLectura)></textarea>
                                    @error('interconsultaForm.nota') <small>{{ $message }}</small> @enderror
                                </label>

                                <div class="emergency-interconsultation-form-actions">
                                    <button type="button" class="emergency-close-action" wire:click="$set('formularioInterconsultaAbierto', false)">
                                        {{ $interconsultaSoloLectura ? 'Cerrar' : 'Cancelar' }}
                                    </button>
                                    @if (! $interconsultaSoloLectura)
                                        <button type="button" class="catalog-primary-action" wire:click="guardarInterconsulta">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </article>
                </section>
            @elseif ($tabActiva === 'movimientos')
                <section class="emergency-diagnosis-stack emergency-movements-stack">
                    <article class="emergency-attention-card emergency-trauma-card">
                        <header>
                            <h2>
                                <iconify-icon icon="lucide:briefcase-medical" aria-hidden="true"></iconify-icon>
                                Trauma Shock
                            </h2>
                        </header>

                        <div class="emergency-trauma-form">
                            <label>
                                <span>Ingresó a Trauma Shock</span>
                                <select wire:model.live="form.en_shock_trauma" @disabled($soloLectura)>
                                    <option value="Si">SI</option>
                                    <option value="No">NO</option>
                                </select>
                                @error('form.en_shock_trauma') <small>{{ $message }}</small> @enderror
                            </label>

                            <label>
                                <span>Fecha / Hora de Ingreso</span>
                                <input type="datetime-local" wire:model.blur="form.shock_trauma_fecha_hora" @readonly($soloLectura)>
                                @error('form.shock_trauma_fecha_hora') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="emergency-combobox">
                                <span>Responsable</span>
                                <div class="emergency-combobox-field">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.250ms="shockTraumaResponsableBusqueda"
                                        placeholder="Buscar profesional o ingresar texto libre"
                                        @readonly($soloLectura)
                                    >
                                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                </div>
                                @if (! $soloLectura && $shockTraumaResponsableBusqueda !== '')
                                    <div class="emergency-combobox-list emergency-free-professional-list">
                                        @forelse ($profesionalesShockTrauma as $profesional)
                                            <button type="button" wire:click="seleccionarResponsableShockTrauma({{ $profesional->id }})">
                                                <strong>{{ $profesional->nombres }}</strong>
                                                <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                            </button>
                                        @empty
                                            <p>No se encontraron profesionales. Se guardará como texto libre.</p>
                                        @endforelse
                                    </div>
                                @endif
                                @error('form.shock_trauma_responsable') <small>{{ $message }}</small> @enderror
                            </label>

                            <label>
                                <span>Resultado de la atención</span>
                                <select wire:model="form.shock_trauma_resultado" @disabled($soloLectura)>
                                    <option value="">Seleccione</option>
                                    @foreach ($resultadosShockTrauma as $resultado)
                                        <option value="{{ $resultado }}">{{ $resultado }}</option>
                                    @endforeach
                                </select>
                                @error('form.shock_trauma_resultado') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="is-wide">
                                <span>Destino Post Trauma Shock</span>
                                <select wire:model="form.shock_trauma_destino" @disabled($soloLectura)>
                                    <option value="">Seleccione</option>
                                    @foreach ($destinosShockTrauma as $destino)
                                        <option value="{{ $destino }}">{{ $destino }}</option>
                                    @endforeach
                                </select>
                                @error('form.shock_trauma_destino') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="is-full">
                                <span>Registro especializado <em>Notas Médicas</em></span>
                                <textarea rows="4" wire:model.blur="form.registro_especializado" placeholder="Ingrese detalles clínicos relevantes durante la estancia en Trauma Shock..." @readonly($soloLectura)></textarea>
                                @error('form.registro_especializado') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:person-standing" aria-hidden="true"></iconify-icon>
                                Transferencias y Movimientos Internos
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevaTransferencia">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nuevo
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-transfers-table">
                                <thead>
                                    <tr>
                                        <th>Fecha / Hora</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Motivo</th>
                                        <th>Responsable</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transferenciasClinicas as $transferencia)
                                        <tr wire:key="transferencia-{{ $transferencia['id'] }}">
                                            <td>{{ $transferencia['fecha_hora'] }}</td>
                                            <td>{{ $transferencia['origen'] }}</td>
                                            <td>{{ $transferencia['destino'] }}</td>
                                            <td>{{ $transferencia['motivo'] }}</td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $transferencia['iniciales'] }}</strong>
                                                    {{ $transferencia['responsable'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="emergency-row-actions">
                                                    <button type="button" wire:click="verTransferencia({{ $transferencia['id'] }})" title="Ver transferencia">
                                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                                    </button>
                                                    @if (! $soloLectura)
                                                        <button type="button" wire:click="editarTransferencia({{ $transferencia['id'] }})" title="Editar transferencia">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $transferencia['id'] }}" data-registro-nombre="{{ $transferencia['destino'] }}" data-catalogo-singular="transferencia" data-delete-method="eliminarTransferencia" data-confirm-title="Eliminar transferencia" data-confirm-message="Deseas eliminar esta transferencia?" title="Eliminar transferencia">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:route-off" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado transferencias o movimientos internos.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioTransferenciaAbierto)
                            <div class="emergency-vital-form emergency-transfer-form">
                                <label>
                                    <span>Fecha / Hora</span>
                                    <input type="datetime-local" wire:model.blur="transferenciaForm.fecha_hora" @readonly($transferenciaSoloLectura)>
                                    @error('transferenciaForm.fecha_hora') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Origen</span>
                                    <input type="text" wire:model.blur="transferenciaForm.origen" placeholder="Ej. Emergencia" @readonly($transferenciaSoloLectura)>
                                    @error('transferenciaForm.origen') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Destino</span>
                                    <input type="text" wire:model.blur="transferenciaForm.destino" placeholder="Ej. UCI" @readonly($transferenciaSoloLectura)>
                                    @error('transferenciaForm.destino') <small>{{ $message }}</small> @enderror
                                </label>
                                <label class="emergency-combobox emergency-transfer-responsible-field">
                                    <span>Responsable</span>
                                    <div class="emergency-combobox-field">
                                        <input
                                            type="search"
                                            wire:model.live.debounce.250ms="transferenciaResponsableBusqueda"
                                            placeholder="Buscar profesional o ingresar texto libre"
                                            @readonly($transferenciaSoloLectura)
                                        >
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if (! $transferenciaSoloLectura && $transferenciaResponsableBusqueda !== '')
                                        <div class="emergency-combobox-list emergency-free-professional-list">
                                            @forelse ($profesionalesTransferencia as $profesional)
                                                <button type="button" wire:click="seleccionarResponsableTransferencia({{ $profesional->id }})">
                                                    <strong>{{ $profesional->nombres }}</strong>
                                                    <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron profesionales. Se guardará como texto libre.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('transferenciaForm.responsable') <small>{{ $message }}</small> @enderror
                                </label>
                                <label class="is-full">
                                    <span>Motivo</span>
                                    <textarea rows="3" wire:model.blur="transferenciaForm.motivo" placeholder="Describa el motivo del movimiento interno" @readonly($transferenciaSoloLectura)></textarea>
                                    @error('transferenciaForm.motivo') <small>{{ $message }}</small> @enderror
                                </label>
                                <div class="emergency-transfer-form-actions">
                                    <button type="button" class="emergency-close-action" wire:click="$set('formularioTransferenciaAbierto', false)">
                                        {{ $transferenciaSoloLectura ? 'Cerrar' : 'Cancelar' }}
                                    </button>
                                    @if (! $transferenciaSoloLectura)
                                        <button type="button" class="catalog-primary-action" wire:click="guardarTransferencia">
                                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                            Guardar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card emergency-references-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:plane-takeoff" aria-hidden="true"></iconify-icon>
                                Referencias a otros Establecimientos
                            </h2>
                        </header>

                        <div class="emergency-reference-form">
                            <label>
                                <span>IPRESS Origen</span>
                                <input type="text" wire:model.blur="referenciaForm.ipress_origen" placeholder="Hospital Regional HCEApp" @readonly($soloLectura)>
                                @error('referenciaForm.ipress_origen') <small>{{ $message }}</small> @enderror
                            </label>
                            <label class="emergency-combobox emergency-reference-ups-field">
                                <span>UPS Origen <em>Unidad Productora de Servicios</em></span>
                                <div class="emergency-combobox-field">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.250ms="upsOrigenBusqueda"
                                        placeholder="Buscar UPS origen"
                                        @readonly($soloLectura)
                                    >
                                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                </div>
                                @if (! $soloLectura && $upsOrigenBusqueda !== '')
                                    <div class="emergency-combobox-list emergency-ups-list">
                                        @forelse ($upsOrigenCatalogo as $ups)
                                            <button type="button" wire:click="seleccionarUpsOrigen({{ $ups->id }})">
                                                <strong>{{ $ups->denominacion }}</strong>
                                                <span>{{ $ups->codigo ?: 'Sin codigo' }}</span>
                                            </button>
                                        @empty
                                            <p>No se encontraron UPS con ese criterio.</p>
                                        @endforelse
                                    </div>
                                @endif
                                @error('referenciaForm.ups_origen_id') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>IPRESS Destino <em>Búsqueda</em></span>
                                <input type="search" wire:model.blur="referenciaForm.ipress_referencia" placeholder="Buscar establecimiento de salud..." @readonly($soloLectura)>
                                @error('referenciaForm.ipress_referencia') <small>{{ $message }}</small> @enderror
                            </label>
                            <label class="emergency-combobox emergency-reference-ups-field">
                                <span>UPS Destino</span>
                                <div class="emergency-combobox-field">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.250ms="upsDestinoBusqueda"
                                        placeholder="Buscar UPS destino"
                                        @readonly($soloLectura)
                                    >
                                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                </div>
                                @if (! $soloLectura && $upsDestinoBusqueda !== '')
                                    <div class="emergency-combobox-list emergency-ups-list">
                                        @forelse ($upsDestinoCatalogo as $ups)
                                            <button type="button" wire:click="seleccionarUpsDestino({{ $ups->id }})">
                                                <strong>{{ $ups->denominacion }}</strong>
                                                <span>{{ $ups->codigo ?: 'Sin codigo' }}</span>
                                            </button>
                                        @empty
                                            <p>No se encontraron UPS con ese criterio.</p>
                                        @endforelse
                                    </div>
                                @endif
                                @error('referenciaForm.ups_destino_id') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Fecha/Hora Solicitud</span>
                                <input type="datetime-local" wire:model.blur="referenciaForm.fecha_hora_solicitud" @readonly($soloLectura)>
                                @error('referenciaForm.fecha_hora_solicitud') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Estado de Respuesta</span>
                                <select wire:model="referenciaForm.respuesta_solicitud" @disabled($soloLectura)>
                                    <option value="">Seleccione</option>
                                    @foreach ($estadosRespuestaReferencia as $estadoRespuesta)
                                        <option value="{{ $estadoRespuesta }}">{{ $estadoRespuesta }}</option>
                                    @endforeach
                                </select>
                                @error('referenciaForm.respuesta_solicitud') <small>{{ $message }}</small> @enderror
                            </label>
                            <label>
                                <span>Fecha/Hora Respuesta</span>
                                <input type="datetime-local" wire:model.blur="referenciaForm.fecha_hora_respuesta" @readonly($soloLectura)>
                                @error('referenciaForm.fecha_hora_respuesta') <small>{{ $message }}</small> @enderror
                            </label>
                            <label class="is-full">
                                <span>Motivo de Referencia</span>
                                <textarea rows="3" wire:model.blur="referenciaForm.motivo_referencia" placeholder="Describa el motivo clínico de la referencia" @readonly($soloLectura)></textarea>
                                @error('referenciaForm.motivo_referencia') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>
                    </article>
                </section>
            @elseif ($tabActiva === 'egresos')
                <section class="emergency-diagnosis-stack emergency-egress-stack">
                    <article class="emergency-attention-card emergency-egress-destination-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:person-standing" aria-hidden="true"></iconify-icon>
                                Destino del Paciente y condición
                            </h2>
                        </header>

                        <div class="emergency-egress-destination-form">
                            <label>
                                <span>Dentro IPRESS</span>
                                <select wire:model="form.destino_dentro_ipress" @disabled($soloLectura)>
                                    <option value="">Seleccione</option>
                                    @foreach ($destinosDentroIpress as $destinoDentroIpress)
                                        <option value="{{ $destinoDentroIpress->value }}">{{ $destinoDentroIpress->value }}</option>
                                    @endforeach
                                </select>
                                @error('form.destino_dentro_ipress') <small>{{ $message }}</small> @enderror
                            </label>

                            <label>
                                <span>Fuera IPRESS</span>
                                <select wire:model="form.destino_fuera_ipress" @disabled($soloLectura)>
                                    <option value="">Seleccione</option>
                                    @foreach ($destinosFueraIpress as $destinoFueraIpress)
                                        <option value="{{ $destinoFueraIpress->value }}">{{ $destinoFueraIpress->value }}</option>
                                    @endforeach
                                </select>
                                @error('form.destino_fuera_ipress') <small>{{ $message }}</small> @enderror
                            </label>

                            <label>
                                <span>Condición</span>
                                <select wire:model="form.condicion_egreso" @disabled($soloLectura)>
                                    <option value="">Seleccione</option>
                                    @foreach ($condicionesEgreso as $condicionEgreso)
                                        <option value="{{ $condicionEgreso->value }}">{{ $condicionEgreso->value }}</option>
                                    @endforeach
                                </select>
                                @error('form.condicion_egreso') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:pill" aria-hidden="true"></iconify-icon>
                                Medicamentos del Alta
                            </h2>

                            @if (! $soloLectura)
                                <button type="button" class="emergency-inline-action" wire:click="abrirNuevoAltaMedicamento">
                                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                                    Nuevo
                                </button>
                            @endif
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-discharge-medications-table">
                                <thead>
                                    <tr>
                                        <th>Medicamento</th>
                                        <th>Presentación</th>
                                        <th>Dosis</th>
                                        <th>Vía</th>
                                        <th>Cantidad</th>
                                        <th>Indicaciones</th>
                                        <th>Origen</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($altaMedicamentos as $altaMedicamento)
                                        <tr wire:key="alta-medicamento-{{ $altaMedicamento['id'] }}">
                                            <td>{{ $altaMedicamento['medicamento'] }}</td>
                                            <td>{{ $altaMedicamento['presentacion'] }}</td>
                                            <td>{{ $altaMedicamento['dosis'] }}</td>
                                            <td>{{ $altaMedicamento['via'] }}</td>
                                            <td>{{ $altaMedicamento['cantidad'] }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($altaMedicamento['indicaciones'], 72) }}</td>
                                            <td><span class="emergency-table-badge">{{ $altaMedicamento['origen'] }}</span></td>
                                            <td>
                                                @if (! $soloLectura)
                                                    <div class="emergency-row-actions">
                                                        <button type="button" wire:click="editarAltaMedicamento({{ $altaMedicamento['id'] }})" title="Editar medicamento del alta">
                                                            <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $altaMedicamento['id'] }}" data-registro-nombre="{{ $altaMedicamento['medicamento'] }}" data-catalogo-singular="medicamento del alta" data-delete-method="eliminarAltaMedicamento" data-confirm-title="Eliminar medicamento del alta" data-confirm-message="Deseas eliminar el medicamento del alta {{ $altaMedicamento['medicamento'] }}?" title="Eliminar medicamento del alta">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:pill" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado medicamentos del alta.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($formularioAltaMedicamentoAbierto)
                            <div class="emergency-vital-form emergency-discharge-medication-form">
                                <label class="emergency-combobox emergency-discharge-medication-name-field">
                                    <span>Medicamento</span>
                                    <div class="emergency-combobox-field">
                                        <input type="search" wire:model.live.debounce.250ms="altaMedicamentoBusqueda" placeholder="Buscar o escribir medicamento">
                                        <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    </div>
                                    @if ($altaMedicamentoBusqueda !== '' && blank($altaMedicamentoForm['medicamento_id']))
                                        <div class="emergency-combobox-list emergency-medication-list">
                                            @forelse ($medicamentosAlta as $medicamento)
                                                <button type="button" wire:click="seleccionarMedicamentoAlta({{ $medicamento->id }})">
                                                    <strong>{{ $medicamento->descripcion }}</strong>
                                                    <span>{{ collect([$medicamento->presentacion, $medicamento->generico, 'Stock: '.$medicamento->stock])->filter()->implode(' · ') }}</span>
                                                </button>
                                            @empty
                                                <p>No se encontraron medicamentos. Se guardará como texto libre.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                    @error('altaMedicamentoForm.medicamento') <small>{{ $message }}</small> @enderror
                                    @error('altaMedicamentoForm.medicamento_id') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Presentación</span>
                                    <input type="text" wire:model.blur="altaMedicamentoForm.presentacion" placeholder="Ej. Tableta 500mg">
                                    @error('altaMedicamentoForm.presentacion') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Dosis</span>
                                    <input type="text" wire:model.blur="altaMedicamentoForm.dosis" placeholder="Ej. 1 tableta">
                                    @error('altaMedicamentoForm.dosis') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Vía</span>
                                    <input type="text" wire:model.blur="altaMedicamentoForm.via" placeholder="Ej. Oral">
                                    @error('altaMedicamentoForm.via') <small>{{ $message }}</small> @enderror
                                </label>

                                <label>
                                    <span>Cantidad</span>
                                    <input type="number" min="1" max="255" wire:model.blur="altaMedicamentoForm.cantidad" placeholder="Ej. 10">
                                    @error('altaMedicamentoForm.cantidad') <small>{{ $message }}</small> @enderror
                                </label>

                                <label class="is-full">
                                    <span>Indicaciones</span>
                                    <textarea rows="2" wire:model.blur="altaMedicamentoForm.indicaciones" placeholder="Ingrese indicaciones para el alta"></textarea>
                                    @error('altaMedicamentoForm.indicaciones') <small>{{ $message }}</small> @enderror
                                </label>

                                <div class="emergency-discharge-medication-form-actions">
                                    <button type="button" class="emergency-close-action" wire:click="$set('formularioAltaMedicamentoAbierto', false)">Cancelar</button>
                                    <button type="button" class="catalog-primary-action" wire:click="guardarAltaMedicamento">
                                        <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                                        Guardar
                                    </button>
                                </div>
                            </div>
                        @endif
                    </article>

                    <article class="emergency-attention-card emergency-egress-data-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:calendar-check" aria-hidden="true"></iconify-icon>
                                Datos del Egreso
                            </h2>
                        </header>

                        <div class="emergency-egress-data-form">
                            <label>
                                <span>Fecha/Hora de Egreso</span>
                                <input type="datetime-local" wire:model.blur="form.fecha_hora_egreso" @readonly($soloLectura)>
                                @error('form.fecha_hora_egreso') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="emergency-combobox emergency-egress-professional-field">
                                <span>Profesional Responsable</span>
                                <div class="emergency-combobox-field">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.250ms="profesionalEgresoBusqueda"
                                        placeholder="Buscar profesional por nombre, especialidad o CMP"
                                        @readonly($soloLectura)
                                    >
                                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                </div>
                                @if (! $soloLectura && $profesionalEgresoBusqueda !== '' && blank($form['profesional_egreso_id']))
                                    <div class="emergency-combobox-list emergency-egress-professional-list">
                                        @forelse ($profesionalesEgreso as $profesional)
                                            <button type="button" wire:click="seleccionarProfesionalEgreso({{ $profesional->id }})">
                                                <strong>{{ $profesional->nombres }}</strong>
                                                <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                            </button>
                                        @empty
                                            <p>No se encontraron profesionales.</p>
                                        @endforelse
                                    </div>
                                @endif
                                @error('form.profesional_egreso_id') <small>{{ $message }}</small> @enderror
                            </label>

                            <label class="is-full">
                                <span>Indicaciones finales</span>
                                <textarea rows="4" wire:model.blur="form.indicaciones_finales" placeholder="Ingrese recomendaciones dietéticas, cuidados en el hogar o signos de alarma..." @readonly($soloLectura)></textarea>
                                @error('form.indicaciones_finales') <small>{{ $message }}</small> @enderror
                            </label>
                        </div>

                        <div class="emergency-section-actions" style="margin-top: 14px;">
                            @if ($atencion)
                                <a
                                    href="{{ route('historia-clinica.emergencia.alta.pdf', $atencion) }}"
                                    class="emergency-inline-action"
                                    target="_blank"
                                    rel="noopener"
                                    title="Genera Ficha de Alta"
                                >
                                    <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                    Genera Ficha de Alta
                                </a>
                            @else
                                <button type="button" class="emergency-inline-action" title="Guarde la atención para generar ficha de alta" disabled>
                                    <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                                    Genera Ficha de Alta
                                </button>
                            @endif
                        </div>
                    </article>
                </section>
            @elseif ($tabActiva === 'documentacion')
                <section class="emergency-documents-stack">
                    <div class="emergency-documents-title">
                        <h2>
                            <iconify-icon icon="lucide:folder-open" aria-hidden="true"></iconify-icon>
                            Documentación Adjunta
                        </h2>
                    </div>

                    <article class="emergency-attention-card emergency-documents-upload-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:file-up" aria-hidden="true"></iconify-icon>
                                Cargar Nuevo Documento
                            </h2>
                        </header>

                        <div class="emergency-document-dropzone">
                            <input
                                id="documentosAdjuntosArchivos"
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                wire:model="documentosAdjuntosArchivos"
                                @disabled($soloLectura)
                            >

                            <iconify-icon icon="lucide:cloud-upload" aria-hidden="true"></iconify-icon>
                            <p>Arrastre y suelte los archivos aquí</p>
                            <span>o</span>
                            <label for="documentosAdjuntosArchivos" class="emergency-document-select-action">
                                Seleccionar Archivo
                            </label>
                            <small>Formatos soportados: PDF, JPG, PNG (Max. 10MB)</small>
                        </div>

                        @if (count($documentosAdjuntosArchivos) > 0)
                            <ul class="emergency-document-selected-list">
                                @foreach ($documentosAdjuntosArchivos as $archivo)
                                    <li wire:key="documento-temporal-{{ $loop->index }}">
                                        <iconify-icon icon="lucide:paperclip" aria-hidden="true"></iconify-icon>
                                        {{ $archivo->getClientOriginalName() }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @error('documentosAdjuntosArchivos') <small class="emergency-document-error">{{ $message }}</small> @enderror
                        @error('documentosAdjuntosArchivos.*') <small class="emergency-document-error">{{ $message }}</small> @enderror

                        <button
                            type="button"
                            class="emergency-document-upload-action"
                            wire:click="guardarDocumentosAdjuntos"
                            wire:loading.attr="disabled"
                            wire:target="documentosAdjuntosArchivos,guardarDocumentosAdjuntos"
                            @disabled($soloLectura || count($documentosAdjuntosArchivos) === 0)
                        >
                            <iconify-icon icon="lucide:upload" aria-hidden="true"></iconify-icon>
                            <span wire:loading.remove wire:target="guardarDocumentosAdjuntos">Cargar Archivos</span>
                            <span wire:loading wire:target="guardarDocumentosAdjuntos">Cargando...</span>
                        </button>
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card emergency-documents-list-card">
                        <header class="emergency-section-toolbar emergency-documents-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:folder" aria-hidden="true"></iconify-icon>
                                Documentos del Paciente
                            </h2>

                            <div class="emergency-documents-search">
                                <label>
                                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    <input type="search" wire:model.live.debounce.300ms="busquedaDocumentoAdjunto" placeholder="Buscar documento...">
                                </label>
                                <button type="button" title="Filtro de documentos" aria-label="Filtro de documentos">
                                    <iconify-icon icon="lucide:list-filter" aria-hidden="true"></iconify-icon>
                                </button>
                            </div>
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-documents-table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Nombre del Archivo</th>
                                        <th>Fecha de Carga</th>
                                        <th>Cargado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($documentosAdjuntos as $documentoAdjunto)
                                        <tr wire:key="documento-adjunto-{{ $documentoAdjunto['id'] }}">
                                            <td>
                                                <span class="emergency-document-type {{ $documentoAdjunto['clase_icono'] }}">
                                                    <iconify-icon icon="{{ $documentoAdjunto['icono'] }}" aria-hidden="true"></iconify-icon>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="emergency-document-name-cell">
                                                    <strong>{{ $documentoAdjunto['nombre'] }}</strong>
                                                    <em>{{ $documentoAdjunto['tamano'] }}</em>
                                                </span>
                                            </td>
                                            <td>{{ $documentoAdjunto['fecha_carga'] }}</td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $documentoAdjunto['iniciales'] }}</strong>
                                                    {{ $documentoAdjunto['cargado_por'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="emergency-row-actions">
                                                    <a href="{{ $documentoAdjunto['url'] }}" target="_blank" rel="noopener" title="Ver documento">
                                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                                    </a>
                                                    <a href="{{ $documentoAdjunto['url'] }}" download title="Descargar documento">
                                                        <iconify-icon icon="lucide:download" aria-hidden="true"></iconify-icon>
                                                    </a>
                                                    @if (! $soloLectura)
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $documentoAdjunto['id'] }}" data-registro-nombre="{{ $documentoAdjunto['nombre'] }}" data-catalogo-singular="documento adjunto" data-delete-method="eliminarDocumentoAdjunto" data-confirm-title="Eliminar documento adjunto" data-confirm-message="Deseas eliminar el documento adjunto {{ $documentoAdjunto['nombre'] }}?" title="Eliminar documento adjunto">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:file-search" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado documentos adjuntos.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <footer class="emergency-documents-footer">
                            Mostrando {{ $documentosAdjuntos->count() }} de {{ $documentosAdjuntos->count() }} documentos
                        </footer>
                    </article>
                </section>
            @else
                @php($tabActual = collect($tabs)->firstWhere('clave', $tabActiva))
                <section class="emergency-placeholder">
                    <iconify-icon icon="{{ $tabActual['icono'] }}" aria-hidden="true"></iconify-icon>
                    <h2>{{ $tabActual['titulo'] }}</h2>
                    <p>Este panel queda preparado para cargar la informacion clinica correspondiente en el siguiente paso de desarrollo.</p>
                </section>
            @endif
        </main>
    </div>

    @if ($modalDocumentosOrdenAbierto)
        <div class="emergency-feedback-backdrop emergency-order-documents-backdrop" role="dialog" aria-modal="true" aria-labelledby="emergency-order-documents-title">
            <section class="emergency-order-documents-modal">
                <header class="emergency-order-documents-header">
                    <div>
                        <p>Orden clínica</p>
                        <h2 id="emergency-order-documents-title">Documentos de la orden</h2>
                        @if ($ordenDocumentosResumen)
                            <span>{{ $ordenDocumentosResumen['tipo'] }} · {{ $ordenDocumentosResumen['detalle'] }} · {{ $ordenDocumentosResumen['fecha_hora'] }}</span>
                        @endif
                    </div>
                    <button type="button" class="emergency-close-icon-action" wire:click="cerrarModalDocumentosOrden" title="Cerrar">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </header>

                <div class="emergency-order-documents-grid">
                    <article class="emergency-attention-card emergency-documents-upload-card">
                        <header class="emergency-section-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:file-up" aria-hidden="true"></iconify-icon>
                                Cargar Nuevo Documento
                            </h2>
                        </header>

                        <div class="emergency-document-dropzone">
                            <input
                                id="ordenDocumentosArchivos"
                                type="file"
                                multiple
                                accept=".pdf,.xls,.xlsx,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/webp"
                                wire:model="ordenDocumentosArchivos"
                                @disabled($soloLectura)
                            >

                            <iconify-icon icon="lucide:cloud-upload" aria-hidden="true"></iconify-icon>
                            <p>Arrastre y suelte los archivos aquí</p>
                            <span>o</span>
                            <label for="ordenDocumentosArchivos" class="emergency-document-select-action">
                                Seleccionar Archivo
                            </label>
                            <small>Formatos soportados: PDF, Excel, Word, JPG, PNG, WEBP (Max. 10MB)</small>
                        </div>

                        @if (count($ordenDocumentosArchivos) > 0)
                            <ul class="emergency-document-selected-list">
                                @foreach ($ordenDocumentosArchivos as $archivo)
                                    <li wire:key="orden-documento-temporal-{{ $loop->index }}">
                                        <iconify-icon icon="lucide:paperclip" aria-hidden="true"></iconify-icon>
                                        {{ $archivo->getClientOriginalName() }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @error('ordenDocumentosArchivos') <small class="emergency-document-error">{{ $message }}</small> @enderror
                        @error('ordenDocumentosArchivos.*') <small class="emergency-document-error">{{ $message }}</small> @enderror

                        <button
                            type="button"
                            class="emergency-document-upload-action"
                            wire:click="guardarDocumentosOrden"
                            wire:loading.attr="disabled"
                            wire:target="ordenDocumentosArchivos,guardarDocumentosOrden"
                            @disabled($soloLectura || count($ordenDocumentosArchivos) === 0)
                        >
                            <iconify-icon icon="lucide:upload" aria-hidden="true"></iconify-icon>
                            <span wire:loading.remove wire:target="guardarDocumentosOrden">Cargar Archivos</span>
                            <span wire:loading wire:target="guardarDocumentosOrden">Cargando...</span>
                        </button>
                    </article>

                    <article class="emergency-attention-card emergency-clinical-table-card emergency-documents-list-card">
                        <header class="emergency-section-toolbar emergency-documents-toolbar">
                            <h2>
                                <iconify-icon icon="lucide:folder" aria-hidden="true"></iconify-icon>
                                Documentos de la Orden
                            </h2>

                            <div class="emergency-documents-search">
                                <label>
                                    <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                                    <input type="search" wire:model.live.debounce.300ms="busquedaDocumentoOrden" placeholder="Buscar documento...">
                                </label>
                                <button type="button" title="Filtro de documentos" aria-label="Filtro de documentos">
                                    <iconify-icon icon="lucide:list-filter" aria-hidden="true"></iconify-icon>
                                </button>
                            </div>
                        </header>

                        <div class="emergency-vitals-table-wrap">
                            <table class="emergency-vitals-table emergency-clinical-table emergency-documents-table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Nombre del Archivo</th>
                                        <th>Fecha de Carga</th>
                                        <th>Cargado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($documentosOrden as $documentoOrden)
                                        <tr wire:key="orden-documento-adjunto-{{ $documentoOrden['id'] }}">
                                            <td>
                                                <span class="emergency-document-type {{ $documentoOrden['clase_icono'] }}">
                                                    <iconify-icon icon="{{ $documentoOrden['icono'] }}" aria-hidden="true"></iconify-icon>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="emergency-document-name-cell">
                                                    <strong>{{ $documentoOrden['nombre'] }}</strong>
                                                    <em>{{ $documentoOrden['tamano'] }}</em>
                                                </span>
                                            </td>
                                            <td>{{ $documentoOrden['fecha_carga'] }}</td>
                                            <td>
                                                <span class="emergency-professional-inline">
                                                    <strong>{{ $documentoOrden['iniciales'] }}</strong>
                                                    {{ $documentoOrden['cargado_por'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="emergency-row-actions">
                                                    <a href="{{ $documentoOrden['url'] }}" target="_blank" rel="noopener" title="Ver documento">
                                                        <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                                                    </a>
                                                    <a href="{{ $documentoOrden['url'] }}" download title="Descargar documento">
                                                        <iconify-icon icon="lucide:download" aria-hidden="true"></iconify-icon>
                                                    </a>
                                                    @if (! $soloLectura)
                                                        <button type="button" data-confirm-delete-emergency-tab data-registro-id="{{ $documentoOrden['id'] }}" data-registro-nombre="{{ $documentoOrden['nombre'] }}" data-catalogo-singular="documento de orden" data-delete-method="eliminarDocumentoOrden" data-confirm-title="Eliminar documento de orden" data-confirm-message="Deseas eliminar el documento {{ $documentoOrden['nombre'] }}?" title="Eliminar documento">
                                                            <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="emergency-empty-cell">
                                                <div class="emergency-table-empty-state">
                                                    <iconify-icon icon="lucide:file-search" aria-hidden="true"></iconify-icon>
                                                    <p>No se han registrado documentos para esta orden.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <footer class="emergency-documents-footer">
                            Mostrando {{ $documentosOrden->count() }} de {{ $documentosOrden->count() }} documentos
                        </footer>
                    </article>
                </div>
            </section>
        </div>
    @endif

    @if ($antecedenteDrawer['abierto'] ?? false)
        <div class="patient-drawer-backdrop"></div>

        <aside class="patient-drawer patient-clinical-drawer {{ $antecedenteDrawer['ancho'] }} is-{{ $antecedenteDrawer['clave'] }}" style="--drawer-accent: {{ $antecedenteDrawer['acento'] }}" aria-modal="true" role="dialog">
            <header>
                <div>
                    <p>Antecedentes medicos</p>
                    <h2>{{ $antecedenteDrawer['titulo'] }}</h2>
                </div>
                <button type="button" wire:click="cerrarDrawerAntecedente" aria-label="Cerrar drawer">
                    <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                </button>
            </header>

            <section class="patient-drawer-records">
                <div class="patient-drawer-toolbar">
                    <strong>
                        <span class="patient-drawer-section-icon">
                            <iconify-icon icon="{{ $antecedenteDrawer['icono'] }}" aria-hidden="true"></iconify-icon>
                        </span>
                        {{ $antecedenteDrawer['titulo'] }}
                    </strong>
                    <button type="button" wire:click="nuevoAntecedenteRegistro">
                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                        Nuevo
                    </button>
                </div>

                @if ($antecedenteDrawer['registros']->isNotEmpty())
                    <table class="patient-drawer-table">
                        <thead>
                            <tr>
                                @switch($antecedenteDrawer['clave'])
                                    @case('alergias')
                                        <th>Alergia</th>
                                        <th>Agente</th>
                                        <th>Alergeno</th>
                                        <th>Periodo</th>
                                        @break
                                    @case('reacciones')
                                        <th>Medicamento</th>
                                        <th>Tipo</th>
                                        <th>Gravedad</th>
                                        <th>Periodo</th>
                                        @break
                                    @case('familiares')
                                        <th>Parentesco</th>
                                        <th>Condicion</th>
                                        <th>CIE10</th>
                                        <th>Periodo</th>
                                        @break
                                    @case('cirugias')
                                        <th>Codigo CPMS</th>
                                        <th>Denominacion</th>
                                        <th>Periodo</th>
                                        @break
                                    @case('hospitalizaciones')
                                        <th>Codigo UPS</th>
                                        <th>Diagnostico / denominacion</th>
                                        <th>Periodo</th>
                                        @break
                                    @case('medicamentos')
                                        <th>Medicamento habitual</th>
                                        <th>Periodo</th>
                                        @break
                                    @default
                                        <th>CIE10 / denominacion</th>
                                        <th>Origen</th>
                                        <th>Periodo</th>
                                @endswitch
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($antecedenteDrawer['registros'] as $registro)
                                <tr>
                                    @switch($antecedenteDrawer['clave'])
                                        @case('alergias')
                                            <td>{{ $registro->alergia?->nombre ?? 'Alergia registrada' }}</td>
                                            <td>{{ $registro->agente_causal ?: '--' }}</td>
                                            <td>{{ $registro->alergeno ?: '--' }}</td>
                                            <td>{{ $registro->periodo ?: '--' }}</td>
                                            @break
                                        @case('reacciones')
                                            <td>{{ $registro->dci_medicamento ?: '--' }}</td>
                                            <td>{{ $registro->tipo?->value ?? '--' }}</td>
                                            <td>{{ $registro->gravedad?->value ?? '--' }}</td>
                                            <td>{{ $registro->periodo ?: '--' }}</td>
                                            @break
                                        @case('familiares')
                                            <td>{{ $registro->parentesco?->value ?? '--' }}</td>
                                            <td>{{ $registro->denominacion ?: ($registro->diagnosticoCie10?->nombre ?? '--') }}</td>
                                            <td>{{ $registro->diagnosticoCie10?->codigo ?? '--' }}</td>
                                            <td>{{ $registro->periodo ?: '--' }}</td>
                                            @break
                                        @case('cirugias')
                                            <td>{{ $registro->codigo_cpms ?: '--' }}</td>
                                            <td>{{ $registro->denominacion ?: '--' }}</td>
                                            <td>{{ $registro->periodo ?: '--' }}</td>
                                            @break
                                        @case('hospitalizaciones')
                                            <td>{{ $registro->codigo_ups ?: '--' }}</td>
                                            <td>{{ $registro->denominacion ?: ($registro->diagnosticoCie10?->nombre ?? '--') }}</td>
                                            <td>{{ $registro->periodo ?: '--' }}</td>
                                            @break
                                        @case('medicamentos')
                                            <td>{{ $registro->medicacion ?: '--' }}</td>
                                            <td>{{ $registro->periodo ?: '--' }}</td>
                                            @break
                                        @default
                                            <td>{{ $registro->diagnosticoCie10?->codigo ? $registro->diagnosticoCie10->codigo.' - '.($registro->denominacion ?: $registro->diagnosticoCie10->nombre) : ($registro->denominacion ?: '--') }}</td>
                                            <td>{{ $registro->origen ?: '--' }}</td>
                                            <td>{{ $registro->periodo ?: '--' }}</td>
                                    @endswitch
                                    <td>
                                        <nav>
                                            <button type="button" wire:click="editarAntecedenteRegistro({{ $registro->id }})" aria-label="Editar registro">
                                                <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                            </button>
                                            <button
                                                type="button"
                                                data-confirm-delete-emergency-tab
                                                data-registro-id="{{ $registro->id }}"
                                                data-registro-nombre="{{ $registro->periodo ?: 'registro clinico' }}"
                                                data-catalogo-singular="registro clinico"
                                                data-delete-method="eliminarAntecedenteRegistro"
                                                aria-label="Eliminar registro"
                                            >
                                                <iconify-icon icon="lucide:trash-2" aria-hidden="true"></iconify-icon>
                                            </button>
                                        </nav>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="patient-drawer-empty">Aun no hay registros para esta seccion.</p>
                @endif
            </section>

            <form class="patient-drawer-form" wire:submit.prevent="guardarAntecedenteRegistro">
                <h3>{{ $antecedenteRegistroEditandoId ? 'Editar registro' : 'Nuevo registro' }}</h3>

                <div class="patient-drawer-form-grid {{ $antecedenteDrawer['ancho'] }}">
                @if ($antecedenteDrawer['clave'] === 'alergias')
                    <label>
                        Alergia
                        <select wire:model="antecedenteFormulario.alergia_id">
                            <option value="">Seleccione</option>
                            @foreach ($antecedenteDrawer['opciones']['alergias'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('antecedenteFormulario.alergia_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Agente causal <input type="text" wire:model.blur="antecedenteFormulario.agente_causal">@error('antecedenteFormulario.agente_causal') <span>{{ $message }}</span> @enderror</label>
                    <label>Alergeno <input type="text" wire:model.blur="antecedenteFormulario.alergeno">@error('antecedenteFormulario.alergeno') <span>{{ $message }}</span> @enderror</label>
                    <label>Periodo <input type="text" wire:model.blur="antecedenteFormulario.periodo">@error('antecedenteFormulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($antecedenteDrawer['clave'] === 'reacciones')
                    <label>Medicamento DCI <input type="text" wire:model.blur="antecedenteFormulario.dci_medicamento">@error('antecedenteFormulario.dci_medicamento') <span>{{ $message }}</span> @enderror</label>
                    <label>Descripcion <textarea rows="3" wire:model.blur="antecedenteFormulario.descripcion"></textarea>@error('antecedenteFormulario.descripcion') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Tipo
                        <select wire:model="antecedenteFormulario.tipo">
                            <option value="">Seleccione</option>
                            @foreach ($antecedenteDrawer['opciones']['tipos_ram'] as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('antecedenteFormulario.tipo') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>
                        Gravedad
                        <select wire:model="antecedenteFormulario.gravedad">
                            <option value="">Seleccione</option>
                            @foreach ($antecedenteDrawer['opciones']['gravedades_ram'] as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('antecedenteFormulario.gravedad') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="antecedenteFormulario.periodo">@error('antecedenteFormulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($antecedenteDrawer['clave'] === 'familiares')
                    <label>
                        Parentesco
                        <select wire:model="antecedenteFormulario.parentesco">
                            <option value="">Seleccione</option>
                            @foreach ($antecedenteDrawer['opciones']['parentescos'] as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('antecedenteFormulario.parentesco') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Denominacion <input type="text" wire:model.blur="antecedenteFormulario.denominacion">@error('antecedenteFormulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Diagnostico CIE10
                        <select wire:model="antecedenteFormulario.diagnostico_cie10_id">
                            <option value="">Seleccione</option>
                            @foreach ($antecedenteDrawer['opciones']['diagnosticos'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('antecedenteFormulario.diagnostico_cie10_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="antecedenteFormulario.periodo">@error('antecedenteFormulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($antecedenteDrawer['clave'] === 'cirugias')
                    <label>Codigo CPMS <input type="text" wire:model.blur="antecedenteFormulario.codigo_cpms">@error('antecedenteFormulario.codigo_cpms') <span>{{ $message }}</span> @enderror</label>
                    <label>Denominacion <input type="text" wire:model.blur="antecedenteFormulario.denominacion">@error('antecedenteFormulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>Periodo <input type="text" wire:model.blur="antecedenteFormulario.periodo">@error('antecedenteFormulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($antecedenteDrawer['clave'] === 'hospitalizaciones')
                    <label>Codigo UPS <input type="text" wire:model.blur="antecedenteFormulario.codigo_ups">@error('antecedenteFormulario.codigo_ups') <span>{{ $message }}</span> @enderror</label>
                    <label>Denominacion <input type="text" wire:model.blur="antecedenteFormulario.denominacion">@error('antecedenteFormulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Diagnostico CIE10
                        <select wire:model="antecedenteFormulario.diagnostico_cie10_id">
                            <option value="">Seleccione</option>
                            @foreach ($antecedenteDrawer['opciones']['diagnosticos'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('antecedenteFormulario.diagnostico_cie10_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="antecedenteFormulario.periodo">@error('antecedenteFormulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($antecedenteDrawer['clave'] === 'medicamentos')
                    <label>Medicamento habitual <input type="text" wire:model.blur="antecedenteFormulario.medicacion">@error('antecedenteFormulario.medicacion') <span>{{ $message }}</span> @enderror</label>
                    <label>Periodo <input type="text" wire:model.blur="antecedenteFormulario.periodo">@error('antecedenteFormulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($antecedenteDrawer['clave'] === 'patologicos')
                    <label>Denominacion <input type="text" wire:model.blur="antecedenteFormulario.denominacion">@error('antecedenteFormulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>Origen <input type="text" wire:model.blur="antecedenteFormulario.origen">@error('antecedenteFormulario.origen') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Diagnostico CIE10
                        <select wire:model="antecedenteFormulario.diagnostico_cie10_id">
                            <option value="">Seleccione</option>
                            @foreach ($antecedenteDrawer['opciones']['diagnosticos'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('antecedenteFormulario.diagnostico_cie10_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="antecedenteFormulario.periodo">@error('antecedenteFormulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @endif
                </div>

                <footer>
                    <button type="button" class="patient-drawer-secondary" wire:click="cerrarDrawerAntecedente">Cerrar</button>
                    <button type="submit" class="catalog-primary-action">
                        <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                        Guardar
                    </button>
                </footer>
            </form>
        </aside>
    @endif

    @if ($modalProfesionalTratanteAbierto)
        <div class="emergency-feedback-backdrop" role="dialog" aria-modal="true" aria-labelledby="emergency-treating-professional-title">
            <section class="emergency-feedback-modal emergency-treating-professional-modal">
                <div class="emergency-feedback-icon">
                    <iconify-icon icon="lucide:stethoscope" aria-hidden="true"></iconify-icon>
                </div>

                <div class="emergency-feedback-content">
                    <p>Profesional tratante</p>
                    <h2 id="emergency-treating-professional-title">Cambiar Profesional Tratante</h2>
                    <span>Selecciona el profesional que quedará asignado como responsable de esta atención.</span>

                    <label class="emergency-combobox emergency-treating-professional-field">
                        <span>Profesional</span>
                        <div class="emergency-combobox-field">
                            <input
                                type="search"
                                wire:model.live.debounce.250ms="profesionalTratanteBusqueda"
                                placeholder="Buscar profesional por nombre, especialidad o CMP"
                                autocomplete="off"
                            >
                            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                        </div>
                        @if ($profesionalTratanteBusqueda !== '' && blank($profesionalTratanteId))
                            <div class="emergency-combobox-list emergency-treating-professional-list">
                                @forelse ($profesionalesTratantes as $profesional)
                                    <button type="button" wire:click="seleccionarProfesionalTratante({{ $profesional->id }})">
                                        <strong>{{ $profesional->nombres }}</strong>
                                        <span>{{ $profesional->especialidad?->nombre ?? 'Sin especialidad' }} · {{ $profesional->cmp ? 'CMP '.$profesional->cmp : 'Sin CMP' }}</span>
                                    </button>
                                @empty
                                    <p>No se encontraron profesionales.</p>
                                @endforelse
                            </div>
                        @endif
                        @error('profesionalTratanteId') <small>{{ $message }}</small> @enderror
                    </label>

                    <div class="emergency-feedback-actions">
                        <button type="button" class="emergency-close-action" wire:click="cerrarModalProfesionalTratante">
                            Cerrar
                        </button>
                        <button type="button" class="catalog-primary-action" wire:click="guardarProfesionalTratante">
                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                            Guardar
                        </button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($panelConfirmacionCierreAbierto)
        <div class="emergency-feedback-backdrop" role="dialog" aria-modal="true" aria-labelledby="emergency-close-confirmation-title">
            <section class="emergency-feedback-modal is-warning">
                <div class="emergency-feedback-icon">
                    <iconify-icon icon="lucide:circle-alert" aria-hidden="true"></iconify-icon>
                </div>

                <div class="emergency-feedback-content">
                    <p>Cierre de atención</p>
                    <h2 id="emergency-close-confirmation-title">¿Esta seguro de Cerrar la atención?</h2>
                    <span>Al confirmar, se guardarán los cambios pendientes y la historia clínica quedará disponible solo para lectura.</span>

                    <div class="emergency-feedback-actions">
                        <button type="button" class="emergency-close-action" wire:click="cancelarCierreAtencion">
                            No
                        </button>
                        <button type="button" class="catalog-primary-action" wire:click="confirmarCierreAtencion">
                            Si
                        </button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($panelAtencionCerradaAbierto)
        <div class="emergency-feedback-backdrop" role="dialog" aria-modal="true" aria-labelledby="emergency-closed-title">
            <section class="emergency-feedback-modal is-success">
                <div class="emergency-feedback-icon">
                    <iconify-icon icon="lucide:check" aria-hidden="true"></iconify-icon>
                </div>

                <div class="emergency-feedback-content">
                    <p>Historia clínica cerrada</p>
                    <h2 id="emergency-closed-title">Atención ha sido cerrada</h2>
                    <span>El registro fue guardado correctamente. Al aceptar volverás al listado de historias del paciente.</span>

                    <div class="emergency-feedback-actions">
                        <button type="button" class="catalog-primary-action" wire:click="aceptarAtencionCerrada">
                            Aceptar
                        </button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($panelErroresAbierto)
        <div class="emergency-feedback-backdrop" role="dialog" aria-modal="true" aria-labelledby="emergency-errors-title">
            <section class="emergency-feedback-modal is-error">
                <div class="emergency-feedback-icon">
                    <iconify-icon icon="lucide:triangle-alert" aria-hidden="true"></iconify-icon>
                </div>

                <div class="emergency-feedback-content">
                    <p>Validacion de datos</p>
                    <h2 id="emergency-errors-title">No se pudo guardar la historia clinica</h2>
                    <span>Revise los datos antes de continuar. Los campos no dependen entre tabs, pero el guardado valida la historia clinica completa.</span>

                    <ul>
                        @foreach ($erroresGuardado as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                    <div class="emergency-feedback-actions">
                        <button type="button" class="catalog-primary-action" wire:click="cerrarPanelErrores">
                            Revisar datos
                        </button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($panelConfirmacionGuardadoAbierto)
        <div class="emergency-feedback-backdrop" role="dialog" aria-modal="true" aria-labelledby="emergency-success-title">
            <section class="emergency-feedback-modal is-success">
                <div class="emergency-feedback-icon">
                    <iconify-icon icon="lucide:check" aria-hidden="true"></iconify-icon>
                </div>

                <div class="emergency-feedback-content">
                    <p>Historia clinica guardada</p>
                    <h2 id="emergency-success-title">¿Deseas seguir editando la Historia Clínica?</h2>
                    <span>El registro fue actualizado correctamente. Puedes continuar en este panel o regresar al historial clinico del paciente.</span>

                    <div class="emergency-feedback-actions">
                        <button type="button" class="emergency-close-action" wire:click="volverAlHistorialPaciente">
                            No, volver al historial
                        </button>
                        <button type="button" class="catalog-primary-action" wire:click="seguirEditandoHistoria">
                            Si, seguir editando
                        </button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($panelSalidaSinGuardarAbierto)
        <div class="emergency-feedback-backdrop" role="dialog" aria-modal="true" aria-labelledby="emergency-unsaved-exit-title">
            <section class="emergency-feedback-modal is-warning">
                <div class="emergency-feedback-icon">
                    <iconify-icon icon="lucide:circle-alert" aria-hidden="true"></iconify-icon>
                </div>

                <div class="emergency-feedback-content">
                    <p>Cambios sin guardar</p>
                    <h2 id="emergency-unsaved-exit-title">¿Desea guardar los cambios antes de salir?</h2>
                    <span>Hay cambios pendientes en la historia clínica. Puede guardarlos antes de volver al historial o salir sin conservarlos.</span>

                    <div class="emergency-feedback-actions">
                        <button type="button" class="emergency-close-action" wire:click="cancelarSalidaDelPanel">
                            Cancelar
                        </button>
                        <button type="button" class="emergency-close-action" wire:click="salirSinGuardarCambios">
                            Salir sin guardar
                        </button>
                        <button type="button" class="catalog-primary-action" wire:click="guardarYSalirDelPanel">
                            <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                            Guardar y salir
                        </button>
                    </div>
                </div>
            </section>
        </div>
    @endif
</section>
