<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div style="margin-top: 2rem;">
            <x-filament::button type="submit" icon="heroicon-o-check-circle">Guardar configuración</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
