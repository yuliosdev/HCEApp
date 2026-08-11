@extends('layouts.app')

@section('title', 'Test de cards neon')

@section('content')
    @php
        $tarjetas = [
            [
                'titulo' => 'Usuarios Totales',
                'valor' => '24,750',
                'detalle' => '12.5% vs mes anterior',
                'icono' => 'lucide:users',
                'tono' => 'surf',
            ],
            [
                'titulo' => 'Atenciones Abiertas',
                'valor' => '1,284',
                'detalle' => '8.2% flujo actual',
                'icono' => 'lucide:briefcase-medical',
                'tono' => 'teal',
            ],
            [
                'titulo' => 'Prioridad Alta',
                'valor' => '86',
                'detalle' => '4.1% en seguimiento',
                'icono' => 'lucide:activity',
                'tono' => 'navy',
            ],
            [
                'titulo' => 'Historias Cerradas',
                'valor' => '942',
                'detalle' => '15.8% resueltas',
                'icono' => 'lucide:shield-check',
                'tono' => 'powder',
            ],
        ];
    @endphp

    <section class="card-style-test-page">
        <div class="card-style-test-heading panel-animate">
            <div>
                <p>Vista test</p>
                <h1>Estilo propuesto para cards metricas</h1>
                <span>Prueba visual compatible con modo claro, oscuro y sistema.</span>
            </div>
        </div>

        <div class="card-style-test-grid">
            @foreach ($tarjetas as $indice => $tarjeta)
                <article class="card-style-test-card card-tone-{{ $tarjeta['tono'] }} panel-animate" style="--panel-delay: {{ $indice * 90 }}ms">
                    <span class="card-style-test-icon">
                        <iconify-icon icon="{{ $tarjeta['icono'] }}" aria-hidden="true"></iconify-icon>
                    </span>

                    <div>
                        <p>{{ $tarjeta['titulo'] }}</p>
                        <strong data-count-target="{{ str_replace(',', '', $tarjeta['valor']) }}">{{ $tarjeta['valor'] }}</strong>
                        <small>
                            <iconify-icon icon="lucide:arrow-up" aria-hidden="true"></iconify-icon>
                            {{ $tarjeta['detalle'] }}
                        </small>
                    </div>
                </article>
            @endforeach
        </div>

        <section class="card-style-test-preview panel-animate" style="--panel-delay: 420ms">
            <div>
                <p>Aplicacion recomendada</p>
                <h2>Usar este estilo solo en cards de indicadores</h2>
                <span>El borde luminoso y el fondo tipo glass deben resaltar metricas, no contenedores de tablas o formularios.</span>
            </div>
        </section>
    </section>
@endsection
