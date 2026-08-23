<section class="patient-dashboard-page">
    <article class="patient-profile-panel patient-dashboard-card hce-card-motion panel-animate" style="--card-accent: #0077b6">
        <div class="patient-profile-photo">
            <span>{{ mb_substr($pacienteActual->nombre, 0, 1) }}</span>
            <i><iconify-icon icon="lucide:check" aria-hidden="true"></iconify-icon></i>
        </div>

        <div class="patient-profile-main">
            <p>Dashboard del paciente</p>
            <h1>{{ $pacienteActual->nombre }}</h1>

            <div class="patient-profile-data">
                <span><iconify-icon icon="lucide:cake" aria-hidden="true"></iconify-icon> Edad: {{ $datosPaciente['edad'] }}</span>
                <span><iconify-icon icon="lucide:user-round" aria-hidden="true"></iconify-icon> {{ $datosPaciente['sexo'] }}</span>
                <span><iconify-icon icon="lucide:droplets" aria-hidden="true"></iconify-icon> Grupo: {{ $datosPaciente['grupo'] }}</span>
                <span><iconify-icon icon="lucide:scale" aria-hidden="true"></iconify-icon> {{ $datosPaciente['peso_talla'] }}</span>
            </div>
        </div>

        <button type="button" class="catalog-primary-action patient-clinic-action" wire:click="verHistoriaClinica">
            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
            Ver Historia Clinica
        </button>
    </article>

    <div class="patient-dashboard-grid">
        <div class="patient-dashboard-main">
            <div class="patient-alert-grid panel-animate" style="--panel-delay: 80ms">
                @foreach ($alertas as $alerta)
                    <article class="patient-clinical-panel patient-list-panel patient-dashboard-card hce-card-motion {{ $alerta['clase'] }}" style="--card-accent: {{ $alerta['clase'] === 'is-critical' ? '#ef4444' : '#415a77' }}">
                        <header class="patient-panel-title">
                            <span><iconify-icon icon="{{ $alerta['icono'] }}" aria-hidden="true"></iconify-icon></span>
                            <h2>{{ $alerta['titulo'] }}</h2>
                            <button type="button" wire:click="abrirDrawer('{{ $alerta['seccion'] }}')" aria-label="Editar {{ $alerta['titulo'] }}">
                                <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                            </button>
                        </header>

                        <ul class="patient-detail-list">
                            @forelse ($alerta['items'] as $item)
                                <li>
                                    <span></span>
                                    <div>
                                        <strong>{{ $item['principal'] }}</strong>
                                        <small>{{ $item['detalle'] }}</small>
                                    </div>
                                </li>
                            @empty
                                <li class="is-empty">{{ $alerta['vacio'] }}</li>
                            @endforelse
                        </ul>
                    </article>
                @endforeach
            </div>

            <article class="patient-clinical-panel patient-family-panel patient-dashboard-card hce-card-motion panel-animate" style="--panel-delay: 140ms; --card-accent: #0077b6">
                <header class="patient-panel-title">
                    <span><iconify-icon icon="{{ $bloques['familiares']['icono'] }}" aria-hidden="true"></iconify-icon></span>
                    <h2>{{ $bloques['familiares']['titulo'] }}</h2>
                    <button type="button" wire:click="abrirDrawer('familiares')" aria-label="Editar antecedentes familiares">
                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                    </button>
                </header>

                <div class="patient-family-table">
                    <div>
                        <span>Parentesco</span>
                        <span>Condicion</span>
                        <span>Periodo</span>
                        <span>Estado</span>
                    </div>

                    @forelse ($bloques['familiares']['items'] as $item)
                        <div>
                            <span>{{ $item['parentesco'] }}</span>
                            <span>{{ $item['condicion'] }}</span>
                            <span>{{ $item['periodo'] }}</span>
                            <span>{{ $item['estado'] }}</span>
                        </div>
                    @empty
                        <p>No hay antecedentes familiares registrados.</p>
                    @endforelse
                </div>
            </article>

            <div class="patient-card-pair">
                @foreach (['cirugias', 'hospitalizaciones'] as $clave)
                    <article class="patient-clinical-panel patient-list-panel patient-dashboard-card hce-card-motion panel-animate" style="--panel-delay: {{ $clave === 'cirugias' ? '200ms' : '260ms' }}; --card-accent: {{ $clave === 'cirugias' ? '#00b4d8' : '#17a2b8' }}">
                        <header class="patient-panel-title">
                            <span><iconify-icon icon="{{ $bloques[$clave]['icono'] }}" aria-hidden="true"></iconify-icon></span>
                            <h2>{{ $bloques[$clave]['titulo'] }}</h2>
                            <button type="button" wire:click="abrirDrawer('{{ $clave }}')" aria-label="Editar {{ $bloques[$clave]['titulo'] }}">
                                <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                            </button>
                        </header>

                        <ul class="patient-detail-list">
                            @forelse ($bloques[$clave]['items'] as $item)
                                <li>
                                    <span></span>
                                    <div>
                                        <strong>{{ $item['principal'] }}</strong>
                                        <small>{{ $item['detalle'] }}</small>
                                    </div>
                                </li>
                            @empty
                                <li class="is-empty">Sin registros clinicos.</li>
                            @endforelse
                        </ul>
                    </article>
                @endforeach
            </div>

            <div class="patient-card-pair">
                @foreach (['medicamentos', 'patologicos'] as $clave)
                    <article class="patient-clinical-panel patient-list-panel patient-dashboard-card hce-card-motion panel-animate" style="--panel-delay: {{ $clave === 'medicamentos' ? '320ms' : '380ms' }}; --card-accent: {{ $clave === 'medicamentos' ? '#10669a' : '#415a77' }}">
                        <header class="patient-panel-title">
                            <span><iconify-icon icon="{{ $bloques[$clave]['icono'] }}" aria-hidden="true"></iconify-icon></span>
                            <h2>{{ $bloques[$clave]['titulo'] }}</h2>
                            <button type="button" wire:click="abrirDrawer('{{ $clave }}')" aria-label="Editar {{ $bloques[$clave]['titulo'] }}">
                                <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                            </button>
                        </header>

                        <ul class="patient-detail-list">
                            @forelse ($bloques[$clave]['items'] as $item)
                                <li>
                                    <span></span>
                                    <div>
                                        <strong>{{ $item['principal'] }}</strong>
                                        <small>{{ $item['detalle'] }}</small>
                                    </div>
                                </li>
                            @empty
                                <li class="is-empty">{{ $clave === 'medicamentos' ? 'Sin medicamentos habituales registrados.' : 'Sin antecedentes patologicos registrados.' }}</li>
                            @endforelse
                        </ul>
                    </article>
                @endforeach
            </div>
        </div>

        <aside class="patient-trends-panel patient-dashboard-card hce-card-motion panel-animate" style="--panel-delay: 180ms; --card-accent: #00b4d8">
            <header>
                <iconify-icon icon="lucide:chart-no-axes-combined" aria-hidden="true"></iconify-icon>
                <h2>Tendencias de Signos Vitales</h2>
                <span>Ultimos 6 registros</span>
            </header>

            <div class="patient-trend-item">
                <div>
                    <span>Presion arterial</span>
                    <strong>{{ $tendencias['presion']['valor'] }}</strong>
                </div>
                <svg viewBox="0 0 170 62" role="img" aria-label="Tendencia de presion arterial">
                    <polyline class="is-secondary" points="{{ $tendencias['presion']['serie_secundaria'] }}"></polyline>
                    <polyline points="{{ $tendencias['presion']['serie_principal'] }}"></polyline>
                </svg>
            </div>

            <div class="patient-trend-item">
                <div>
                    <span>Frecuencia cardiaca</span>
                    <strong>{{ $tendencias['frecuencia']['valor'] }} <small>{{ $tendencias['frecuencia']['unidad'] }}</small></strong>
                </div>
                <svg viewBox="0 0 170 62" role="img" aria-label="Tendencia de frecuencia cardiaca">
                    <polyline class="is-green" points="{{ $tendencias['frecuencia']['serie_principal'] }}"></polyline>
                </svg>
            </div>

            <div class="patient-trend-item">
                <div>
                    <span>Peso</span>
                    <strong>{{ $tendencias['peso']['valor'] }} <small>{{ $tendencias['peso']['unidad'] }}</small></strong>
                </div>
                <svg viewBox="0 0 170 62" role="img" aria-label="Tendencia de peso">
                    <polyline class="is-muted" points="{{ $tendencias['peso']['serie_principal'] }}"></polyline>
                </svg>
            </div>
        </aside>
    </div>

    @if ($drawer['abierto'])
        <div class="patient-drawer-backdrop" wire:click="cerrarDrawer"></div>

        <aside class="patient-drawer patient-clinical-drawer {{ $drawer['ancho'] }} is-{{ $drawer['clave'] }}" style="--drawer-accent: {{ $drawer['acento'] }}" aria-modal="true" role="dialog">
            <header>
                <div>
                    <p>Gestion clinica</p>
                    <h2>{{ $drawer['titulo'] }}</h2>
                </div>
                <button type="button" wire:click="cerrarDrawer" aria-label="Cerrar drawer">
                    <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                </button>
            </header>

            <section class="patient-drawer-records">
                <div class="patient-drawer-toolbar">
                    <strong>
                        <span class="patient-drawer-section-icon">
                            <iconify-icon icon="{{ $drawer['icono'] }}" aria-hidden="true"></iconify-icon>
                        </span>
                        {{ $drawer['titulo'] }}
                    </strong>
                    <button type="button" wire:click="nuevoRegistro">
                        <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                        Nuevo
                    </button>
                </div>

                @if ($drawer['registros']->isNotEmpty())
                    <table class="patient-drawer-table">
                        <thead>
                            <tr>
                                @switch($drawer['clave'])
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
                            @foreach ($drawer['registros'] as $registro)
                                <tr>
                                    @switch($drawer['clave'])
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
                                            <button type="button" wire:click="editarRegistro({{ $registro->id }})" aria-label="Editar registro">
                                                <iconify-icon icon="lucide:pencil" aria-hidden="true"></iconify-icon>
                                            </button>
                                            <button
                                                type="button"
                                                data-confirm-delete-paciente-dashboard
                                                data-registro-id="{{ $registro->id }}"
                                                data-registro-nombre="{{ $registro->periodo ?: 'registro clinico' }}"
                                                data-catalogo-singular="registro clinico"
                                                data-delete-method="eliminarRegistro"
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

            <form class="patient-drawer-form" wire:submit.prevent="guardarRegistro">
                <h3>{{ $registroEditandoId ? 'Editar registro' : 'Nuevo registro' }}</h3>

                <div class="patient-drawer-form-grid {{ $drawer['ancho'] }}">
                @if ($drawer['clave'] === 'alergias')
                    <label>
                        Alergia
                        <select wire:model="formulario.alergia_id">
                            <option value="">Seleccione</option>
                            @foreach ($drawer['opciones']['alergias'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('formulario.alergia_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Agente causal <input type="text" wire:model.blur="formulario.agente_causal">@error('formulario.agente_causal') <span>{{ $message }}</span> @enderror</label>
                    <label>Alergeno <input type="text" wire:model.blur="formulario.alergeno">@error('formulario.alergeno') <span>{{ $message }}</span> @enderror</label>
                    <label>Periodo <input type="text" wire:model.blur="formulario.periodo">@error('formulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($drawer['clave'] === 'reacciones')
                    <label>Medicamento DCI <input type="text" wire:model.blur="formulario.dci_medicamento">@error('formulario.dci_medicamento') <span>{{ $message }}</span> @enderror</label>
                    <label>Descripcion <textarea rows="3" wire:model.blur="formulario.descripcion"></textarea>@error('formulario.descripcion') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Tipo
                        <select wire:model="formulario.tipo">
                            <option value="">Seleccione</option>
                            @foreach ($drawer['opciones']['tipos_ram'] as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('formulario.tipo') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>
                        Gravedad
                        <select wire:model="formulario.gravedad">
                            <option value="">Seleccione</option>
                            @foreach ($drawer['opciones']['gravedades_ram'] as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('formulario.gravedad') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="formulario.periodo">@error('formulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($drawer['clave'] === 'familiares')
                    <label>
                        Parentesco
                        <select wire:model="formulario.parentesco">
                            <option value="">Seleccione</option>
                            @foreach ($drawer['opciones']['parentescos'] as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('formulario.parentesco') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Denominacion <input type="text" wire:model.blur="formulario.denominacion">@error('formulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Diagnostico CIE10
                        <select wire:model="formulario.diagnostico_cie10_id">
                            <option value="">Seleccione</option>
                            @foreach ($drawer['opciones']['diagnosticos'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('formulario.diagnostico_cie10_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="formulario.periodo">@error('formulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($drawer['clave'] === 'cirugias')
                    <label>Codigo CPMS <input type="text" wire:model.blur="formulario.codigo_cpms">@error('formulario.codigo_cpms') <span>{{ $message }}</span> @enderror</label>
                    <label>Denominacion <input type="text" wire:model.blur="formulario.denominacion">@error('formulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>Periodo <input type="text" wire:model.blur="formulario.periodo">@error('formulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($drawer['clave'] === 'hospitalizaciones')
                    <label>Codigo UPS <input type="text" wire:model.blur="formulario.codigo_ups">@error('formulario.codigo_ups') <span>{{ $message }}</span> @enderror</label>
                    <label>Denominacion <input type="text" wire:model.blur="formulario.denominacion">@error('formulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Diagnostico CIE10
                        <select wire:model="formulario.diagnostico_cie10_id">
                            <option value="">Seleccione</option>
                            @foreach ($drawer['opciones']['diagnosticos'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('formulario.diagnostico_cie10_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="formulario.periodo">@error('formulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($drawer['clave'] === 'medicamentos')
                    <label>Medicamento habitual <input type="text" wire:model.blur="formulario.medicacion">@error('formulario.medicacion') <span>{{ $message }}</span> @enderror</label>
                    <label>Periodo <input type="text" wire:model.blur="formulario.periodo">@error('formulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @elseif ($drawer['clave'] === 'patologicos')
                    <label>Denominacion <input type="text" wire:model.blur="formulario.denominacion">@error('formulario.denominacion') <span>{{ $message }}</span> @enderror</label>
                    <label>Origen <input type="text" wire:model.blur="formulario.origen">@error('formulario.origen') <span>{{ $message }}</span> @enderror</label>
                    <label>
                        Diagnostico CIE10
                        <select wire:model="formulario.diagnostico_cie10_id">
                            <option value="">Seleccione</option>
                            @foreach ($drawer['opciones']['diagnosticos'] as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('formulario.diagnostico_cie10_id') <span>{{ $message }}</span> @enderror
                    </label>
                    <label>Periodo <input type="text" wire:model.blur="formulario.periodo">@error('formulario.periodo') <span>{{ $message }}</span> @enderror</label>
                @endif
                </div>

                <footer>
                    <button type="button" class="patient-drawer-secondary" wire:click="cerrarDrawer">Cerrar</button>
                    <button type="submit" class="catalog-primary-action">
                        <iconify-icon icon="lucide:save" aria-hidden="true"></iconify-icon>
                        Guardar
                    </button>
                </footer>
            </form>
        </aside>
    @endif
</section>
