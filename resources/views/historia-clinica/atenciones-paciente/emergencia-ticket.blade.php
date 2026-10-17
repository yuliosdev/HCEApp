<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket Emergencia {{ $atencion->id }}</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #ffffff;
            color: #000000;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            margin: 0;
            width: 80mm;
        }

        .ticket {
            min-height: 148mm;
            width: 80mm;
        }

        .ticket-header {
            background: #000000;
            color: #ffffff;
            padding: 8px 10px 11px;
            text-align: center;
        }

        .clinic {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        .dash {
            border-top: 1px dashed currentColor;
            height: 1px;
            margin: 8px 0;
        }

        .emergency {
            font-size: 29px;
            font-weight: 400;
            letter-spacing: 1px;
            margin: 0;
        }

        .ticket-body {
            padding: 24px 16px 18px;
        }

        .field {
            font-size: 13px;
            margin: 0 0 10px;
        }

        .field strong {
            font-weight: 700;
        }

        .attention {
            font-size: 13px;
            font-weight: 700;
            padding: 9px 0;
            text-align: center;
        }

        .attention span {
            font-weight: 400;
        }

        .message {
            font-size: 13px;
            font-style: italic;
            font-weight: 700;
            line-height: 1.55;
            margin: 20px 7px 0;
        }
    </style>
</head>
<body>
    @php
        $fechaHora = $atencion->fecha_hora_atencion?->format('d/m/Y h:i:sA') ?? '--';
        $paciente = $atencion->paciente?->nombre ?: 'Sin Registro';
        $alias = $atencion->alias ?: '--';
        $tipo = $atencion->tipo ?: '--';
        $medico = $atencion->profesionalResponsable?->nombres ?: '--';
    @endphp

    <main class="ticket">
        <header class="ticket-header">
            <p class="clinic">Clinica Santa Rosa</p>
            <div class="dash"></div>
            <h1 class="emergency">EMERGENCIA</h1>
            <div class="dash"></div>
        </header>

        <section class="ticket-body">
            <p class="field"><strong>Fecha / Hora:</strong> {{ $fechaHora }}</p>
            <p class="field"><strong>Paciente:</strong> {{ $paciente === 'Sin Registro' ? '(Sin Registro)' : $paciente }}</p>
            <p class="field"><strong>Alias:</strong> {{ $alias }}</p>
            <p class="field"><strong>Tipo de Emergencia:</strong> {{ $tipo }}</p>
            <p class="field"><strong>Médico Tratante:</strong> {{ $medico }}</p>

            <div class="dash"></div>
            <p class="attention">N° Atención Emergencia: <span>{{ $atencion->id }}</span>.</p>
            <div class="dash"></div>

            <p class="message">
                Presentar este Ticket en Admisión o Farmacia para el registro del paciente y el pago de la Atención.
            </p>
        </section>
    </main>
</body>
</html>
