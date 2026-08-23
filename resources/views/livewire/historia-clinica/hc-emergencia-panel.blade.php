<section class="emergency-record-page">
    <header class="emergency-record-header panel-animate">
        <div class="emergency-patient-block">
            <span class="emergency-patient-avatar">
                <iconify-icon icon="lucide:user-round" aria-hidden="true"></iconify-icon>
            </span>

            <div>
                <h1>{{ $datosPaciente['nombre'] }}</h1>
                <div class="emergency-patient-meta">
                    <span>{{ $datosPaciente['sexo'] }}</span>
                    <i></i>
                    <span>{{ $datosPaciente['edad'] }}</span>
                </div>
                <div class="emergency-patient-codes">
                    <span><strong>HC:</strong> {{ $datosPaciente['historia'] }}</span>
                    <span><strong>AT:</strong> {{ $form['numero_atencion'] ?: 'Nuevo' }}</span>
                    <span><iconify-icon icon="lucide:phone" aria-hidden="true"></iconify-icon>{{ $datosPaciente['telefono'] }}</span>
                </div>
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
                <article class="is-priority">
                    <span>Prioridad</span>
                    @if ($soloLectura)
                        <strong>{{ optional($prioridades->firstWhere('id', (int) $form['prioridad_id']))->prioridad ?? 'Sin prioridad' }}</strong>
                    @else
                        <select wire:model.live="form.prioridad_id" aria-label="Prioridad de atencion">
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
                            Cierre de Atencion
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
                    <span>
                        <iconify-icon icon="lucide:stethoscope" aria-hidden="true"></iconify-icon>
                    </span>
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
                                    <select wire:model="form.topico" @disabled($soloLectura)>
                                        <option value="">Seleccione</option>
                                        @foreach ($topicos as $topico)
                                            <option value="{{ $topico->value }}">{{ $topico->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.topico') <small>{{ $message }}</small> @enderror
                                </label>
                                <label>
                                    <span>Ambiente</span>
                                    <input type="text" wire:model.blur="form.ambiente" placeholder="Ej. Shock Trauma" @readonly($soloLectura)>
                                    @error('form.ambiente') <small>{{ $message }}</small> @enderror
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
</section>
