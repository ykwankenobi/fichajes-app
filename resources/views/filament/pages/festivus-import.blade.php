<x-filament-panels::page>
    <form wire:submit="import">
        {{ $this->form }}

        <div style="margin-top: 2rem;">
            <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray">
                Importar calendario
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
