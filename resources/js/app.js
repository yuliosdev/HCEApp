import './bootstrap';
import 'preline';

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

// Livewire puede reemplazar partes del DOM; Preline necesita reactivar sus componentes despues de esos cambios.
document.addEventListener('DOMContentLoaded', inicializarPreline);
document.addEventListener('livewire:init', () => {
    inicializarPreline();
    window.Livewire?.on('cerrar-overlay-preline', cerrarOverlayPreline);
});
document.addEventListener('livewire:load', inicializarPreline);
document.addEventListener('livewire:navigated', inicializarPreline);
document.addEventListener('livewire:updated', inicializarPreline);
