@php
    $iconos = [
        'dashboard' => 'lucide:house',
        'emergencias' => 'lucide:briefcase-medical',
        'pacientes' => 'lucide:users',
        'historias' => 'lucide:file-text',
        'calendario' => 'lucide:calendar-days',
        'catalogos' => 'lucide:grid-2x2',
        'reportes' => 'lucide:chart-column',
        'auditoria' => 'lucide:shield-check',
        'seguridad' => 'lucide:lock-keyhole',
        'sistema' => 'lucide:settings',
        'test' => 'lucide:flask-conical',
    ];
@endphp

<iconify-icon icon="{{ $iconos[$icono] ?? 'lucide:menu' }}" aria-hidden="true"></iconify-icon>
