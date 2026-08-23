<section class="patient-search-page">
    <div class="patient-search-heading panel-animate">
        <div>
            <p>Historia clinica</p>
            <h1>Busqueda de Pacientes</h1>
            <span>Localice registros medicos rapidamente utilizando nombre, documento o numero de historia.</span>
        </div>
    </div>

    <form class="patient-search-box panel-animate" wire:submit.prevent="buscar">
        <div class="patient-search-fields">
            <span>Nombres y Apellidos</span>
            <span>DNI / Documento de Identidad</span>
        </div>

        <div class="patient-search-input">
            <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
            <input type="search" wire:model="busqueda" placeholder="Buscar por nombres, DNI o historia clinica">

            @if ($busqueda !== '' || $busquedaAplicada !== '')
                <button type="button" wire:click="limpiar" aria-label="Limpiar busqueda">
                    <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                </button>
            @endif
        </div>

        <div class="patient-search-actions">
            <button type="submit" class="catalog-primary-action patient-search-button">
                <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                Buscar paciente
            </button>
        </div>
    </form>

    <div class="patient-results-heading panel-animate" style="--panel-delay: 120ms">
        <h2>Resultados de Busqueda</h2>
        <span>{{ $pacientes->count() }} encontrados</span>
    </div>

    <div class="patient-card-grid">
        @forelse ($pacientes as $indice => $paciente)
            @php
                $sexo = $paciente->sexo ?: 'No definido';
                $sexoNormalizado = str($sexo)->lower()->toString();
                $sexoClase = str_contains($sexoNormalizado, 'femenino') ? 'is-female' : (str_contains($sexoNormalizado, 'masculino') ? 'is-male' : 'is-unknown');
                $sexoIcono = $sexoClase === 'is-female' ? 'lucide:venus' : ($sexoClase === 'is-male' ? 'lucide:mars' : 'lucide:circle-help');
                $ultimaAtencion = $paciente->ultima_atencion_fecha ? \Illuminate\Support\Carbon::parse($paciente->ultima_atencion_fecha)->format('d/m/Y') : 'Sin atencion';
            @endphp

            <article class="patient-result-card hce-card-motion {{ $sexoClase }} panel-animate" style="--panel-delay: {{ 180 + ($indice * 80) }}ms">
                <span class="patient-card-icon">
                    <iconify-icon icon="lucide:user-round" aria-hidden="true"></iconify-icon>
                </span>

                <span class="patient-sex-badge {{ $sexoClase }}">
                    <iconify-icon icon="{{ $sexoIcono }}" aria-hidden="true"></iconify-icon>
                    {{ $sexo }}
                </span>

                <div class="patient-card-main">
                    <h3>{{ $paciente->nombre }}</h3>
                    <p>{{ $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age.' anos' : 'Edad no registrada' }}</p>
                </div>

                <dl class="patient-card-data">
                    <div>
                        <dt>{{ $paciente->tipo_documento ?: 'Documento' }}:</dt>
                        <dd>{{ $paciente->numero_documento ?: 'No registrado' }}</dd>
                    </div>
                    <div>
                        <dt>Ultima visita:</dt>
                        <dd>{{ $ultimaAtencion }}</dd>
                    </div>
                </dl>

                <button type="button" class="patient-history-button" wire:click="prepararHistorial({{ $paciente->id }})">
                    <iconify-icon icon="lucide:eye" aria-hidden="true"></iconify-icon>
                    Ver historial
                </button>
            </article>
        @empty
            <article class="patient-empty-card hce-card-motion panel-animate" style="--panel-delay: 180ms">
                <iconify-icon icon="lucide:search" aria-hidden="true"></iconify-icon>
                <p>
                    @if ($busquedaAplicada === '')
                        Utilice la busqueda superior para encontrar pacientes.
                    @else
                        No se encontraron pacientes con la busqueda ingresada.
                    @endif
                </p>
            </article>
        @endforelse
    </div>
</section>
