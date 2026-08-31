<?php

namespace App\Filament\Pages\WorkTimeReports\Widgets;

use App\Services\WorkTimeReportService;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ReportSummaryTable extends TableWidget
{
    protected static ?string $heading = 'Resumen';

    #[Url(as: 'report_type')]
    public string $reportType = 'month';

    #[Url]
    public string $week = '';

    #[Url]
    public string $month = '';

    #[Url(as: 'user_id')]
    public ?int $selectedUserId = null;

    public function mount(): void
    {
        $this->reportType = request('report_type') === 'week' ? 'week' : 'month';
        $this->week = request('week', now()->format('o-\WW'));
        $this->month = request('month', now()->format('Y-m'));

        $userId = request('user_id');
        $this->selectedUserId = filled($userId) ? (int) $userId : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getReportRows())
            ->columns([
                TextColumn::make('usuario')
                    ->label('Empleado')
                    ->searchable(),

                TextColumn::make('esperadas')
                    ->label('Esperadas'),

                TextColumn::make('computables')
                    ->label('Computadas'),

                TextColumn::make('trabajadas')
                    ->label('Trabajadas'),

                TextColumn::make('justificadas')
                    ->label('Justificadas'),

                TextColumn::make('injustificadas')
                    ->label('Sin justificar'),

                TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->color(fn (string $state): string => str_starts_with($state, '-') ? 'danger' : 'success')
                    ->weight('bold'),
            ])
            ->paginated(false);
    }

    protected function getReportRows(): Collection
    {
        $service = app(WorkTimeReportService::class);
        $user = Filament::auth()->user();

        if ($this->reportType === 'week') {
            return $service->getWeeklySummary(
                $user->id,
                (bool) $user->is_admin,
                $this->week,
                $this->selectedUserId
            );
        }

        return $service->getMonthlySummary(
            $user->id,
            (bool) $user->is_admin,
            $this->month,
            $this->selectedUserId
        );
    }
}
