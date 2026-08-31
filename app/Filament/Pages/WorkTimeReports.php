<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\WorkTimeReportService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class WorkTimeReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes de fichajes';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.work-time-reports';

    public ?array $filters = [];

    public string $reportType = 'month';

    public string $week;

    public string $month;

    public ?int $selectedUserId = null;

    public bool $includeDailyDetails = false;

    public function mount(): void
    {
        $this->reportType = request('report_type') === 'week' ? 'week' : 'month';
        $this->week = request('week', now()->format('o-\WW'));
        $this->month = request('month', now()->format('Y-m'));

        $userId = request('user_id');
        $this->selectedUserId = filled($userId) ? (int) $userId : null;

        $this->includeDailyDetails = request()->boolean('include_daily');

        $this->form->fill([
            'report_type' => $this->reportType,
            'week' => $this->week,
            'month' => $this->month,
            'user_id' => $this->selectedUserId,
            'include_daily' => $this->includeDailyDetails,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('report_type')
                    ->label('Tipo de informe')
                    ->options([
                        'month' => 'Mes',
                        'week' => 'Semana',
                    ])
                    ->required()
                    ->live()
                    ->columnSpan(1),

                TextInput::make('week')
                    ->label('Semana')
                    ->type('week')
                    ->visible(fn (callable $get): bool => $get('report_type') === 'week')
                    ->columnSpan(1),

                TextInput::make('month')
                    ->label('Mes')
                    ->type('month')
                    ->visible(fn (callable $get): bool => $get('report_type') === 'month')
                    ->columnSpan(1),

                Select::make('user_id')
                    ->label('Empleado')
                    ->options(fn (): array => User::query()
                        ->where('is_admin', false)
                        ->where('activo', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->placeholder('Todos')
                    ->columnSpan(1),

                Checkbox::make('include_daily')
                    ->label('Incluir detalle diario en el PDF')
                    ->helperText('Agrupa los días por empleado dentro del PDF exportado.')
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->statePath('filters');
    }

    public function applyFilters(): void
    {
        $data = $this->form->getState();

        $this->reportType = $data['report_type'] ?? 'month';
        $this->week = $data['week'] ?? now()->format('o-\WW');
        $this->month = $data['month'] ?? now()->format('Y-m');
        $this->selectedUserId = filled($data['user_id'] ?? null)
            ? (int) $data['user_id']
            : null;
        $this->includeDailyDetails = (bool) ($data['include_daily'] ?? false);

        $query = [
            'report_type' => $this->reportType,
            'week' => $this->week,
            'month' => $this->month,
            'user_id' => $this->selectedUserId,
            'include_daily' => $this->includeDailyDetails ? 1 : null,
        ];

        $this->redirectRoute('filament.admin.pages.work-time-reports', array_filter($query));
    }

    public function exportPdf(): void
    {
        $data = $this->form->getState();

        $reportType = $data['report_type'] ?? 'month';
        $week = $data['week'] ?? now()->format('o-\WW');
        $month = $data['month'] ?? now()->format('Y-m');
        $userId = $data['user_id'] ?? null;
        $includeDaily = (bool) ($data['include_daily'] ?? false);

        $query = array_filter([
            'user_id' => $userId,
            'include_daily' => $includeDaily ? 1 : null,
        ]);

        if ($reportType === 'week') {
            $query['week'] = $week;

            $url = route('reports.weekly.export.pdf', $query);
        } else {
            $query['month'] = $month;

            $url = route('reports.monthly.export.pdf', $query);
        }

        $this->js("window.open(" . json_encode($url) . ", '_blank')");
    }

    public function getUsersProperty(): Collection
    {
        return User::query()
            ->where('is_admin', false)
            ->where('activo', true)
            ->orderBy('name')
            ->get();
    }

    public function getReportProperty(): Collection
    {
        $service = app(WorkTimeReportService::class);
        $user = auth()->user();

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

    public function getDailyReportProperty(): Collection
    {
        $service = app(WorkTimeReportService::class);
        $user = auth()->user();

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

    public function getPeriodLabelProperty(): string
    {
        if ($this->reportType === 'week') {
            [$year, $weekNumber] = explode('-W', $this->week);

            $weekStart = Carbon::now()
                ->setISODate((int) $year, (int) $weekNumber)
                ->startOfWeek();

            $weekEnd = $weekStart->copy()->endOfWeek();

            return 'Semana ' . (int) $weekNumber . ': ' .
                $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y');
        }

        return Carbon::createFromFormat('Y-m-d', $this->month . '-01')
            ->translatedFormat('F Y');
    }
}
