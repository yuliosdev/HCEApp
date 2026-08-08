import './bootstrap';
import 'preline';
import 'iconify-icon';

const inicializarPreline = () => {
    if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
        window.HSStaticMethods.autoInit();
    }
};

const cerrarOverlayPreline = (evento) => {
    const detalle = Array.isArray(evento) ? evento[0] : evento;
    const id = detalle?.id;
    const elemento = id ? document.getElementById(id) : null;

    if (elemento && window.HSOverlay && typeof window.HSOverlay.close === 'function') {
        window.HSOverlay.close(elemento);
    }
};

const prepararTransicionSalidaLogin = () => {
    document.querySelectorAll('[data-login-transition-form]').forEach((formulario) => {
        if (formulario.dataset.loginTransitionReady === 'true') {
            return;
        }

        formulario.dataset.loginTransitionReady = 'true';

        formulario.addEventListener('submit', (evento) => {
            if (formulario.dataset.loginSubmitting === 'true') {
                return;
            }

            evento.preventDefault();
            formulario.dataset.loginSubmitting = 'true';

            const contenedor = document.querySelector('[data-login-shell]');
            const panel = document.querySelector('[data-login-panel]');
            const boton = formulario.querySelector('[type="submit"]');

            contenedor?.classList.add('login-shell-salir');
            panel?.classList.add('login-panel-salir');

            if (boton) {
                boton.setAttribute('disabled', 'disabled');
                boton.textContent = boton.dataset.loadingText || 'Validando acceso...';
            }

            window.setTimeout(() => HTMLFormElement.prototype.submit.call(formulario), 460);
        });
    });
};

const claveTema = 'hceapp.theme';
const claveMenu = 'hceapp.sidebar';
const claveGruposMenu = 'hceapp.sidebar.groups';
const claveSeleccionMenu = 'hceapp.sidebar.selection';
const consultaTemaSistema = window.matchMedia('(prefers-color-scheme: dark)');
const consultaMenuMovil = window.matchMedia('(max-width: 900px)');

const obtenerTemaAplicado = (tema) => {
    if (tema === 'system') {
        return consultaTemaSistema.matches ? 'dark' : 'light';
    }

    return tema;
};

const aplicarTema = (tema) => {
    const temaNormalizado = ['light', 'dark', 'system'].includes(tema) ? tema : 'system';
    const temaAplicado = obtenerTemaAplicado(temaNormalizado);

    document.documentElement.dataset.theme = temaNormalizado;
    document.documentElement.dataset.appliedTheme = temaAplicado;
    document.documentElement.classList.toggle('theme-dark', temaAplicado === 'dark');

    document.querySelectorAll('[data-theme-option]').forEach((boton) => {
        boton.classList.toggle('is-active', boton.dataset.themeOption === temaNormalizado);
    });

    document.querySelectorAll('[data-theme-current-icon]').forEach((icono) => {
        icono.hidden = icono.dataset.themeCurrentIcon !== temaNormalizado;
    });
};

const aplicarModoMenu = (modo) => {
    const modoNormalizado = modo === 'collapsed' ? 'collapsed' : 'normal';

    document.documentElement.dataset.sidebar = modoNormalizado;
};

const cerrarMenuMovil = () => {
    const panel = document.querySelector('[data-sidebar-panel]');

    document.documentElement.dataset.mobileSidebar = 'closed';
    document.body.classList.remove('app-mobile-sidebar-open');
    panel?.classList.remove('is-mobile-open');
    document.querySelector('[data-mobile-sidebar-backdrop]')?.setAttribute('hidden', 'hidden');

    if (consultaMenuMovil.matches) {
        panel?.setAttribute('aria-hidden', 'true');
    } else {
        panel?.removeAttribute('aria-hidden');
    }

    document.querySelectorAll('[data-mobile-sidebar-open]').forEach((boton) => {
        boton.setAttribute('aria-expanded', 'false');
    });
};

const abrirMenuMovil = () => {
    if (!consultaMenuMovil.matches) {
        return;
    }

    cerrarOtrosDropdowns(null);
    document.documentElement.dataset.mobileSidebar = 'open';
    document.body.classList.add('app-mobile-sidebar-open');
    document.querySelector('[data-sidebar-panel]')?.classList.add('is-mobile-open');
    document.querySelector('[data-sidebar-panel]')?.removeAttribute('aria-hidden');
    document.querySelector('[data-mobile-sidebar-backdrop]')?.removeAttribute('hidden');

    document.querySelectorAll('[data-mobile-sidebar-open]').forEach((boton) => {
        boton.setAttribute('aria-expanded', 'true');
    });
};

const alternarMenuMovil = () => {
    if (document.documentElement.dataset.mobileSidebar === 'open') {
        cerrarMenuMovil();
        return;
    }

    abrirMenuMovil();
};

const leerGruposMenu = () => {
    try {
        return JSON.parse(localStorage.getItem(claveGruposMenu) || '{}');
    } catch {
        return {};
    }
};

const guardarGrupoMenu = (clave, abierto) => {
    const grupos = {};

    grupos[clave] = abierto;
    localStorage.setItem(claveGruposMenu, JSON.stringify(grupos));
};

const aplicarGrupoMenu = (clave, abierto) => {
    const grupo = document.querySelector(`[data-menu-group="${clave}"]`);
    const boton = document.querySelector(`[data-menu-toggle="${clave}"]`);
    const submenu = document.querySelector(`[data-menu-submenu="${clave}"]`);

    if (!grupo || !boton || !submenu) {
        return;
    }

    grupo.classList.toggle('is-open', abierto);
    boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    submenu.hidden = !abierto;
};

const cerrarGruposMenuExcepto = (claveActual = null) => {
    document.querySelectorAll('[data-menu-group]').forEach((grupo) => {
        const clave = grupo.dataset.menuGroup;

        if (clave !== claveActual) {
            aplicarGrupoMenu(clave, false);
        }
    });
};

const limpiarSeleccionMenu = () => {
    document.querySelectorAll('[data-menu-toggle], [data-menu-leaf], [data-menu-subitem]').forEach((elemento) => {
        elemento.classList.remove('is-active');
    });
};

const aplicarSeleccionMenu = (selector) => {
    if (!selector) {
        return;
    }

    const elemento = document.querySelector(selector);

    if (!elemento) {
        return;
    }

    limpiarSeleccionMenu();
    elemento.classList.add('is-active');
    localStorage.setItem(claveSeleccionMenu, selector);

    if (elemento.dataset.menuSubitem) {
        const clavePadre = elemento.dataset.menuParent;

        cerrarGruposMenuExcepto(clavePadre);
        aplicarGrupoMenu(clavePadre, true);
        guardarGrupoMenu(clavePadre, true);
    }

    if (elemento.dataset.menuLeaf) {
        cerrarGruposMenuExcepto();
        localStorage.setItem(claveGruposMenu, JSON.stringify({}));
    }
};

const cerrarMenuMovilTrasSeleccion = () => {
    if (consultaMenuMovil.matches) {
        cerrarMenuMovil();
    }
};

const prepararMenuLateral = () => {
    const gruposGuardados = leerGruposMenu();

    document.querySelectorAll('[data-menu-group]').forEach((grupo) => {
        const clave = grupo.dataset.menuGroup;
        const abiertoInicial = Object.prototype.hasOwnProperty.call(gruposGuardados, clave)
            ? gruposGuardados[clave]
            : grupo.classList.contains('is-open');

        aplicarGrupoMenu(clave, abiertoInicial);
    });

    const seleccionGuardada = localStorage.getItem(claveSeleccionMenu);

    if (seleccionGuardada && document.querySelector(seleccionGuardada)) {
        aplicarSeleccionMenu(seleccionGuardada);
    }

    document.querySelectorAll('[data-menu-toggle]').forEach((boton) => {
        if (boton.dataset.menuReady === 'true') {
            return;
        }

        boton.dataset.menuReady = 'true';
        boton.addEventListener('click', () => {
            const clave = boton.dataset.menuToggle;
            const estaAbierto = boton.getAttribute('aria-expanded') === 'true';
            const siguienteEstado = !estaAbierto;

            if (document.documentElement.dataset.sidebar === 'collapsed') {
                localStorage.setItem(claveMenu, 'normal');
                aplicarModoMenu('normal');
            }

            cerrarGruposMenuExcepto(clave);
            aplicarGrupoMenu(clave, siguienteEstado);
            guardarGrupoMenu(clave, siguienteEstado);

            if (siguienteEstado) {
                limpiarSeleccionMenu();
                boton.classList.add('is-active');
                localStorage.setItem(claveSeleccionMenu, `[data-menu-toggle="${clave}"]`);
            }
        });
    });

    document.querySelectorAll('[data-menu-leaf]').forEach((enlace) => {
        if (enlace.dataset.menuLeafReady === 'true') {
            return;
        }

        enlace.dataset.menuLeafReady = 'true';
        enlace.addEventListener('click', () => {
            aplicarSeleccionMenu(`[data-menu-leaf="${enlace.dataset.menuLeaf}"]`);
            cerrarMenuMovilTrasSeleccion();
        });
    });

    document.querySelectorAll('[data-menu-subitem]').forEach((enlace) => {
        if (enlace.dataset.menuSubitemReady === 'true') {
            return;
        }

        enlace.dataset.menuSubitemReady = 'true';
        enlace.addEventListener('click', () => {
            aplicarSeleccionMenu(`[data-menu-subitem="${enlace.dataset.menuSubitem}"]`);
            cerrarMenuMovilTrasSeleccion();
        });
    });
};

const prepararMenuMovil = () => {
    document.querySelectorAll('[data-mobile-sidebar-open]').forEach((boton) => {
        if (boton.dataset.mobileSidebarReady === 'true') {
            return;
        }

        boton.dataset.mobileSidebarReady = 'true';
        boton.addEventListener('click', (evento) => {
            evento.stopPropagation();
            alternarMenuMovil();
        });
    });

    document.querySelectorAll('[data-mobile-sidebar-backdrop]').forEach((backdrop) => {
        if (backdrop.dataset.mobileSidebarBackdropReady === 'true') {
            return;
        }

        backdrop.dataset.mobileSidebarBackdropReady = 'true';
        backdrop.addEventListener('click', cerrarMenuMovil);
    });

    if (!consultaMenuMovil.matches || document.documentElement.dataset.mobileSidebar !== 'open') {
        cerrarMenuMovil();
    }
};

const cerrarDropdown = (contenedor) => {
    const boton = contenedor.querySelector('[data-dropdown-toggle]');
    const menu = contenedor.querySelector('[data-dropdown-menu]');

    contenedor.classList.remove('is-open');
    boton?.setAttribute('aria-expanded', 'false');

    if (menu) {
        menu.hidden = true;
    }
};

const cerrarOtrosDropdowns = (actual) => {
    document.querySelectorAll('[data-dropdown].is-open').forEach((contenedor) => {
        if (contenedor !== actual) {
            cerrarDropdown(contenedor);
        }
    });
};

const prepararDropdowns = () => {
    document.querySelectorAll('[data-dropdown]').forEach((contenedor) => {
        if (contenedor.dataset.dropdownReady === 'true') {
            return;
        }

        const boton = contenedor.querySelector('[data-dropdown-toggle]');
        const menu = contenedor.querySelector('[data-dropdown-menu]');

        if (!boton || !menu) {
            return;
        }

        contenedor.dataset.dropdownReady = 'true';

        boton.addEventListener('click', (evento) => {
            evento.stopPropagation();

            const debeAbrir = !contenedor.classList.contains('is-open');

            cerrarOtrosDropdowns(contenedor);
            contenedor.classList.toggle('is-open', debeAbrir);
            boton.setAttribute('aria-expanded', debeAbrir ? 'true' : 'false');
            menu.hidden = !debeAbrir;
        });
    });
};

const prepararPreferenciasPanel = () => {
    aplicarTema(localStorage.getItem(claveTema) || 'system');
    aplicarModoMenu(localStorage.getItem(claveMenu) || 'normal');
    prepararMenuLateral();
    prepararMenuMovil();
    prepararDropdowns();

    document.querySelectorAll('[data-theme-option]').forEach((boton) => {
        if (boton.dataset.themeReady === 'true') {
            return;
        }

        boton.dataset.themeReady = 'true';
        boton.addEventListener('click', () => {
            const tema = boton.dataset.themeOption || 'system';

            localStorage.setItem(claveTema, tema);
            aplicarTema(tema);
            cerrarOtrosDropdowns(null);
        });
    });

    document.querySelectorAll('[data-sidebar-toggle]').forEach((boton) => {
        if (boton.dataset.sidebarReady === 'true') {
            return;
        }

        boton.dataset.sidebarReady = 'true';
        boton.addEventListener('click', () => {
            const modoActual = document.documentElement.dataset.sidebar === 'collapsed' ? 'collapsed' : 'normal';
            const siguienteModo = modoActual === 'collapsed' ? 'normal' : 'collapsed';

            localStorage.setItem(claveMenu, siguienteModo);
            aplicarModoMenu(siguienteModo);
        });
    });
};

document.addEventListener('click', () => cerrarOtrosDropdowns(null));
document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') {
        cerrarOtrosDropdowns(null);
        cerrarMenuMovil();
    }
});

consultaMenuMovil.addEventListener('change', (evento) => {
    if (!evento.matches) {
        cerrarMenuMovil();
    }
});

const animarConteo = (elemento) => {
    if (elemento.dataset.countAnimated === 'true') {
        return;
    }

    elemento.dataset.countAnimated = 'true';

    const destino = Number(elemento.dataset.countTarget || 0);
    const duracion = 900;
    const inicio = performance.now();
    const formateador = new Intl.NumberFormat('es-PE', {
        maximumFractionDigits: Number.isInteger(destino) ? 0 : 1,
    });

    const avanzar = (marcaTiempo) => {
        const progreso = Math.min((marcaTiempo - inicio) / duracion, 1);
        const suavizado = 1 - Math.pow(1 - progreso, 3);

        elemento.textContent = formateador.format(destino * suavizado);

        if (progreso < 1) {
            requestAnimationFrame(avanzar);
        }
    };

    requestAnimationFrame(avanzar);
};

const prepararAnimacionesDashboard = () => {
    document.querySelectorAll('[data-count-target]').forEach(animarConteo);
};

const inicializarAplicacion = () => {
    inicializarPreline();
    prepararTransicionSalidaLogin();
    prepararPreferenciasPanel();
    prepararAnimacionesDashboard();
};

consultaTemaSistema.addEventListener('change', () => {
    if ((localStorage.getItem(claveTema) || 'system') === 'system') {
        aplicarTema('system');
    }
});

// Livewire puede reemplazar partes del DOM; Preline necesita reactivar sus componentes despues de esos cambios.
document.addEventListener('DOMContentLoaded', inicializarAplicacion);
document.addEventListener('livewire:init', () => {
    inicializarAplicacion();
    window.Livewire?.on('cerrar-overlay-preline', cerrarOverlayPreline);
});
document.addEventListener('livewire:load', inicializarAplicacion);
document.addEventListener('livewire:navigated', inicializarAplicacion);
document.addEventListener('livewire:updated', inicializarAplicacion);
