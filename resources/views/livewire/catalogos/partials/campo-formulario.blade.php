<label class="{{ $campo['clase'] ?? '' }}">
    <span>{{ $campo['etiqueta'] }}</span>

    @if (($campo['tipo'] ?? 'text') === 'textarea')
        <textarea wire:model.blur="form.{{ $campo['nombre'] }}" rows="4" @readonly($modoFormulario === 'ver')></textarea>
    @elseif (($campo['tipo'] ?? 'text') === 'select')
        <select wire:model="form.{{ $campo['nombre'] }}" @disabled($modoFormulario === 'ver')>
            @if (! ($campo['requerido'] ?? false))
                <option value="">Sin definir</option>
            @endif
            @foreach ($campo['opciones'] as $opcion)
                <option value="{{ $opcion }}">{{ $opcion }}</option>
            @endforeach
        </select>
    @elseif (($campo['tipo'] ?? 'text') === 'number')
        <input
            type="number"
            wire:model.blur="form.{{ $campo['nombre'] }}"
            min="{{ $campo['min'] ?? 0 }}"
            @if (isset($campo['max_numero'])) max="{{ $campo['max_numero'] }}" @endif
            @readonly($modoFormulario === 'ver')
        >
    @elseif (($campo['tipo'] ?? 'text') === 'decimal')
        <input
            type="number"
            wire:model.blur="form.{{ $campo['nombre'] }}"
            min="{{ $campo['min'] ?? 0 }}"
            step="{{ $campo['step'] ?? '0.01' }}"
            inputmode="decimal"
            @readonly($modoFormulario === 'ver')
        >
    @elseif (($campo['tipo'] ?? 'text') === 'boolean')
        <select wire:model="form.{{ $campo['nombre'] }}" @disabled($modoFormulario === 'ver')>
            <option value="1">Si</option>
            <option value="0">No</option>
        </select>
    @else
        <input type="text" wire:model.blur="form.{{ $campo['nombre'] }}" @readonly($modoFormulario === 'ver')>
    @endif

    @error('form.'.$campo['nombre']) <small>{{ $message }}</small> @enderror
</label>
