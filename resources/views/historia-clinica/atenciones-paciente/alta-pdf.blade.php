<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha de Alta del Paciente {{ $atencion->id }}</title>
    <style>
        @page {
            margin: 24px 30px 28px;
        }

        body {
            color: #142747;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
            margin: 0;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #2c6ea3;
            padding-bottom: 12px;
        }

        .header-table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 145px;
        }

        .logo {
            height: 110px;
            width: 110px;
        }

        .logo-fallback {
            background: #5cc1d3;
            border-radius: 60px;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            height: 92px;
            line-height: 1.15;
            padding-top: 28px;
            text-align: center;
            text-transform: uppercase;
            width: 120px;
        }

        .title-cell {
            padding-top: 8px;
            text-align: left;
        }

        .title {
            color: #0b2a50;
            font-size: 27px;
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1;
            text-transform: uppercase;
        }

        .subtitle {
            color: #0b2a50;
            font-size: 12px;
            letter-spacing: 7px;
            margin-top: 10px;
            text-transform: uppercase;
        }

        .header-date {
            color: #142747;
            font-size: 10px;
            padding-top: 70px;
            text-align: right;
            width: 165px;
        }

        .section {
            margin-top: 12px;
        }

        .section-title {
            background: #dcecf7;
            border-radius: 5px;
            color: #064a8f;
            font-size: 11px;
            font-weight: 700;
            padding: 8px 12px;
            text-transform: uppercase;
        }

        .section-body {
            background: #ffffff;
            border: 1px solid #d1e0ea;
            border-top: 0;
            border-radius: 0 0 5px 5px;
            padding: 10px 12px;
        }

        .patient-table,
        .egress-table,
        .treatment-table,
        .diagnosis-table {
            border-collapse: collapse;
            width: 100%;
        }

        .patient-table td {
            padding: 3px 10px;
            vertical-align: top;
        }

        .patient-table .split {
            border-left: 1px solid #cfdce8;
        }

        .label {
            color: #071b39;
            font-weight: 700;
            white-space: nowrap;
            width: 150px;
        }

        .value {
            color: #142747;
        }

        .diagnosis-table th,
        .treatment-table th {
            background: #edf6fb;
            border: 1px solid #d1e0ea;
            color: #071b39;
            font-size: 9px;
            font-weight: 700;
            padding: 7px 6px;
            text-align: left;
        }

        .diagnosis-table td,
        .treatment-table td {
            border: 1px solid #d1e0ea;
            color: #142747;
            padding: 8px 7px;
            vertical-align: top;
        }

        .diagnosis-line {
            color: #142747;
            font-size: 11px;
            padding: 2px 4px;
        }

        .diagnosis-code {
            color: #071b39;
            font-weight: 700;
        }

        .egress-table td {
            padding: 4px 22px;
            vertical-align: top;
        }

        .micro-label {
            color: #173b62;
            display: block;
            font-size: 6px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .micro-value {
            color: #071b39;
            font-size: 9px;
            font-weight: 700;
        }

        .treatment-table th,
        .treatment-table td {
            text-align: center;
        }

        .treatment-table td:nth-child(2),
        .treatment-table td:nth-child(3),
        .treatment-table td:nth-child(6) {
            text-align: left;
        }

        .medicine-name {
            color: #071b39;
            font-weight: 700;
        }

        .recommendations {
            border-collapse: collapse;
            width: 100%;
        }

        .recommendations td {
            vertical-align: top;
        }

        .recommendation-icon {
            background: #eaf5fb;
            border-radius: 8px;
            color: #2c6ea3;
            font-size: 34px;
            font-weight: 700;
            height: 58px;
            line-height: 58px;
            text-align: center;
            width: 68px;
        }

        .final-indications {
            color: #142747;
            font-size: 10px;
            padding-left: 12px;
            white-space: pre-line;
        }

        .empty {
            color: #64748b;
            font-style: italic;
            text-align: center;
        }

        .footer {
            border-top: 1px solid #d6e4ed;
            color: #64748b;
            font-size: 8px;
            margin-top: 14px;
            padding-top: 7px;
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

        $fecha = fn ($valor): string => $valor ? $valor->format('d/m/Y') : '--';
        $hora = fn ($valor): string => $valor ? $valor->format('h:i a') : '--';
        $fechaHora = fn ($valor): string => $valor ? $valor->format('d/m/Y H:i') : '--';
        $edad = $paciente?->fecha_nacimiento ? $paciente->fecha_nacimiento->age.' anos' : '--';
        $nombrePaciente = $paciente?->nombre ?: $atencion->alias;

        $profesional = function ($profesional) use ($texto): string {
            if (! $profesional) {
                return '--';
            }

            return collect([
                $profesional->nombres,
                $profesional->especialidad?->nombre,
                $profesional->cmp ? 'CMP '.$profesional->cmp : null,
            ])->filter()->implode(' - ');
        };
    @endphp

    <header class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if ($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="Clinica Santa Rosa">
                    @else
                        <div class="logo-fallback">Clinica<br>Santa Rosa</div>
                    @endif
                </td>
                <td class="title-cell">
                    <h1 class="title">Ficha de Alta del Paciente</h1>
                    <p class="subtitle">Informacion Medica al Egreso</p>
                </td>
                <td class="header-date">
                    <p>Fecha de alta: {{ $fecha($atencion->fecha_hora_egreso) }}</p>
                    <p>Hora: {{ $hora($atencion->fecha_hora_egreso) }}</p>
                </td>
            </tr>
        </table>
    </header>

    <section class="section">
        <h2 class="section-title">Datos del Paciente</h2>
        <div class="section-body">
            <table class="patient-table">
                <tr>
                    <td class="label">Nombres y apellidos:</td>
                    <td class="value">{{ $texto($nombrePaciente) }}</td>
                    <td class="label split">N. de historia clinica:</td>
                    <td class="value">{{ $texto($paciente?->numero_historia ?: $atencion->id) }}</td>
                </tr>
                <tr>
                    <td class="label">Documento:</td>
                    <td class="value">{{ $texto($paciente?->tipo_documento) }} {{ $texto($paciente?->numero_documento) }}</td>
                    <td class="label split">Servicio:</td>
                    <td class="value">Emergencia</td>
                </tr>
                <tr>
                    <td class="label">Fecha de nacimiento:</td>
                    <td class="value">{{ $paciente?->fecha_nacimiento?->format('d/m/Y') ?? '--' }}</td>
                    <td class="label split">Fecha de ingreso:</td>
                    <td class="value">{{ $fecha($atencion->fecha_hora_atencion) }}</td>
                </tr>
                <tr>
                    <td class="label">Edad:</td>
                    <td class="value">{{ $edad }}</td>
                    <td class="label split">Fecha de alta:</td>
                    <td class="value">{{ $fecha($atencion->fecha_hora_egreso) }}</td>
                </tr>
                <tr>
                    <td class="label">Sexo:</td>
                    <td class="value">{{ $texto($paciente?->sexo) }}</td>
                    <td class="label split">Tipo de alta:</td>
                    <td class="value">{{ $texto($atencion->condicion_egreso) }}</td>
                </tr>
                <tr>
                    <td class="label">Destino dentro IPRESS:</td>
                    <td class="value">{{ $texto($atencion->destino_dentro_ipress) }}</td>
                    <td class="label split">Destino fuera IPRESS:</td>
                    <td class="value">{{ $texto($atencion->destino_fuera_ipress) }}</td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">Diagnosticos</h2>
        <div class="section-body">
            @forelse ($atencion->diagnosticosIngreso as $diagnostico)
                <p class="diagnosis-line"><span class="diagnosis-code">{{ $texto($diagnostico->diagnosticoCie10?->codigo) }}</span> - {{ $texto($diagnostico->diagnosticoCie10?->nombre) }}</p>
            @empty
            @endforelse

            @forelse ($atencion->diagnosticosEgreso as $diagnostico)
                <p class="diagnosis-line"><span class="diagnosis-code">{{ $texto($diagnostico->codigo_cie10 ?: $diagnostico->diagnosticoCie10?->codigo) }}</span> - {{ $texto($diagnostico->denominacion ?: $diagnostico->diagnosticoCie10?->nombre) }} (Egreso)</p>
            @empty
            @endforelse

            @if ($atencion->diagnosticosIngreso->isEmpty() && $atencion->diagnosticosEgreso->isEmpty())
                <p class="empty">No se registraron diagnosticos.</p>
            @endif
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">Tratamiento al Alta</h2>
        <table class="treatment-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Cantidad</th>
                    <th style="width: 20%;">Medicamento</th>
                    <th style="width: 18%;">Presentacion</th>
                    <th style="width: 13%;">Dosis</th>
                    <th style="width: 11%;">Via</th>
                    <th>Indicaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($atencion->altaMedicamentos as $medicamento)
                    <tr>
                        <td>{{ $texto($medicamento->cantidad) }}</td>
                        <td><span class="medicine-name">{{ $texto($medicamento->medicamento) }}</span></td>
                        <td>{{ $texto($medicamento->presentacion) }}</td>
                        <td>{{ $texto($medicamento->dosis) }}</td>
                        <td>{{ $texto($medicamento->via) }}</td>
                        <td>{{ $texto($medicamento->indicaciones) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No se registraron medicamentos del alta.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title">Recomendaciones Generales</h2>
        <div class="section-body">
            <table class="recommendations">
                <tr>
                    <td style="width: 86px;"><div class="recommendation-icon">✓</div></td>
                    <td><div class="final-indications">{{ $texto($atencion->indicaciones_finales) }}</div></td>
                </tr>
            </table>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">Datos del Egreso</h2>
        <div class="section-body">
            <table class="egress-table">
                <tr>
                    <td><span class="micro-label">Fecha/Hora de Egreso</span><span class="micro-value">{{ $fechaHora($atencion->fecha_hora_egreso) }}</span></td>
                    <td><span class="micro-label">Profesional Responsable</span><span class="micro-value">{{ $profesional($atencion->profesionalEgreso) }}</span></td>
                </tr>
            </table>
        </div>
    </section>

    <footer class="footer">
        Generado: {{ $fechaHora($generadoEn) }} | Adm: {{ $texto($atencion->numero_atencion) }}
    </footer>
</body>
</html>
