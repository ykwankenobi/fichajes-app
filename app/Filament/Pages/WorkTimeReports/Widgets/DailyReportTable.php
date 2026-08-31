<?php

namespace App\Filament\Pages\WorkTimeReports\Widgets;

use App\Services\WorkTimeReportService;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class DailyReportTable extends TableWidget
{
    protected static ?string $heading = 'Detalle diario';

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
            ->records(fn (): Collection => $this->getDailyReportRows())
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha'),

                TextColumn::make('computables')
                    ->label('Computadas'),

                TextColumn::make('trabajadas')
                    ->label('Trabajadas'),

                TextColumn::make('justificadas')
                    ->label('Justificadas'),

                TextColumn::make('injustificadas')
                    ->label('Sin justificar'),
            ])
            ->paginated(false);
    }

    protected function getDailyReportRows(): Collection
    {
        if (! filled($this->selectedUserId)) {
            return collect();
        }

        $service = app(WorkTimeReportService::class);
        $user = Filament::auth()->user();

        if ($this->reportType === 'week') {
            return $service->getWeeklyDailySummary(
                $user->id,
                (bool) $user->is_admin,
                $this->week,
                $this->selectedUserId
            );
        }

        return $service->getMonthlyDailySummary(
            $user->id,
            (bool) $user->is_admin,
            $this->month,
            $this->selectedUserId
        );
    }
}
