<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Catalogo de {{ $configuracion['titulo'] }}</title>
    <style>
        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
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
            font-size: 9px;
            padding: 6px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #d7e3ec;
            padding: 6px;
            vertical-align: top;
        }

        .estado {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Catalogo de {{ $configuracion['titulo'] }}</h1>
    <div class="meta">
        Generado por: {{ $generadoPor->name }} | Fecha: {{ $generadoEn->format('d/m/Y H:i:s') }}<br>
        Filtros aplicados: busqueda {{ $filtros['busqueda'] }}, estado {{ $filtros['estado'] }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($configuracion['campos'] as $campo)
                    <th>{{ $campo['etiqueta'] }}</th>
                @endforeach
                <th>Estado</th>
                <th>Registrado por</th>
                <th>Creado</th>
                <th>Actualizado por</th>
                <th>Actualizado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $registro)
                <tr>
                    @foreach ($configuracion['campos'] as $campo)
                        @php($valor = data_get($registro, $campo['nombre']))
                        <td>{{ $valor instanceof \BackedEnum ? $valor->value : (filled($valor) ? $valor : '-') }}</td>
                    @endforeach
                    @php($estado = data_get($registro, 'estado'))
                    <td class="estado">{{ $estado instanceof \BackedEnum ? $estado->value : (filled($estado) ? $estado : '-') }}</td>
                    <td>{{ optional($registro->registradoPor)->name ?? 'No registrado' }}</td>
                    <td>{{ optional($registro->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ optional($registro->actualizadoPor)->name ?? 'No registrado' }}</td>
                    <td>{{ optional($registro->updated_at)->format('d/m/Y H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($configuracion['campos']) + 5 }}">No existen registros para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
