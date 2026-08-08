<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system" data-sidebar="normal">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'HCEApp'))</title>

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

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="app-body min-h-screen antialiased">
        @php
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
                        ],
                    ],
                ],
                [
                    'titulo' => 'Gestion clinica',
                    'items' => [
                        [
                            'texto' => 'Emergencias',
                            'clave' => 'emergencias',
                            'icono' => 'emergencias',
                            'subitems' => [
                                ['texto' => 'Bandeja de emergencia', 'url' => route('dashboard')],
                                ['texto' => 'Nueva atencion', 'url' => '#'],
                                ['texto' => 'Seguimiento clinico', 'url' => '#'],
                            ],
                        ],
                        [
                            'texto' => 'Pacientes',
                            'clave' => 'pacientes',
                            'icono' => 'pacientes',
                            'subitems' => [
                                ['texto' => 'Listado de pacientes', 'url' => '#'],
                                ['texto' => 'Registro rapido', 'url' => '#'],
                                ['texto' => 'Busqueda avanzada', 'url' => '#'],
                            ],
                        ],
                        [
                            'texto' => 'Historias clinicas',
                            'clave' => 'historias',
                            'icono' => 'historias',
                            'subitems' => [
                                ['texto' => 'Historias abiertas', 'url' => '#'],
                                ['texto' => 'Historias cerradas', 'url' => '#'],
                                ['texto' => 'Medicamentos habituales', 'url' => '#'],
                            ],
                        ],
                        [
                            'texto' => 'Turnos y triaje',
                            'clave' => 'triaje',
                            'icono' => 'calendario',
                            'subitems' => [
                                ['texto' => 'Cola de triaje', 'url' => '#'],
                                ['texto' => 'Asignacion de turnos', 'url' => '#'],
                                ['texto' => 'Ambientes disponibles', 'url' => '#'],
                            ],
                        ],
                    ],
                ],
                [
                    'titulo' => 'Catalogos',
                    'items' => [
                        [
                            'texto' => 'Mantenimiento',
                            'clave' => 'mantenimiento',
                            'icono' => 'catalogos',
                            'subitems' => [
                                ['texto' => 'Prioridades', 'url' => '#'],
                                ['texto' => 'Profesionales', 'url' => '#'],
                                ['texto' => 'Especialidades', 'url' => '#'],
                                ['texto' => 'Condiciones de alta', 'url' => '#'],
                            ],
                        ],
                    ],
                ],
                [
                    'titulo' => 'Reportes y control',
                    'items' => [
                        [
                            'texto' => 'Reportes',
                            'clave' => 'reportes',
                            'icono' => 'reportes',
                            'subitems' => [
                                ['texto' => 'Resumen diario', 'url' => '#'],
                                ['texto' => 'Atenciones por prioridad', 'url' => '#'],
                                ['texto' => 'Indicadores clinicos', 'url' => '#'],
                            ],
                        ],
                        [
                            'texto' => 'Auditoria',
                            'clave' => 'auditoria',
                            'icono' => 'auditoria',
                            'subitems' => [
                                ['texto' => 'Trazabilidad', 'url' => '#'],
                                ['texto' => 'Accesos al sistema', 'url' => '#'],
                                ['texto' => 'Cambios sensibles', 'url' => '#'],
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
                                ['texto' => 'Usuarios', 'url' => '#'],
                                ['texto' => 'Roles y permisos', 'url' => '#'],
                                ['texto' => 'Politicas de acceso', 'url' => '#'],
                            ],
                        ],
                        [
                            'texto' => 'Sistema',
                            'clave' => 'sistema',
                            'icono' => 'sistema',
                            'subitems' => [
                                ['texto' => 'Identidad del login', 'url' => route('configuracion.login.editar'), 'activo' => request()->routeIs('configuracion.login.*')],
                                ['texto' => 'Preferencias visuales', 'url' => '#'],
                                ['texto' => 'Parametros generales', 'url' => '#'],
                            ],
                        ],
                    ],
                ],
            ];

            if (app()->environment(['local', 'testing'])) {
                $menuSecciones[] = [
                    'titulo' => 'Laboratorio',
                    'items' => [
                        [
                            'texto' => 'Test tecnologias',
                            'url' => route('test.tecnologias'),
                            'activo' => request()->routeIs('test.tecnologias'),
                            'icono' => 'test',
                        ],
                    ],
                ];
            }
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
                            <span class="app-topbar-title">Bienvenido, {{ auth()->user()->name }}</span>
                            <span class="app-topbar-subtitle">
                                <iconify-icon icon="lucide:shield-check" aria-hidden="true"></iconify-icon>
                                Rol: Administrador
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
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </button>

                            <div class="app-dropdown-menu app-user-menu" data-dropdown-menu hidden>
                                <div class="app-user-summary">
                                    <strong>{{ auth()->user()->name }}</strong>
                                    <span>{{ auth()->user()->email }}</span>
                                </div>
                                <a href="#">Configuracion del usuario</a>
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
