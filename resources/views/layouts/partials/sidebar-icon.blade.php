@php
    $iconos = [
        'dashboard' => 'lucide:house',
        'historia-clinica' => 'lucide:clipboard-list',
        'pacientes' => 'lucide:users',
        'profesionales' => 'lucide:stethoscope',
        'mantenimiento' => 'lucide:wrench',
        'reportes' => 'lucide:chart-column',
        'auditoria' => 'lucide:shield-check',
        'seguridad' => 'lucide:lock-keyhole',
        'sistema' => 'lucide:settings',
        'test' => 'lucide:flask-conical',
    ];
@endphp

<iconify-icon icon="{{ $iconos[$icono] ?? 'lucide:menu' }}" aria-hidden="true"></iconify-icon>
