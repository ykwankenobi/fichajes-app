<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 2rem;">
            <x-filament::button type="submit" icon="heroicon-o-check-circle">
                Guardar ajustes
            </x-filament::button>
        </div>
    </form>

    <x-filament::section icon="heroicon-o-device-tablet">
        <x-slot name="heading">
            Terminal de fichajes
        </x-slot>

        <x-slot name="description">
            Abre este enlace desde Chrome en el dispositivo del centro y pulsa «Instalar app». La aplicación instalada se abrirá sin barra de direcciones.
        </x-slot>

        <x-filament::button
            tag="a"
            :href="route('kiosk.index')"
            target="_blank"
            icon="heroicon-o-arrow-top-right-on-square"
        >
            Abrir terminal de fichajes
        </x-filament::button>
    </x-filament::section>
</x-filament-panels::page>
