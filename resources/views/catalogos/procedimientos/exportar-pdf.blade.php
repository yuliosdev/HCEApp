<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Catalogo de procedimientos</title>
    <style>
        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        h1 {
            color: #03045e;
            font-size: 22px;
            margin: 0 0 6px;
        }

        .meta {
            color: #415a77;
            margin-bottom: 18px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #0077b6;
            color: #ffffff;
            font-size: 10px;
            padding: 7px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #d7e3ec;
            padding: 7px;
            vertical-align: top;
        }

        .estado {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Catalogo de procedimientos</h1>
    <div class="meta">
        Generado por: {{ $generadoPor->name }} | Fecha: {{ $generadoEn->format('d/m/Y H:i:s') }}<br>
        Filtros aplicados: busqueda {{ $filtros['busqueda'] }}, estado {{ $filtros['estado'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Denominacion</th>
                <th>Estado</th>
                <th>Registrado por</th>
                <th>Creado</th>
                <th>Actualizado por</th>
                <th>Actualizado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($procedimientos as $procedimiento)
                <tr>
                    <td>{{ $procedimiento->codigo }}</td>
                    <td>{{ $procedimiento->denominacion }}</td>
                    <td class="estado">{{ $procedimiento->estado }}</td>
                    <td>{{ optional($procedimiento->registradoPor)->name ?? 'No registrado' }}</td>
                    <td>{{ optional($procedimiento->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ optional($procedimiento->actualizadoPor)->name ?? 'No registrado' }}</td>
                    <td>{{ optional($procedimiento->updated_at)->format('d/m/Y H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No existen procedimientos para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
