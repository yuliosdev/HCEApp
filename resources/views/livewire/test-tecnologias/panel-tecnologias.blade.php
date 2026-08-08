<div class="space-y-6">
    <section class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-medium text-teal-700">Fase 5C</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Test de tecnologias frontend</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-600">
                    Laboratorio local para validar Tailwind CSS 4, Livewire 4, Preline UI y PHPFlasher antes de construir pantallas clinicas definitivas.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50" data-hs-overlay="#drawer-formulario-tecnologia">
                    Abrir drawer
                </button>
                <button type="button" class="inline-flex items-center rounded-md bg-[#0f2d2e] px-3 py-2 text-sm font-semibold text-white hover:bg-[#143b3c]" data-hs-overlay="#modal-formulario-tecnologia">
                    Abrir modal
                </button>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($resumen as $item)
            <article class="rounded-md border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-950">{{ $item['nombre'] }}</h2>
                        <p class="mt-2 text-sm leading-5 text-zinc-600">{{ $item['detalle'] }}</p>
                    </div>
                    <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">{{ $item['estado'] }}</span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            <article class="rounded-md border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-950">Tabla paginada de atenciones simuladas</h2>
                            <p class="mt-1 text-sm text-zinc-600">Paginacion Livewire con controles visuales tipo Preline UI.</p>
                        </div>

                        <div class="hs-dropdown relative inline-flex">
                            <button id="dropdown-notificaciones" type="button" class="hs-dropdown-toggle inline-flex items-center rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                                Probar notificaciones
                                <iconify-icon icon="lucide:chevron-down" class="ms-2 size-4 text-zinc-500" aria-hidden="true"></iconify-icon>
                            </button>

                            <div class="hs-dropdown-menu z-20 mt-2 hidden min-w-56 rounded-md border border-zinc-200 bg-white p-1 shadow-lg" role="menu" aria-orientation="vertical" aria-labelledby="dropdown-notificaciones">
                                <button type="button" wire:click="emitirNotificacion('flash')" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100">PHPFlasher base</button>
                                <button type="button" wire:click="emitirNotificacion('toastr')" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100">Toastr</button>
                                <button type="button" wire:click="emitirNotificacion('noty')" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100">Noty</button>
                                <button type="button" wire:click="emitirNotificacion('sweetalert')" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100">SweetAlert2</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto]">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500">Busqueda</span>
                            <input type="search" wire:model.live.debounce.300ms="busqueda" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="Paciente, DNI o servicio">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500">Prioridad</span>
                            <select wire:model.live="filtroPrioridad" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                @foreach ($prioridades as $prioridad)
                                    <option value="{{ $prioridad }}">{{ $prioridad }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="flex items-end">
                            <button type="button" wire:click="limpiarFiltros" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Paciente</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Prioridad</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Servicio</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Espera</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white">
                            @forelse ($atenciones as $atencion)
                                <tr wire:key="atencion-demo-{{ $atencion['documento'] }}" class="hover:bg-teal-50/40">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-zinc-950">{{ $atencion['paciente'] }}</p>
                                        <p class="text-xs text-zinc-500">DNI {{ $atencion['documento'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $atencion['prioridad'] === 'Critica' ? 'bg-red-50 text-red-700' : ($atencion['prioridad'] === 'Alta' ? 'bg-amber-50 text-amber-700' : ($atencion['prioridad'] === 'Media' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700')) }}">
                                            {{ $atencion['prioridad'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-700">{{ $atencion['servicio'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $atencion['estado'] === 'Abierta' ? 'bg-teal-50 text-teal-700' : 'bg-zinc-100 text-zinc-600' }}">
                                            {{ $atencion['estado'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-700">{{ $atencion['tiempo'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500">No hay resultados con los filtros actuales.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-600">
                        Mostrando {{ $atenciones->firstItem() ?? 0 }} a {{ $atenciones->lastItem() ?? 0 }} de {{ $atenciones->total() }} registros.
                    </p>

                    <nav class="flex items-center gap-x-1" aria-label="Paginacion de atenciones">
                        <button type="button" wire:click="previousPage" @disabled($atenciones->onFirstPage()) class="inline-flex size-9 items-center justify-center rounded-md border border-zinc-300 bg-white text-sm text-zinc-700 hover:bg-zinc-50 disabled:pointer-events-none disabled:opacity-45" aria-label="Pagina anterior">
                            <span aria-hidden="true">&lt;</span>
                        </button>

                        @for ($pagina = 1; $pagina <= $atenciones->lastPage(); $pagina++)
                            <button type="button" wire:click="gotoPage({{ $pagina }})" class="inline-flex size-9 items-center justify-center rounded-md border text-sm font-medium {{ $atenciones->currentPage() === $pagina ? 'border-[#0f2d2e] bg-[#0f2d2e] text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' }}" aria-current="{{ $atenciones->currentPage() === $pagina ? 'page' : 'false' }}">
                                {{ $pagina }}
                            </button>
                        @endfor

                        <button type="button" wire:click="nextPage" @disabled(!$atenciones->hasMorePages()) class="inline-flex size-9 items-center justify-center rounded-md border border-zinc-300 bg-white text-sm text-zinc-700 hover:bg-zinc-50 disabled:pointer-events-none disabled:opacity-45" aria-label="Pagina siguiente">
                            <span aria-hidden="true">&gt;</span>
                        </button>
                    </nav>
                </div>
            </article>

            <article class="rounded-md border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="border-b border-zinc-200">
                    <nav class="-mb-px flex gap-x-1" aria-label="Tabs" role="tablist" aria-orientation="horizontal">
                        <button type="button" class="active hs-tab-active:border-[#0f2d2e] hs-tab-active:text-[#0f2d2e] border-b-2 border-transparent px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900" id="tabs-tecnologias-item-1" aria-selected="true" data-hs-tab="#tabs-tecnologias-1" aria-controls="tabs-tecnologias-1" role="tab">
                            Livewire
                        </button>
                        <button type="button" class="hs-tab-active:border-[#0f2d2e] hs-tab-active:text-[#0f2d2e] border-b-2 border-transparent px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900" id="tabs-tecnologias-item-2" aria-selected="false" data-hs-tab="#tabs-tecnologias-2" aria-controls="tabs-tecnologias-2" role="tab">
                            Preline
                        </button>
                        <button type="button" class="hs-tab-active:border-[#0f2d2e] hs-tab-active:text-[#0f2d2e] border-b-2 border-transparent px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900" id="tabs-tecnologias-item-3" aria-selected="false" data-hs-tab="#tabs-tecnologias-3" aria-controls="tabs-tecnologias-3" role="tab">
                            PHPFlasher
                        </button>
                    </nav>
                </div>

                <div class="mt-4">
                    <div id="tabs-tecnologias-1" role="tabpanel" aria-labelledby="tabs-tecnologias-item-1">
                        <div class="grid gap-4 md:grid-cols-2">
                            <livewire:test-tecnologias.contador-sfc />
                            <livewire:test-tecnologias.selector-mfc />
                        </div>
                    </div>

                    <div id="tabs-tecnologias-2" class="hidden" role="tabpanel" aria-labelledby="tabs-tecnologias-item-2">
                        <div class="hs-accordion-group space-y-2">
                            <div class="hs-accordion active rounded-md border border-zinc-200" id="accordion-preline-modal">
                                <button class="hs-accordion-toggle inline-flex w-full items-center justify-between gap-x-3 px-4 py-3 text-start text-sm font-semibold text-zinc-800 hover:text-teal-700" aria-expanded="true" aria-controls="accordion-preline-modal-collapse">
                                    Modal y drawer
                                    <span class="text-zinc-400">+</span>
                                </button>
                                <div id="accordion-preline-modal-collapse" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300" role="region" aria-labelledby="accordion-preline-modal">
                                    <div class="px-4 pb-4 text-sm leading-6 text-zinc-600">
                                        La pantalla usa overlays Preline para abrir formularios modales y drawers laterales sin escribir JavaScript manual por pantalla.
                                    </div>
                                </div>
                            </div>

                            <div class="hs-accordion rounded-md border border-zinc-200" id="accordion-preline-tabla">
                                <button class="hs-accordion-toggle inline-flex w-full items-center justify-between gap-x-3 px-4 py-3 text-start text-sm font-semibold text-zinc-800 hover:text-teal-700" aria-expanded="false" aria-controls="accordion-preline-tabla-collapse">
                                    Paginacion y dropdown
                                    <span class="text-zinc-400">+</span>
                                </button>
                                <div id="accordion-preline-tabla-collapse" class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300" role="region" aria-labelledby="accordion-preline-tabla">
                                    <div class="px-4 pb-4 text-sm leading-6 text-zinc-600">
                                        Los botones de paginacion y el menu de notificaciones usan estilos y convenciones visuales compatibles con Preline UI.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tabs-tecnologias-3" class="hidden" role="tabpanel" aria-labelledby="tabs-tecnologias-item-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button type="button" wire:click="emitirNotificacion('flash')" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Flash base</button>
                            <button type="button" wire:click="emitirNotificacion('toastr')" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Toastr</button>
                            <button type="button" wire:click="emitirNotificacion('noty')" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Noty</button>
                            <button type="button" wire:click="emitirNotificacion('sweetalert')" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">SweetAlert2</button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <aside class="space-y-6">
            <article class="rounded-md border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-950">Tooltip Preline</h2>
                        <p class="mt-1 text-sm text-zinc-600">Validacion visual de ayuda contextual.</p>
                    </div>
                    <div class="hs-tooltip inline-block">
                        <button type="button" class="hs-tooltip-toggle inline-flex size-9 items-center justify-center rounded-md border border-zinc-300 text-sm font-semibold text-zinc-700">
                            ?
                            <span class="hs-tooltip-content invisible absolute z-10 inline-block rounded-md bg-zinc-900 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                                Tooltip administrado por Preline UI.
                            </span>
                        </button>
                    </div>
                </div>
            </article>

            <article class="rounded-md border border-zinc-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-950">Formulario rapido</h2>
                <p class="mt-1 text-sm text-zinc-600">El mismo form object se usa en modal y drawer.</p>

                <div class="mt-4 space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-zinc-700">Paciente</span>
                        <input type="text" wire:model.live="form.paciente" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                        @error('form.paciente') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <button type="button" wire:click="registrarDesdeModal" class="w-full rounded-md bg-[#0f2d2e] px-3 py-2 text-sm font-semibold text-white hover:bg-[#143b3c]">
                        Validar y notificar
                    </button>
                </div>
            </article>
        </aside>
    </section>

    <div id="modal-formulario-tecnologia" wire:ignore.self class="hs-overlay pointer-events-none fixed start-0 top-0 z-80 hidden size-full overflow-y-auto overflow-x-hidden" role="dialog" tabindex="-1" aria-labelledby="modal-formulario-tecnologia-label">
        <div class="m-3 mt-0 opacity-0 transition-all ease-out hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 sm:mx-auto sm:w-full sm:max-w-lg">
            <div class="pointer-events-auto flex flex-col rounded-md border border-zinc-200 bg-white shadow-lg">
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3">
                    <h3 id="modal-formulario-tecnologia-label" class="font-semibold text-zinc-950">Formulario modal Preline</h3>
                    <button type="button" class="inline-flex size-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100" data-hs-overlay="#modal-formulario-tecnologia" aria-label="Cerrar">
                        <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                    </button>
                </div>

                <form wire:submit="registrarDesdeModal" class="space-y-4 p-4">
                    @include('test-tecnologias.partials.formulario-demo')

                    <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4">
                        <button type="button" wire:click="limpiarFormulario" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Limpiar</button>
                        <button type="submit" class="rounded-md bg-[#0f2d2e] px-3 py-2 text-sm font-semibold text-white hover:bg-[#143b3c]">Guardar prueba</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="drawer-formulario-tecnologia" wire:ignore.self class="hs-overlay fixed end-0 top-0 z-80 hidden h-full w-full max-w-md translate-x-full transform border-s border-zinc-200 bg-white transition-all hs-overlay-open:translate-x-0" role="dialog" tabindex="-1" aria-labelledby="drawer-formulario-tecnologia-label">
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3">
                <h3 id="drawer-formulario-tecnologia-label" class="font-semibold text-zinc-950">Formulario drawer Preline</h3>
                <button type="button" class="inline-flex size-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100" data-hs-overlay="#drawer-formulario-tecnologia" aria-label="Cerrar">
                    <iconify-icon icon="lucide:x" aria-hidden="true"></iconify-icon>
                </button>
            </div>

            <form wire:submit="registrarDesdeDrawer" class="flex-1 space-y-4 overflow-y-auto p-4">
                @include('test-tecnologias.partials.formulario-demo')

                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4">
                    <button type="button" data-hs-overlay="#drawer-formulario-tecnologia" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Cerrar</button>
                    <button type="submit" class="rounded-md bg-[#0f2d2e] px-3 py-2 text-sm font-semibold text-white hover:bg-[#143b3c]">Guardar prueba</button>
                </div>
            </form>
        </div>
    </div>
</div>
