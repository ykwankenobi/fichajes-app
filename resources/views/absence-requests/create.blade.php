<x-app-layout>
    <x-slot name="header">
        <x-ui.page.title>
            Solicitar ausencia
        </x-ui.page.title>

        <x-ui.link href="{{ route('absence-requests.index') }}">
            Cancelar
        </x-ui.link>
    </x-slot>

    <x-page.container>

        <x-forms.absence
            :action="route('absence-requests.store')"
            submit="Enviar solicitud"
        />

    </x-page.container>
</x-app-layout>