<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system" data-sidebar="normal">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'HCEApp'))</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|montserrat:600,700,800|poppins:500,600,700&display=swap" rel="stylesheet">

        <script>
            (() => {
                const temaGuardado = localStorage.getItem('hceapp.theme') || 'system';
                const menuGuardado = localStorage.getItem('hceapp.sidebar') || 'normal';
                const usaOscuroSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const temaAplicado = temaGuardado === 'system' ? (usaOscuroSistema ? 'dark' : 'light') : temaGuardado;

                document.documentElement.dataset.theme = temaGuardado;
                document.documentElement.dataset.appliedTheme = temaAplicado;
                document.documentElement.dataset.sidebar = menuGuardado;
                document.documentElement.classList.toggle('theme-dark', temaAplicado === 'dark');
            })();
        </script>

        <link rel="stylesheet" href="{{ asset('vendor/flasher/sweetalert2.min.css') }}">
        <script src="{{ asset('vendor/flasher/sweetalert2.min.js') }}" defer></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="app-body min-h-screen antialiased">
        @php
            $usuarioActual = auth()->user();
            $rolesActuales = $usuarioActual->getRoleNames()->join(', ') ?: 'Sin rol asignado';
            $permiso = \App\Support\Seguridad\Permisos::class;

            $menuSecciones = [
                [
                    'titulo' => 'Principal',
                    'items' => [
                        [
                            'texto' => 'Dashboard',
                            'clave' => 'dashboard',
                            'url' => route('dashboard'),
                            'activo' => request()->routeIs('dashboard'),
                            'icono' => 'dashboard',
                            'permiso' => $permiso::DASHBOARD_VER,
                        ],
                    ],
                ],
                [
                    'titulo' => 'Gestion Clinica',
                    'items' => [
                        [
                            'texto' => 'Historia Clinica',
                            'clave' => 'historia-clinica',
                            'icono' => 'historia-clinica',
                            'subitems' => [
                                ['texto' => 'Atenciones del Paciente', 'url' => '#', 'permiso' => $permiso::HISTORIA_CLINICA_VER],
                                ['texto' => 'Historias abiertas', 'url' => '#', 'permiso' => $permiso::HISTORIA_CLINICA_VER],
                            ],
                        ],
                        [
                            'texto' => 'Pacientes',
                            'clave' => 'pacientes',
                            'icono' => 'pacientes',
                            'url' => route('gestion-clinica.pacientes.index'),
                            'activo' => request()->routeIs('gestion-clinica.pacientes.*'),
                            'permiso' => $permiso::PACIENTES_VER,
                        ],
                        [
                            'texto' => 'Profesionales',
                            'clave' => 'profesionales',
                            'icono' => 'profesionales',
                            'url' => route('gestion-clinica.profesionales.index'),
                            'activo' => request()->routeIs('gestion-clinica.profesionales.*'),
                            'permiso' => $permiso::PROFESIONALES_VER,
                        ],
                    ],
                ],
                [
                    'titulo' => 'Catalogos',
                    'items' => [
                        [
                            'texto' => 'Mantenimiento',
                            'clave' => 'mantenimiento',
                            'icono' => 'mantenimiento',
                            'subitems' => [
                                ['texto' => 'Procedimientos', 'url' => route('catalogos.procedimientos.index'), 'activo' => request()->routeIs('catalogos.procedimientos.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'CIE10', 'url' => route('catalogos.cie10.index'), 'activo' => request()->routeIs('catalogos.cie10.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Medicamentos', 'url' => route('catalogos.medicamentos.index'), 'activo' => request()->routeIs('catalogos.medicamentos.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Examenes', 'url' => route('catalogos.examenes.index'), 'activo' => request()->routeIs('catalogos.examenes.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Especialidades', 'url' => route('catalogos.especialidades.index'), 'activo' => request()->routeIs('catalogos.especialidades.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Signos Vitales', 'url' => route('catalogos.signos-vitales.index'), 'activo' => request()->routeIs('catalogos.signos-vitales.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Alergias', 'url' => route('catalogos.alergias.index'), 'activo' => request()->routeIs('catalogos.alergias.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Tipo de Ordenes', 'url' => route('catalogos.tipo-ordenes.index'), 'activo' => request()->routeIs('catalogos.tipo-ordenes.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Prioridades', 'url' => route('catalogos.prioridades.index'), 'activo' => request()->routeIs('catalogos.prioridades.*'), 'permiso' => $permiso::CATALOGOS_VER],
                                ['texto' => 'Ambientes', 'url' => route('catalogos.ambientes.index'), 'activo' => request()->routeIs('catalogos.ambientes.*'), 'permiso' => $permiso::CATALOGOS_VER],
                            ],
                        ],
                    ],
                ],
                [
                    'titulo' => 'Reportes y Control',
                    'items' => [
                        [
                            'texto' => 'Reportes',
                            'clave' => 'reportes',
                            'icono' => 'reportes',
                            'subitems' => [
                                ['texto' => 'Atenciones por Prioridad', 'url' => '#', 'permiso' => $permiso::REPORTES_VER],
                                ['texto' => 'Resumen Diario', 'url' => '#', 'permiso' => $permiso::REPORTES_VER],
                                ['texto' => 'Indicadores Clinicos', 'url' => '#', 'permiso' => $permiso::REPORTES_VER],
                            ],
                        ],
                        [
                            'texto' => 'Auditoria',
                            'clave' => 'auditoria',
                            'icono' => 'auditoria',
                            'subitems' => [
                                ['texto' => 'Trazabilidad', 'url' => '#', 'permiso' => $permiso::AUDITORIA_VER],
                                ['texto' => 'Accesos al Sistema', 'url' => '#', 'permiso' => $permiso::AUDITORIA_VER],
                            ],
                        ],
                    ],
                ],
                [
                    'titulo' => 'Configuraciones',
                    'items' => [
                        [
                            'texto' => 'Seguridad',
                            'clave' => 'seguridad',
                            'icono' => 'seguridad',
                            'subitems' => [
                                ['texto' => 'Usuarios', 'url' => route('seguridad.usuarios.index'), 'activo' => request()->routeIs('seguridad.usuarios.*'), 'permiso' => $permiso::USUARIOS_VER],
                                ['texto' => 'Roles y Permisos', 'url' => route('seguridad.roles.index'), 'activo' => request()->routeIs('seguridad.roles.*'), 'permiso' => $permiso::ROLES_VER],
                            ],
                        ],
                        [
                            'texto' => 'Sistema',
                            'clave' => 'sistema',
                            'icono' => 'sistema',
                            'subitems' => [
                                ['texto' => 'Identidad del login', 'url' => route('configuracion.login.editar'), 'activo' => request()->routeIs('configuracion.login.*'), 'permiso' => $permiso::CONFIGURACION_VER],
                                ['texto' => 'Parametros Generales', 'url' => '#', 'permiso' => $permiso::CONFIGURACION_VER],
                            ],
                        ],
                    ],
                ],
            ];

            $menuSecciones = collect($menuSecciones)
                ->map(function (array $seccion) use ($usuarioActual): array {
                    $seccion['items'] = collect($seccion['items'])
                        ->map(function (array $item) use ($usuarioActual): ?array {
                            if (filled($item['subitems'] ?? [])) {
                                $item['subitems'] = collect($item['subitems'])
                                    ->filter(fn (array $subitem): bool => empty($subitem['permiso'] ?? null) || $usuarioActual->can($subitem['permiso']))
                                    ->values()
                                    ->all();

                                return filled($item['subitems']) ? $item : null;
                            }

                            return empty($item['permiso'] ?? null) || $usuarioActual->can($item['permiso']) ? $item : null;
                        })
                        ->filter()
                        ->values()
                        ->all();

                    return $seccion;
                })
                ->filter(fn (array $seccion): bool => filled($seccion['items']))
                ->values()
                ->all();
        @endphp

        <div class="app-shell min-h-screen" data-app-shell>
            <aside class="app-sidebar" data-sidebar-panel aria-label="Menu principal">
                <div class="app-sidebar-brand">
                    <a href="{{ route('dashboard') }}" class="app-brand-link" aria-label="Ir al panel principal">
                        <span class="app-brand-mark">
                            <iconify-icon icon="material-symbols:bolt-rounded" aria-hidden="true"></iconify-icon>
                        </span>
                        <span class="app-brand-text">{{ config('app.name', 'HCEApp') }}</span>
                    </a>

                    <button type="button" class="app-sidebar-toggle" data-sidebar-toggle aria-label="Cambiar modo del menu">
                        <iconify-icon icon="lucide:chevron-left" aria-hidden="true"></iconify-icon>
                    </button>
                </div>

                <nav class="app-sidebar-nav">
                    @foreach ($menuSecciones as $seccion)
                        <div class="app-sidebar-section">
                            <p class="app-sidebar-heading">{{ $seccion['titulo'] }}</p>

                            @foreach ($seccion['items'] as $item)
                                @php
                                    $tieneSubitems = filled($item['subitems'] ?? []);
                                    $itemActivo = $item['activo'] ?? collect($item['subitems'] ?? [])->contains(fn ($subitem) => $subitem['activo'] ?? false);
                                    $claveMenu = $item['clave'] ?? str($item['texto'])->slug()->toString();
                                @endphp

                                @if ($tieneSubitems)
                                    <div class="app-sidebar-group {{ $itemActivo ? 'is-open' : '' }}" data-menu-group="{{ $claveMenu }}">
                                        <button type="button" class="app-sidebar-link app-sidebar-parent {{ $itemActivo ? 'is-active' : '' }}" data-menu-toggle="{{ $claveMenu }}" aria-expanded="{{ $itemActivo ? 'true' : 'false' }}">
                                            <span class="app-sidebar-icon">
                                                @include('layouts.partials.sidebar-icon', ['icono' => $item['icono']])
                                            </span>
                                            <span class="app-sidebar-label">{{ $item['texto'] }}</span>
                                            <span class="app-sidebar-caret">
                                                <iconify-icon icon="lucide:chevron-right" aria-hidden="true"></iconify-icon>
                                            </span>
                                        </button>

                                        <div class="app-sidebar-submenu" data-menu-submenu="{{ $claveMenu }}" @if (! $itemActivo) hidden @endif>
                                            @foreach ($item['subitems'] as $subitem)
                                                <a href="{{ $subitem['url'] }}" class="{{ ($subitem['activo'] ?? false) ? 'is-active' : '' }}" data-menu-subitem="{{ $claveMenu }}:{{ str($subitem['texto'])->slug()->toString() }}" data-menu-parent="{{ $claveMenu }}">
                                                    {{ $subitem['texto'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ $item['url'] }}" class="app-sidebar-link {{ $itemActivo ? 'is-active' : '' }}" data-menu-leaf="{{ $claveMenu }}">
                                        <span class="app-sidebar-icon">
                                            @include('layouts.partials.sidebar-icon', ['icono' => $item['icono']])
                                        </span>
                                        <span class="app-sidebar-label">{{ $item['texto'] }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </nav>
            </aside>

            <button type="button" class="app-sidebar-backdrop" data-mobile-sidebar-backdrop aria-label="Cerrar menu principal" hidden></button>

            <div class="app-main">
                <header class="app-topbar">
                    <div class="app-topbar-user">
                        <button type="button" class="app-mobile-menu-button" data-mobile-sidebar-open aria-label="Abrir menu principal" aria-expanded="false">
                            <iconify-icon icon="lucide:menu" aria-hidden="true"></iconify-icon>
                        </button>
                        <span class="app-topbar-icon">
                            <iconify-icon icon="lucide:user-round" aria-hidden="true"></iconify-icon>
                        </span>
                        <span class="min-w-0">
                            <span class="app-topbar-title">Bienvenido, {{ $usuarioActual->name }}</span>
                            <span class="app-topbar-subtitle">
                                <iconify-icon icon="lucide:shield-check" aria-hidden="true"></iconify-icon>
                                Rol: {{ $rolesActuales }}
                            </span>
                        </span>
                    </div>

                    <div class="app-topbar-actions">
                        <div class="app-dropdown" data-dropdown>
                            <button type="button" class="app-header-button app-theme-trigger" data-dropdown-toggle aria-expanded="false" title="Cambiar tema" aria-label="Cambiar tema">
                                <span class="app-header-button-icon" data-theme-current-icon="light">
                                    <iconify-icon icon="lucide:sun" aria-hidden="true"></iconify-icon>
                                </span>
                                <span class="app-header-button-icon" data-theme-current-icon="dark" hidden>
                                    <iconify-icon icon="lucide:moon" aria-hidden="true"></iconify-icon>
                                </span>
                                <span class="app-header-button-icon" data-theme-current-icon="system" hidden>
                                    <iconify-icon icon="lucide:monitor" aria-hidden="true"></iconify-icon>
                                </span>
                            </button>

                            <div class="app-dropdown-menu app-theme-menu" data-dropdown-menu hidden>
                                <button type="button" data-theme-option="light" aria-label="Tema claro" title="Tema claro">
                                    <iconify-icon icon="lucide:sun" aria-hidden="true"></iconify-icon>
                                </button>
                                <button type="button" data-theme-option="dark" aria-label="Tema oscuro" title="Tema oscuro">
                                    <iconify-icon icon="lucide:moon" aria-hidden="true"></iconify-icon>
                                </button>
                                <button type="button" data-theme-option="system" aria-label="Tema sistema" title="Tema sistema">
                                    <iconify-icon icon="lucide:monitor" aria-hidden="true"></iconify-icon>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="app-icon-button" aria-label="Aplicaciones">
                            <iconify-icon icon="lucide:grid-2x2" aria-hidden="true"></iconify-icon>
                        </button>

                        <button type="button" class="app-icon-button app-notification-button" aria-label="Notificaciones">
                            <iconify-icon icon="lucide:bell" aria-hidden="true"></iconify-icon>
                            <span></span>
                        </button>

                        <div class="app-dropdown" data-dropdown>
                            <button type="button" class="app-avatar-button" data-dropdown-toggle title="Opciones de usuario" aria-label="Opciones de usuario" aria-expanded="false">
                                {{ strtoupper(substr($usuarioActual->name, 0, 1)) }}
                            </button>

                            <div class="app-dropdown-menu app-user-menu" data-dropdown-menu hidden>
                                <div class="app-user-summary">
                                    <strong>{{ $usuarioActual->name }}</strong>
                                    <span>{{ $usuarioActual->email }}</span>
                                </div>
                                @can(\App\Support\Seguridad\Permisos::USUARIOS_VER)
                                    <a href="{{ route('seguridad.usuarios.index') }}">Configuracion del usuario</a>
                                @endcan
                                <a href="#">Preferencias personales</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit">Cerrar sesion</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="app-content">
                    @yield('content')
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
