<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de Atenciones</title>
    <style>
        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.35;
        }

        h1 {
            color: #03045e;
            font-size: 20px;
            margin: 0 0 6px;
        }

        .meta {
            color: #415a77;
            margin-bottom: 12px;
        }

        .stats {
            border-collapse: collapse;
            margin-bottom: 12px;
            width: 100%;
        }

        .stats td {
            background: #f3f8fb;
            border: 1px solid #d7e3ec;
            color: #03045e;
            font-weight: bold;
            padding: 8px;
            text-align: center;
        }

        .stats span {
            color: #415a77;
            display: block;
            font-size: 7px;
            text-transform: uppercase;
        }

        table.data {
            border-collapse: collapse;
            width: 100%;
        }

        table.data th {
            background: #0077b6;
            border: 1px solid #0077b6;
            color: #ffffff;
            font-size: 7px;
            padding: 5px;
            text-align: left;
            text-transform: uppercase;
        }

        table.data td {
            border: 1px solid #d7e3ec;
            padding: 5px;
            vertical-align: top;
        }

        .muted {
            color: #64748b;
        }

        .status {
            border-radius: 10px;
            display: inline-block;
            font-size: 7px;
            font-weight: bold;
            padding: 2px 7px;
        }

        .status.open {
            background: #dcfce7;
            color: #217a5d;
        }

        .status.closed {
            background: #fee2e2;
            color: #b91c1c;
        }
    </style>
</head>
<body>
    @php
        $valor = fn ($dato): string => $dato instanceof \BackedEnum ? $dato->value : (filled($dato) ? (string) $dato : '--');
    @endphp

    <h1>Historial de Atenciones</h1>
    <div class="meta">
        Fecha desde: {{ $filtros['desde'] }} |
        Fecha hasta: {{ $filtros['hasta'] }} |
        Estado: {{ $filtros['estado'] }} |
        Generado por: {{ $generadoPor?->name ?? '--' }} |
        Fecha: {{ $generadoEn->format('d/m/Y H:i:s') }}
    </div>

    <table class="stats">
        <tr>
            <td><span>Total</span>{{ $estadisticas['total'] }}</td>
            <td><span>Abiertas</span>{{ $estadisticas['abiertas'] }}</td>
            <td><span>Cerradas</span>{{ $estadisticas['cerradas'] }}</td>
            <td><span>Con triaje</span>{{ $estadisticas['triaje'] }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Fecha / Hora</th>
                <th>Paciente</th>
                <th>Tipo</th>
                <th>N° Atencion</th>
                <th>Profesional</th>
                <th>Ambiente</th>
                <th>DX Principal</th>
                <th>Triaje</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($atenciones as $atencion)
                @php($diagnostico = $archivos->diagnosticoPrincipal($atencion))
                @php($abierta = $valor($atencion->estado_atencion) === \App\Enums\EstadoAtencion::Abierta->value)
                <tr>
                    <td>{{ $atencion->fecha_hora_atencion?->format('d/m/Y H:i') ?? '--' }}</td>
                    <td>{{ $atencion->paciente?->nombre ?? $atencion->alias ?? '--' }}</td>
                    <td>Emergencia</td>
                    <td>
                        <strong>{{ $atencion->id }}</strong><br>
                        <span class="muted">Adm: {{ $atencion->numero_atencion ?: '--' }}</span>
                    </td>
                    <td>{{ $valor($atencion->profesionalResponsable?->nombres) }}</td>
                    <td>{{ $valor($atencion->destino_topico ?: $atencion->ambiente?->denominacion) }}</td>
                    <td>
                        @if ($diagnostico?->diagnosticoCie10)
                            {{ $diagnostico->diagnosticoCie10->nombre }} ({{ $diagnostico->diagnosticoCie10->codigo }})
                        @else
                            <span class="muted">En evaluacion...</span>
                        @endif
                    </td>
                    <td>{{ $atencion->prioridad?->orden ? 'Nivel '.$atencion->prioridad->orden : 'Nivel --' }} | {{ $atencion->triaje ? 'Realizado' : 'Pendiente' }}</td>
                    <td><span class="status {{ $abierta ? 'open' : 'closed' }}">{{ $abierta ? 'Abierta' : 'Cerrada' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No se encontraron historias clinicas para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
