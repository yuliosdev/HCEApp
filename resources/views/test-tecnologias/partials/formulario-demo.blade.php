<div class="grid gap-4 sm:grid-cols-2">
    <label class="block sm:col-span-2">
        <span class="mb-1 block text-sm font-medium text-zinc-700">Paciente</span>
        <input type="text" wire:model.live="form.paciente" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="Nombre del paciente">
        @error('form.paciente') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="mb-1 block text-sm font-medium text-zinc-700">Documento</span>
        <input type="text" wire:model.live="form.documento" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="DNI">
        @error('form.documento') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="mb-1 block text-sm font-medium text-zinc-700">Prioridad</span>
        <select wire:model.live="form.prioridad" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">
            <option value="Baja">Baja</option>
            <option value="Media">Media</option>
            <option value="Alta">Alta</option>
            <option value="Critica">Critica</option>
        </select>
        @error('form.prioridad') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block sm:col-span-2">
        <span class="mb-1 block text-sm font-medium text-zinc-700">Adaptador de notificacion</span>
        <select wire:model.live="form.adaptador" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600">
            <option value="flash">PHPFlasher base</option>
            <option value="toastr">Toastr</option>
            <option value="noty">Noty</option>
            <option value="sweetalert">SweetAlert2</option>
        </select>
        @error('form.adaptador') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block sm:col-span-2">
        <span class="mb-1 block text-sm font-medium text-zinc-700">Observacion</span>
        <textarea wire:model.live="form.observacion" rows="3" class="block w-full rounded-md border-zinc-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="Detalle de prueba"></textarea>
        @error('form.observacion') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>
</div>
