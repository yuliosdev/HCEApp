@extends('layouts.app')

@section('title', 'Panel principal HCEApp')

@section('content')
    @php
        $tarjetas = [
            [
                'titulo' => 'Atenciones abiertas',
                'valor' => $episodiosAbiertos,
                'detalle' => 'Episodios en curso',
                'variacion' => '+12.4%',
                'contexto' => 'flujo actual',
                'color' => 'azul',
                'icono' => 'lucide:briefcase-medical',
            ],
            [
                'titulo' => 'Atenciones cerradas',
                'valor' => $episodiosCerrados,
                'detalle' => 'Altas registradas',
                'variacion' => '+8.7%',
                'contexto' => 'turno anterior',
                'color' => 'ambar',
                'icono' => 'lucide:shield-check',
            ],
            [
                'titulo' => 'Pacientes registrados',
                'valor' => $pacientesRegistrados,
                'detalle' => 'Base clinica',
                'variacion' => '+4.3%',
                'contexto' => 'en seguimiento',
                'color' => 'cian',
                'icono' => 'lucide:users',
            ],
            [
                'titulo' => 'Profesionales activos',
                'valor' => $profesionalesActivos,
                'detalle' => 'Equipo disponible',
                'variacion' => '-2.5%',
                'contexto' => 'por asignar',
                'color' => 'rojo',
                'icono' => 'lucide:user-plus',
            ],
        ];

        $flujoAtencion = [
            ['etiqueta' => 'Triaje', 'valor' => 39.7, 'color' => 'neutro'],
            ['etiqueta' => 'Evaluacion', 'valor' => 28.3, 'color' => 'azul'],
            ['etiqueta' => 'Tratamiento', 'valor' => 17.4, 'color' => 'cian'],
            ['etiqueta' => 'Observacion', 'valor' => 14.6, 'color' => 'marino'],
        ];

        $barras = [58, 69, 50, 59, 48, 78, 75, 61, 66, 56];
        $actividad = [
            ['titulo' => 'Triajes registrados', 'tiempo' => '2h 10min', 'porcentaje' => '39.7%'],
            ['titulo' => 'Historias por cerrar', 'tiempo' => '3h 15min', 'porcentaje' => '28.3%'],
            ['titulo' => 'Altas generadas', 'tiempo' => '1h 24min', 'porcentaje' => '17.4%'],
            ['titulo' => 'Pendientes clinicos', 'tiempo' => '5h 19min', 'porcentaje' => '14.6%'],
        ];
        $rendimiento = [
            ['titulo' => 'Pacientes en observacion', 'valor' => '10k', 'variacion' => '25.8%'],
            ['titulo' => 'Interconsultas pendientes', 'valor' => '5k', 'variacion' => '4.3%'],
            ['titulo' => 'Atenciones resueltas', 'valor' => '15k', 'variacion' => '12.5'],
        ];
    @endphp

    <section class="dashboard-page">
        <div class="dashboard-heading panel-animate">
            <div>
                <p class="dashboard-kicker">Panel principal HCEApp</p>
                <h1>Centro operativo de emergencias</h1>
            </div>
            <a href="#" class="dashboard-primary-action">
                <span>
                    <iconify-icon icon="lucide:plus" aria-hidden="true"></iconify-icon>
                </span>
                Nueva atencion
            </a>
        </div>

        <section class="dashboard-stat-grid">
            @foreach ($tarjetas as $indice => $tarjeta)
                <article class="dashboard-stat-card panel-animate accent-{{ $tarjeta['color'] }}" style="--panel-delay: {{ $indice * 90 }}ms">
                    <span class="dashboard-stat-icon">
                        <iconify-icon icon="{{ $tarjeta['icono'] }}" aria-hidden="true"></iconify-icon>
                    </span>
                    <div>
                        <p class="dashboard-stat-value" data-count-target="{{ $tarjeta['valor'] }}">0</p>
                        <p class="dashboard-stat-title">{{ $tarjeta['titulo'] }}</p>
                        <p class="dashboard-stat-trend">
                            <strong>{{ $tarjeta['variacion'] }}</strong>
                            <span>{{ $tarjeta['contexto'] }}</span>
                        </p>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="dashboard-main-grid">
            <article class="dashboard-panel dashboard-panel-large panel-animate" style="--panel-delay: 120ms">
                <div class="dashboard-panel-header">
                    <div>
                        <h2>Resumen operativo</h2>
                        <p>Distribucion de atenciones del periodo actual</p>
                    </div>
                    <button type="button" class="dashboard-more-button" aria-label="Mas opciones">...</button>
                </div>

                <div class="dashboard-flow-labels">
                    @foreach ($flujoAtencion as $segmento)
                        <span>{{ $segmento['etiqueta'] }}</span>
                    @endforeach
                </div>

                <div class="dashboard-flow-bar" aria-label="Distribucion operativa">
                    @foreach ($flujoAtencion as $segmento)
                        <span class="flow-{{ $segmento['color'] }}" style="width: {{ $segmento['valor'] }}%">
                            {{ $segmento['valor'] }}%
                        </span>
                    @endforeach
                </div>

                <div class="dashboard-activity-list">
                    @foreach ($actividad as $item)
                        <div class="dashboard-activity-item">
                            <span class="dashboard-activity-icon">
                                <iconify-icon icon="lucide:file-text" aria-hidden="true"></iconify-icon>
                            </span>
                            <span>{{ $item['titulo'] }}</span>
                            <strong>{{ $item['tiempo'] }}</strong>
                            <em>{{ $item['porcentaje'] }}</em>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="dashboard-panel panel-animate" style="--panel-delay: 180ms">
                <div class="dashboard-panel-header">
                    <div>
                        <h2>Estadisticas clinicas</h2>
                        <p>Total de movimientos {{ number_format(array_sum($barras) * 100) }}</p>
                    </div>
                    <button type="button" class="dashboard-month-button">Agosto</button>
                </div>

                <div class="dashboard-bar-chart" aria-label="Movimientos diarios">
                    <div class="dashboard-chart-grid"></div>
                    @foreach ($barras as $indice => $barra)
                        <div class="dashboard-bar-column">
                            <span style="height: {{ $barra }}%"></span>
                            <small>{{ $indice + 1 }} Ago</small>
                        </div>
                    @endforeach
                </div>

                <div class="dashboard-chart-legend">
                    <span><i class="legend-alerta"></i> Triajes</span>
                    <span><i class="legend-atencion"></i> Atenciones</span>
                </div>
            </article>
        </section>

        <section class="dashboard-bottom-grid">
            <article class="dashboard-panel panel-animate" style="--panel-delay: 240ms">
                <div class="dashboard-panel-header">
                    <div>
                        <h2>Rendimiento de atencion</h2>
                        <p>12% de incremento este mes</p>
                    </div>
                    <button type="button" class="dashboard-more-button" aria-label="Mas opciones">...</button>
                </div>

                <div class="dashboard-performance-list">
                    @foreach ($rendimiento as $item)
                        <div>
                            <span class="dashboard-performance-icon">
                                <iconify-icon icon="lucide:briefcase-medical" aria-hidden="true"></iconify-icon>
                            </span>
                            <span>
                                <strong>{{ $item['titulo'] }}</strong>
                                <em>{{ $item['variacion'] }}</em>
                            </span>
                            <b>{{ $item['valor'] }}</b>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="dashboard-panel dashboard-donut-panel panel-animate" style="--panel-delay: 300ms">
                <div class="dashboard-panel-header">
                    <div>
                        <h2>Prioridades clinicas</h2>
                        <p>Promedio de casos criticos</p>
                    </div>
                    <button type="button" class="dashboard-more-button" aria-label="Mas opciones">...</button>
                </div>

                <div class="dashboard-donut" style="--donut-percent: 30%">
                    <div>
                        <strong data-count-target="30">0</strong><span>%</span>
                        <p>AVG. Criticos</p>
                    </div>
                </div>
            </article>

            <article class="dashboard-panel panel-animate" style="--panel-delay: 360ms">
                <div class="dashboard-panel-header">
                    <div>
                        <h2>Ordenes por estado</h2>
                        <p>{{ $episodiosAbiertos + $episodiosCerrados }} operaciones en proceso</p>
                    </div>
                    <button type="button" class="dashboard-more-button" aria-label="Mas opciones">...</button>
                </div>

                <div class="dashboard-status-tabs">
                    <span class="is-active">Nuevo</span>
                    <span>Preparando</span>
                    <span>Cerrando</span>
                </div>

                <div class="dashboard-timeline">
                    <div>
                        <span class="timeline-dot timeline-green"></span>
                        <strong>ADMISION</strong>
                        <p>Paciente Principal</p>
                        <em>Documento y filiacion listos.</em>
                    </div>
                    <div>
                        <span class="timeline-dot timeline-blue"></span>
                        <strong>TRIAJE</strong>
                        <p>Equipo activo</p>
                        <em>Atencion pendiente de seguimiento.</em>
                    </div>
                </div>
            </article>
        </section>
    </section>
@endsection
