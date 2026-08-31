<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Filtros del informe
        </x-slot>

        <x-slot name="description">
            Selecciona el periodo y el empleado para generar el resumen.
        </x-slot>

        <form id="work-time-report-filters" wire:submit="applyFilters">
            {{ $this->form }}
        </form>
    </x-filament::section>

    <div class="flex justify-end">
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button
                type="button"
                color="gray"
                wire:click="exportPdf"
            >
                Exportar PDF
            </x-filament::button>

            <x-filament::button
                type="button"
                wire:click="applyFilters"
            >
                Aplicar filtros
            </x-filament::button>
        </div>
    </div>

    @livewire(\App\Filament\Pages\WorkTimeReports\Widgets\ReportSummaryTable::class, [
        'reportType' => $this->reportType,
        'week' => $this->week,
        'month' => $this->month,
        'selectedUserId' => $this->selectedUserId,
    ])

    @if(filled($this->selectedUserId))
        @livewire(\App\Filament\Pages\WorkTimeReports\Widgets\DailyReportTable::class, [
            'reportType' => $this->reportType,
            'week' => $this->week,
            'month' => $this->month,
            'selectedUserId' => $this->selectedUserId,
        ])
    @endif
</x-filament-panels::page>
