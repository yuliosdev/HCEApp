<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Examenes de Laboratorio {{ $atencion->id }}</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            color: #172033;
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
            border-bottom: 3px solid #0f8fb0;
            padding-bottom: 14px;
        }

        .header-table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 110px;
        }

        .logo {
            height: 82px;
            width: 82px;
        }

        .logo-fallback {
            border: 2px solid #0f8fb0;
            border-radius: 48px;
            color: #0f8fb0;
            font-size: 9px;
            font-weight: 800;
            height: 78px;
            line-height: 1.15;
            padding-top: 24px;
            text-align: center;
            text-transform: uppercase;
            width: 78px;
        }

        .title-cell {
            text-align: center;
        }

        .title {
            color: #001340;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .subtitle {
            color: #4a6078;
            font-size: 9px;
            margin-top: 5px;
        }

        .patient-block {
            background: #f4f9fc;
            border: 1px solid #c9dbe7;
            margin-top: 16px;
            padding: 12px 14px;
        }

        .patient-name {
            color: #001340;
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 9px;
            text-transform: uppercase;
        }

        .patient-meta {
            border-collapse: collapse;
            width: 100%;
        }

        .patient-meta td {
            color: #25364f;
            font-size: 9px;
            padding: 2px 10px 2px 0;
            width: 20%;
        }

        .patient-meta strong {
            color: #415a77;
            display: block;
            font-size: 7px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .section-title {
            color: #001340;
            font-size: 12px;
            font-weight: 800;
            margin: 18px 0 8px;
            text-transform: uppercase;
        }

        .diagnosis-title {
            color: #001340;
            font-size: 9px;
            font-weight: 800;
            margin: 12px 0 6px;
            text-transform: uppercase;
        }

        .exams-table,
        .diagnosis-table {
            border-collapse: collapse;
            width: 100%;
        }

        .diagnosis-table th {
            background: #e7f3f8;
            border: 1px solid #c9dbe7;
            color: #001340;
            font-size: 7px;
            padding: 5px;
            text-align: left;
            text-transform: uppercase;
        }

        .diagnosis-table td {
            background: #ffffff;
            border: 1px solid #d6e4ed;
            font-size: 8px;
            padding: 5px;
            vertical-align: top;
        }

        .exams-table th {
            background: #0f8fb0;
            border: 1px solid #0f8fb0;
            color: #ffffff;
            font-size: 8px;
            padding: 7px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        .exams-table td {
            border: 1px solid #d6e4ed;
            padding: 7px 6px;
            vertical-align: top;
        }

        .exams-table tbody tr:nth-child(even) td {
            background: #f8fbfd;
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
            margin-top: 18px;
            padding-top: 8px;
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
                    <h1 class="title">Examenes de Laboratorio</h1>
                    <p class="subtitle">Clinica Santa Rosa Sullana - Historia Clinica de Emergencia</p>
                </td>
                <td style="width: 110px;"></td>
            </tr>
        </table>
    </header>

    <section class="patient-block">
        <h2 class="patient-name">{{ $texto($nombrePaciente) }}</h2>
        <table class="patient-meta">
            <tr>
                <td><strong>Sexo</strong>{{ $texto($paciente?->sexo) }}</td>
                <td><strong>Edad</strong>{{ $edad }}</td>
                <td><strong>Grupo sanguineo</strong>{{ $texto($paciente?->grupo_sanguineo) }}</td>
                <td><strong>Adm</strong>{{ $texto($atencion->numero_atencion) }}</td>
                <td><strong>Reg HC</strong>{{ $texto($atencion->id) }}</td>
            </tr>
        </table>

        <h3 class="diagnosis-title">Diagnosticos de ingreso</h3>
        <table class="diagnosis-table">
            <thead>
                <tr>
                    <th style="width: 16%;">CIE10</th>
                    <th>Denominacion</th>
                    <th style="width: 18%;">Tipo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($atencion->diagnosticosIngreso as $diagnostico)
                    <tr>
                        <td>{{ $texto($diagnostico->diagnosticoCie10?->codigo) }}</td>
                        <td>{{ $texto($diagnostico->diagnosticoCie10?->nombre) }}</td>
                        <td>{{ $texto($diagnostico->tipo_diagnostico) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="empty">No se registraron diagnosticos de ingreso.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <h2 class="section-title">Listado de examenes de laboratorio</h2>
    <table class="exams-table">
        <thead>
            <tr>
                <th style="width: 18%;">Fecha / Hora</th>
                <th>Examen</th>
                <th style="width: 30%;">Profesional</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($atencion->solicitudesExamenes as $examen)
                <tr>
                    <td>{{ $fecha($examen->fecha_hora_solicitud) }}</td>
                    <td>{{ $texto($examen->examen?->descripcion) }}</td>
                    <td>{{ $profesional($examen->profesionalSalud) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="empty">No se registraron examenes de laboratorio para esta atencion.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer class="footer">
        Generado: {{ $generadoEn->format('d/m/Y H:i') }} | Adm: {{ $texto($atencion->numero_atencion) }}
    </footer>
</body>
</html>
