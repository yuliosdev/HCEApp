document.addEventListener('livewire:navigated', () => {
    window.HSStaticMethods?.autoInit?.();
});
