<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historia Clinica de Emergencia {{ $atencion->id }}</title>
    <style>
        @page {
            margin: 24px 26px;
        }

        body {
            color: #152238;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            margin: 0;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #0077b6;
            margin-bottom: 12px;
            padding-bottom: 10px;
        }

        .brand {
            color: #0077b6;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .title {
            color: #001340;
            font-size: 20px;
            font-weight: 700;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .subtitle {
            color: #415a77;
            font-size: 9px;
            margin-top: 5px;
        }

        .header-table,
        .data-table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-table td {
            vertical-align: top;
        }

        .patient-name {
            color: #001340;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .status-badge {
            border: 1px solid #0077b6;
            border-radius: 12px;
            color: #0077b6;
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            padding: 3px 8px;
            text-transform: uppercase;
        }

        .status-badge.is-closed {
            border-color: #b91c1c;
            color: #b91c1c;
        }

        .priority-box {
            background: #eaf6fb;
            border: 1px solid #98c1d9;
            color: #001340;
            font-weight: 700;
            padding: 8px 10px;
            text-align: center;
        }

        .priority-box strong {
            display: block;
            font-size: 14px;
            margin-top: 2px;
        }

        .section {
            border: 1px solid #c8d6e3;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .section-title {
            background: #f3f8fb;
            border-bottom: 1px solid #c8d6e3;
            color: #001340;
            font-size: 11px;
            font-weight: 700;
            padding: 7px 9px;
            text-transform: uppercase;
        }

        .section-body {
            padding: 8px 9px;
        }

        .grid-2 {
            border-collapse: collapse;
            width: 100%;
        }

        .grid-2 > tbody > tr > td {
            padding: 0 7px 7px 0;
            vertical-align: top;
            width: 50%;
        }

        .grid-3 {
            border-collapse: collapse;
            width: 100%;
        }

        .grid-3 > tbody > tr > td {
            padding: 0 7px 7px 0;
            vertical-align: top;
            width: 33.33%;
        }

        .field {
            border: 1px solid #d7e3ec;
            min-height: 30px;
            padding: 5px 6px;
        }

        .label {
            color: #415a77;
            display: block;
            font-size: 7px;
            font-weight: 700;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .value {
            color: #152238;
            font-size: 9px;
            font-weight: 600;
            white-space: pre-line;
        }

        .muted {
            color: #64748b;
            font-weight: 400;
        }

        .data-table th {
            background: #0077b6;
            border: 1px solid #0077b6;
            color: #ffffff;
            font-size: 7px;
            padding: 5px;
            text-align: left;
            text-transform: uppercase;
        }

        .data-table td {
            border: 1px solid #d7e3ec;
            padding: 5px;
            vertical-align: top;
        }

        .data-table .empty {
            color: #64748b;
            font-style: italic;
            text-align: center;
        }

        .chip {
            background: #dbeafe;
            border-radius: 10px;
            color: #004b83;
            display: inline-block;
            font-size: 7px;
            font-weight: 700;
            padding: 2px 6px;
            text-transform: uppercase;
        }

        .footer {
            border-top: 1px solid #d7e3ec;
            color: #64748b;
            font-size: 7px;
            margin-top: 10px;
            padding-top: 6px;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $texto = function ($valor): string {
            if ($valor instanceof \BackedEnum) {
                $valor = $valor->value;
            }

            return filled($valor) ? (string) $valor : '--';
        };

        $fecha = fn ($valor): string => $valor ? $valor->format('d/m/Y H:i') : '--';

        $profesional = function ($profesional) use ($texto): string {
            if (! $profesional) {
                return '--';
            }

            return collect([
                $profesional->nombres,
                $profesional->especialidad?->nombre,
                $profesional->cmp ? 'CMP '.$profesional->cmp : null,
                $profesional->rne ? 'RNE '.$profesional->rne : null,
            ])->filter()->implode(' - ');
        };

        $codigoCie10 = fn ($diagnostico): string => $diagnostico?->codigo ? $diagnostico->codigo : '--';
        $nombreCie10 = fn ($diagnostico): string => $diagnostico?->nombre ? $diagnostico->nombre : '--';

        $glasgowTotal = $atencion->eg_total;
        if ($glasgowTotal === null && $atencion->eg_ocular !== null && $atencion->eg_verbal !== null && $atencion->eg_motora !== null) {
            $glasgowTotal = (int) $atencion->eg_ocular + (int) $atencion->eg_verbal + (int) $atencion->eg_motora;
        }

        $glasgowEstado = match (true) {
            $glasgowTotal >= 13 && $glasgowTotal <= 15 => 'Normal / Leve',
            $glasgowTotal >= 9 && $glasgowTotal <= 12 => 'Moderado',
            $glasgowTotal >= 3 && $glasgowTotal <= 8 => 'Grave / Coma',
            default => '--',
        };
    @endphp

    <header class="header">
        <table class="header-table">
            <tr>
                <td style="width: 72%;">
                    <p class="brand">HCEApp</p>
                    <h1 class="title">Historia Clinica Electronica de Emergencia</h1>
                    <p class="subtitle">
                        Registro HCE: {{ $atencion->id }} |
                        Adm: {{ $texto($atencion->numero_atencion) }} |
                        Generado: {{ $generadoEn->format('d/m/Y H:i') }} |
                        Usuario: {{ $generadoPor?->name ?? '--' }}
                    </p>
                </td>
                <td style="text-align: right;">
                    <span class="status-badge {{ $texto($atencion->estado_atencion) === 'Cerrada' ? 'is-closed' : '' }}">
                        {{ $texto($atencion->estado_atencion) }}
                    </span>
                    <div class="priority-box" style="margin-top: 8px;">
                        Prioridad
                        <strong>{{ $atencion->prioridad?->orden ? 'Nivel '.$atencion->prioridad->orden : '--' }}</strong>
                        {{ $texto($atencion->prioridad?->prioridad) }}
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <section class="section">
        <h2 class="section-title">1. Identificacion del episodio y paciente</h2>
        <div class="section-body">
            <p class="patient-name">{{ $texto($paciente?->nombre ?: $atencion->alias) }}</p>
            <table class="grid-3">
                <tr>
                    <td><div class="field"><span class="label">Historia</span><span class="value">{{ $texto($paciente?->numero_historia) }}</span></div></td>
                    <td><div class="field"><span class="label">Tipo / documento</span><span class="value">{{ $texto($paciente?->tipo_documento) }} {{ $texto($paciente?->numero_documento) }}</span></div></td>
                    <td><div class="field"><span class="label">Sexo / nacimiento</span><span class="value">{{ $texto($paciente?->sexo) }} | {{ $paciente?->fecha_nacimiento?->format('d/m/Y') ?? '--' }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Grupo sanguineo</span><span class="value">{{ $texto($paciente?->grupo_sanguineo) }}</span></div></td>
                    <td><div class="field"><span class="label">Factor RH</span><span class="value">{{ $texto($paciente?->factor_rh) }}</span></div></td>
                    <td><div class="field"><span class="label">Telefono</span><span class="value">{{ $texto($paciente?->telefono) }}</span></div></td>
                </tr>
                <tr>
                    <td colspan="2"><div class="field"><span class="label">Direccion</span><span class="value">{{ $texto($paciente?->direccion) }}</span></div></td>
                    <td><div class="field"><span class="label">Alias de atencion</span><span class="value">{{ $texto($atencion->alias) }}</span></div></td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">2. Datos de la atencion</h2>
        <div class="section-body">
            <table class="grid-2">
                <tr>
                    <td><div class="field"><span class="label">Fecha / hora episodio</span><span class="value">{{ $fecha($atencion->fecha_hora_inicio_episodio) }}</span></div></td>
                    <td><div class="field"><span class="label">Fecha / hora ingreso</span><span class="value">{{ $fecha($atencion->fecha_hora_atencion) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Topico</span><span class="value">{{ $texto($atencion->topico) }}</span></div></td>
                    <td><div class="field"><span class="label">Ambiente</span><span class="value">{{ $texto($atencion->ambiente) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Acompanante</span><span class="value">{{ $texto($atencion->acompanante) }}</span></div></td>
                    <td><div class="field"><span class="label">Parentesco / telefono</span><span class="value">{{ $texto($atencion->parentesco) }} | {{ $texto($atencion->telefono) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Fuente</span><span class="value">{{ $texto($atencion->fuente) }}</span></div></td>
                    <td><div class="field"><span class="label">Recepcion</span><span class="value">{{ $fecha($atencion->fecha_hora_recepcion) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Lugar del evento</span><span class="value">{{ $texto($atencion->lugar_evento) }}</span></div></td>
                    <td><div class="field"><span class="label">Tipo lesion / evento</span><span class="value">{{ $texto($atencion->tipo_lesion_evento) }}</span></div></td>
                </tr>
                <tr>
                    <td colspan="2"><div class="field"><span class="label">Signos vitales transmitidos</span><span class="value">{{ $texto($atencion->signos_vitales_transmitidos) }}</span></div></td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">3. Triaje y anamnesis</h2>
        <div class="section-body">
            <table class="grid-3">
                <tr>
                    <td><div class="field"><span class="label">Fecha / hora triaje</span><span class="value">{{ $fecha($atencion->fecha_hora_triaje) }}</span></div></td>
                    <td><div class="field"><span class="label">Profesional triaje</span><span class="value">{{ $profesional($atencion->profesionalTriaje) }}</span></div></td>
                    <td><div class="field"><span class="label">Destino / topico</span><span class="value">{{ $texto($atencion->destino_topico) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Color triaje</span><span class="value">{{ $texto($atencion->color_triaje) }}</span></div></td>
                    <td><div class="field"><span class="label">Tiempo espera</span><span class="value">{{ $texto($atencion->tiempo_espera_calculado) }}</span></div></td>
                    <td><div class="field"><span class="label">Reevaluacion</span><span class="value">{{ $fecha($atencion->reevaluacion_programada) }}</span></div></td>
                </tr>
            </table>

            <table class="data-table" style="margin-bottom: 8px;">
                <thead>
                    <tr>
                        <th>Funcion vital</th>
                        <th>Valor</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($atencion->signosVitalesTriaje as $signo)
                        <tr>
                            <td>{{ $texto($signo->signoVital?->descripcion) }}</td>
                            <td>{{ $texto($signo->valor) }}</td>
                            <td>{{ $texto($signo->signoVital?->unidad) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">No se registraron signos vitales de triaje.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="grid-2">
                <tr>
                    <td><div class="field"><span class="label">Fecha / hora anamnesis</span><span class="value">{{ $fecha($atencion->fecha_hora_anamnesis) }}</span></div></td>
                    <td><div class="field"><span class="label">Tiempo enfermedad</span><span class="value">{{ $texto($atencion->tiempo_enfermedad) }} {{ $texto($atencion->unidad_tiempo_enfermedad) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Signos y sintomas</span><span class="value">{{ $texto($atencion->signos_sintomas) }}</span></div></td>
                    <td><div class="field"><span class="label">Desarrollo cronologico</span><span class="value">{{ $texto($atencion->desarrollo_cronologico) }}</span></div></td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">4. Antecedentes clinicos del paciente</h2>
        <div class="section-body">
            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>Alergia</th><th>Agente causal</th><th>Alergeno</th><th>Periodo</th></tr></thead>
                <tbody>
                    @forelse ($paciente?->alergias ?? collect() as $alergia)
                        <tr><td>{{ $texto($alergia->alergia?->nombre) }}</td><td>{{ $texto($alergia->agente_causal) }}</td><td>{{ $texto($alergia->alergeno) }}</td><td>{{ $texto($alergia->periodo) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty">No se registraron alergias.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>RAM</th><th>Tipo</th><th>Gravedad</th><th>DCI medicamento</th><th>Periodo</th></tr></thead>
                <tbody>
                    @forelse ($paciente?->reaccionesAdversasMedicamentos ?? collect() as $ram)
                        <tr><td>{{ $texto($ram->descripcion) }}</td><td>{{ $texto($ram->tipo) }}</td><td>{{ $texto($ram->gravedad) }}</td><td>{{ $texto($ram->dci_medicamento) }}</td><td>{{ $texto($ram->periodo) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="empty">No se registraron reacciones adversas.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table">
                <thead><tr><th>Tipo</th><th>Dato</th><th>Codigo</th><th>Periodo</th></tr></thead>
                <tbody>
                    @forelse ($paciente?->antecedentesPatologicos ?? collect() as $item)
                        <tr><td>Antecedente patologico</td><td>{{ $texto($item->denominacion ?: $item->diagnosticoCie10?->nombre) }}</td><td>{{ $texto($item->diagnosticoCie10?->codigo) }}</td><td>{{ $texto($item->periodo) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty">No se registraron antecedentes patologicos.</td></tr>
                    @endforelse
                    @foreach ($paciente?->medicamentosHabituales ?? collect() as $item)
                        <tr><td>Medicamento habitual</td><td>{{ $texto($item->medicacion) }}</td><td>--</td><td>{{ $texto($item->periodo) }}</td></tr>
                    @endforeach
                    @foreach ($paciente?->antecedentesFamiliares ?? collect() as $item)
                        <tr><td>Antecedente familiar</td><td>{{ $texto($item->parentesco) }} - {{ $texto($item->denominacion ?: $item->diagnosticoCie10?->nombre) }}</td><td>{{ $texto($item->diagnosticoCie10?->codigo) }}</td><td>{{ $texto($item->periodo) }}</td></tr>
                    @endforeach
                    @foreach ($paciente?->cirugiasPrevias ?? collect() as $item)
                        <tr><td>Cirugia previa</td><td>{{ $texto($item->denominacion) }}</td><td>{{ $texto($item->codigo_cpms) }}</td><td>{{ $texto($item->periodo) }}</td></tr>
                    @endforeach
                    @foreach ($paciente?->hospitalizacionesPrevias ?? collect() as $item)
                        <tr><td>Hospitalizacion previa</td><td>{{ $texto($item->denominacion ?: $item->diagnosticoCie10?->nombre) }}</td><td>{{ $texto($item->codigo_ups ?: $item->ups?->codigo) }}</td><td>{{ $texto($item->periodo) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">5. Examen clinico</h2>
        <div class="section-body">
            <table class="grid-2">
                <tr>
                    <td><div class="field"><span class="label">Glasgow</span><span class="value">Ocular {{ $texto($atencion->eg_ocular) }} | Verbal {{ $texto($atencion->eg_verbal) }} | Motora {{ $texto($atencion->eg_motora) }} | Total {{ $texto($glasgowTotal) }} ({{ $glasgowEstado }})</span></div></td>
                    <td><div class="field"><span class="label">Estado general</span><span class="value">{{ $texto($atencion->estado_general) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Hidratacion / nutricion</span><span class="value">{{ $texto($atencion->hidratacion) }} | {{ $texto($atencion->nutricion) }}</span></div></td>
                    <td><div class="field"><span class="label">Conciencia / piel y anexos</span><span class="value">{{ $texto($atencion->estado_conciencia) }} | {{ $texto($atencion->piel_anexos) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Cabeza y cuello</span><span class="value">{{ $texto($atencion->cabeza_cuello) }}</span></div></td>
                    <td><div class="field"><span class="label">Torax respiratorio</span><span class="value">{{ $texto($atencion->torax_respiratorio) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Cardiovascular</span><span class="value">{{ $texto($atencion->cardiovascular) }}</span></div></td>
                    <td><div class="field"><span class="label">Abdomen</span><span class="value">{{ $texto($atencion->abdomen) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Neurologico</span><span class="value">{{ $texto($atencion->neurologico) }}</span></div></td>
                    <td><div class="field"><span class="label">Musculoesqueletico / extremidades</span><span class="value">{{ $texto($atencion->musculoesqueletico_extremidades) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Genitourinario / ginecoobstetrico</span><span class="value">{{ $texto($atencion->genitourinario_ginecoobstetrico) }}</span></div></td>
                    <td><div class="field"><span class="label">Otros</span><span class="value">{{ $texto($atencion->otros) }}</span></div></td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">6. Shock trauma, plan y firma</h2>
        <div class="section-body">
            <table class="grid-2">
                <tr>
                    <td><div class="field"><span class="label">Ingreso a shock trauma</span><span class="value">{{ $texto($atencion->en_shock_trauma) }} | {{ $fecha($atencion->shock_trauma_fecha_hora) }}</span></div></td>
                    <td><div class="field"><span class="label">Responsable</span><span class="value">{{ $texto($atencion->shock_trauma_responsable) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Resultado</span><span class="value">{{ $texto($atencion->shock_trauma_resultado) }}</span></div></td>
                    <td><div class="field"><span class="label">Destino shock trauma</span><span class="value">{{ $texto($atencion->shock_trauma_destino) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Registro especializado</span><span class="value">{{ $texto($atencion->registro_especializado) }}</span></div></td>
                    <td><div class="field"><span class="label">Plan de trabajo</span><span class="value">{{ $texto($atencion->plan_trabajo) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Profesional responsable</span><span class="value">{{ $profesional($atencion->profesionalResponsable) }}</span></div></td>
                    <td><div class="field"><span class="label">Firma</span><span class="value">{{ $texto($atencion->estado_firma) }} | {{ $fecha($atencion->fecha_hora_firma) }}</span></div></td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">7. Diagnosticos, ordenes y laboratorio</h2>
        <div class="section-body">
            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>CIE10</th><th>Denominacion</th><th>Tipo</th><th>Fecha</th><th>Observacion</th></tr></thead>
                <tbody>
                    @forelse ($atencion->diagnosticosIngreso as $dx)
                        <tr><td>{{ $codigoCie10($dx->diagnosticoCie10) }}</td><td>{{ $nombreCie10($dx->diagnosticoCie10) }}</td><td>{{ $texto($dx->tipo_diagnostico) }}</td><td>{{ $fecha($dx->fecha_hora) }}</td><td>{{ $texto($dx->observacion) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="empty">No se registraron diagnosticos de ingreso.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>Orden</th><th>Detalle</th><th>Prioridad</th><th>Fecha / hora</th><th>Resultado</th><th>Profesional</th></tr></thead>
                <tbody>
                    @forelse ($atencion->ordenes as $orden)
                        <tr><td>{{ $texto($orden->tipoOrden?->descripcion) }}</td><td>{{ $texto($orden->detalle) }}</td><td><span class="chip">{{ $texto($orden->prioridad) }}</span></td><td>{{ $fecha($orden->fecha_hora_solicitud) }}</td><td>{{ $texto($orden->resultado) }}</td><td>{{ $profesional($orden->profesionalSalud) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="empty">No se registraron ordenes.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table">
                <thead><tr><th>Examen</th><th>Prioridad</th><th>Fecha / hora</th><th>Resultado</th><th>Estado</th><th>Profesional</th></tr></thead>
                <tbody>
                    @forelse ($atencion->solicitudesExamenes as $examen)
                        <tr><td>{{ $texto($examen->examen?->descripcion) }}</td><td><span class="chip">{{ $texto($examen->prioridad) }}</span></td><td>{{ $fecha($examen->fecha_hora_solicitud) }}</td><td>{{ $texto($examen->resultado) }}</td><td>{{ $texto($examen->estado) }}</td><td>{{ $profesional($examen->profesionalSalud) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="empty">No se registraron examenes de laboratorio.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">8. Farmacos y procedimientos</h2>
        <div class="section-body">
            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>Medicamento</th><th>Presentacion</th><th>Dosis</th><th>Via</th><th>Duracion</th><th>Cantidad</th><th>Fecha</th><th>Profesional</th></tr></thead>
                <tbody>
                    @forelse ($atencion->prescripciones as $prescripcion)
                        <tr><td>{{ $texto($prescripcion->medicamento) }}</td><td>{{ $texto($prescripcion->presentacion) }}</td><td>{{ $texto($prescripcion->dosis) }}</td><td>{{ $texto($prescripcion->via) }}</td><td>{{ $texto($prescripcion->duracion) }}</td><td>{{ $texto($prescripcion->cantidad) }}</td><td>{{ $fecha($prescripcion->fecha_hora) }}</td><td>{{ $profesional($prescripcion->profesionalSalud) }}</td></tr>
                    @empty
                        <tr><td colspan="8" class="empty">No se registraron prescripciones.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table">
                <thead><tr><th>Codigo</th><th>Procedimiento</th><th>Sesiones</th><th>Fecha / hora</th></tr></thead>
                <tbody>
                    @forelse ($atencion->procedimientosRealizados as $procedimiento)
                        <tr><td>{{ $texto($procedimiento->procedimiento?->codigo) }}</td><td>{{ $texto($procedimiento->denominacion ?: $procedimiento->procedimiento?->denominacion) }}</td><td>{{ $texto($procedimiento->sesiones) }}</td><td>{{ $fecha($procedimiento->fecha_hora) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty">No se registraron procedimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">9. Evoluciones, interconsultas y enfermeria</h2>
        <div class="section-body">
            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>Fecha / hora</th><th>Profesional</th><th>Detalle clinico</th></tr></thead>
                <tbody>
                    @forelse ($atencion->evoluciones as $evolucion)
                        <tr><td>{{ $fecha($evolucion->fecha_hora) }}</td><td>{{ $profesional($evolucion->profesionalSalud) }}</td><td>{{ $texto($evolucion->detalle_clinico) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty">No se registraron evoluciones clinicas.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>Fecha / hora</th><th>Profesional</th><th>Nota</th></tr></thead>
                <tbody>
                    @forelse ($atencion->interconsultas as $interconsulta)
                        <tr><td>{{ $fecha($interconsulta->fecha_hora) }}</td><td>{{ $profesional($interconsulta->profesionalSalud) }}</td><td>{{ $texto($interconsulta->nota) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty">No se registraron interconsultas.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>Fecha / hora</th><th>Profesional</th><th>Nota</th><th>Firma</th></tr></thead>
                <tbody>
                    @forelse ($atencion->notasEnfermeria as $nota)
                        <tr><td>{{ $fecha($nota->fecha_hora) }}</td><td>{{ $profesional($nota->profesionalSalud) }}</td><td>{{ $texto($nota->nota) }}</td><td>{{ $texto($nota->firma) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty">No se registraron notas de enfermeria.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="grid-3">
                <tr>
                    <td><div class="field"><span class="label">Ingresos</span><span class="value">{{ $texto($atencion->ingresos) }}</span></div></td>
                    <td><div class="field"><span class="label">Egresos</span><span class="value">{{ $texto($atencion->egresos) }}</span></div></td>
                    <td><div class="field"><span class="label">Balance</span><span class="value">{{ $texto($atencion->balance) }}</span></div></td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">10. Movimientos, referencia, egreso y documentos</h2>
        <div class="section-body">
            <table class="data-table" style="margin-bottom: 8px;">
                <thead><tr><th>Fecha / hora</th><th>Origen</th><th>Destino</th><th>Motivo</th><th>Responsable</th></tr></thead>
                <tbody>
                    @forelse ($atencion->transferencias as $transferencia)
                        <tr><td>{{ $fecha($transferencia->fecha_hora) }}</td><td>{{ $texto($transferencia->origen) }}</td><td>{{ $texto($transferencia->destino) }}</td><td>{{ $texto($transferencia->motivo) }}</td><td>{{ $texto($transferencia->responsable) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="empty">No se registraron transferencias.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="grid-2">
                <tr>
                    <td><div class="field"><span class="label">IPRESS origen</span><span class="value">{{ $texto($atencion->referencia?->ipress_origen) }}</span></div></td>
                    <td><div class="field"><span class="label">UPS origen</span><span class="value">{{ $texto($atencion->referencia?->upsOrigen?->denominacion) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">IPRESS destino</span><span class="value">{{ $texto($atencion->referencia?->ipress_referencia) }}</span></div></td>
                    <td><div class="field"><span class="label">UPS destino</span><span class="value">{{ $texto($atencion->referencia?->upsDestino?->denominacion) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Solicitud / respuesta</span><span class="value">{{ $fecha($atencion->referencia?->fecha_hora_solicitud) }} | {{ $fecha($atencion->referencia?->fecha_hora_respuesta) }}</span></div></td>
                    <td><div class="field"><span class="label">Respuesta</span><span class="value">{{ $texto($atencion->referencia?->respuesta_solicitud) }}</span></div></td>
                </tr>
                <tr>
                    <td colspan="2"><div class="field"><span class="label">Motivo referencia</span><span class="value">{{ $texto($atencion->referencia?->motivo_referencia) }}</span></div></td>
                </tr>
            </table>

            <table class="data-table" style="margin: 8px 0;">
                <thead><tr><th>Clasificacion</th><th>CIE10</th><th>Denominacion</th><th>Tipo</th><th>Observaciones</th></tr></thead>
                <tbody>
                    @forelse ($atencion->diagnosticosEgreso as $dx)
                        <tr><td>{{ $texto($dx->clasificacion) }}</td><td>{{ $texto($dx->codigo_cie10 ?: $dx->diagnosticoCie10?->codigo) }}</td><td>{{ $texto($dx->denominacion ?: $dx->diagnosticoCie10?->nombre) }}</td><td>{{ $texto($dx->tipo_diagnostico) }}</td><td>{{ $texto($dx->observaciones) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="empty">No se registraron diagnosticos de egreso.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="grid-3">
                <tr>
                    <td><div class="field"><span class="label">Destino dentro IPRESS</span><span class="value">{{ $texto($atencion->destino_dentro_ipress) }}</span></div></td>
                    <td><div class="field"><span class="label">Destino fuera IPRESS</span><span class="value">{{ $texto($atencion->destino_fuera_ipress) }}</span></div></td>
                    <td><div class="field"><span class="label">Condicion egreso</span><span class="value">{{ $texto($atencion->condicion_egreso) }}</span></div></td>
                </tr>
                <tr>
                    <td><div class="field"><span class="label">Fecha / hora egreso</span><span class="value">{{ $fecha($atencion->fecha_hora_egreso) }}</span></div></td>
                    <td colspan="2"><div class="field"><span class="label">Profesional egreso</span><span class="value">{{ $profesional($atencion->profesionalEgreso) }}</span></div></td>
                </tr>
                <tr>
                    <td colspan="3"><div class="field"><span class="label">Indicaciones finales</span><span class="value">{{ $texto($atencion->indicaciones_finales) }}</span></div></td>
                </tr>
            </table>

            <table class="data-table" style="margin: 8px 0;">
                <thead><tr><th>Medicamento alta</th><th>Presentacion</th><th>Dosis</th><th>Via</th><th>Cantidad</th><th>Indicaciones</th></tr></thead>
                <tbody>
                    @forelse ($atencion->altaMedicamentos as $medicamento)
                        <tr><td>{{ $texto($medicamento->medicamento) }}</td><td>{{ $texto($medicamento->presentacion) }}</td><td>{{ $texto($medicamento->dosis) }}</td><td>{{ $texto($medicamento->via) }}</td><td>{{ $texto($medicamento->cantidad) }}</td><td>{{ $texto($medicamento->indicaciones) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="empty">No se registraron medicamentos de alta.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="data-table">
                <thead><tr><th>Tipo documento</th><th>Nombre / contenido</th><th>Cargado por</th><th>Fecha</th></tr></thead>
                <tbody>
                    @forelse ($atencion->documentosAdjuntos as $documento)
                        <tr><td>{{ $texto($documento->tipo) }}</td><td>{{ $texto($documento->contenido ?: basename((string) $documento->ruta_archivo)) }}</td><td>{{ $texto($documento->registradoPor?->name) }}</td><td>{{ $documento->created_at?->format('d/m/Y H:i') ?? '--' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty">No se registraron documentos adjuntos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <footer class="footer">
        Registrado por: {{ $texto($atencion->registradoPor?->name) }} |
        Actualizado por: {{ $texto($atencion->actualizadoPor?->name) }} |
        Ultima actualizacion: {{ $atencion->updated_at?->format('d/m/Y H:i') ?? '--' }}
    </footer>
</body>
</html>
