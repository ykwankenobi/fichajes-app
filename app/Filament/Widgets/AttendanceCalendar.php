<?php

namespace App\Filament\Widgets;

use App\Models\AbsenceRequest;
use App\Models\Holiday;
use App\Models\WorkTimeRecord;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class AttendanceCalendar extends FullCalendarWidget
{
    protected static ?int $sort = -10;

    protected ?string $heading = 'Calendario de actividad';

    protected function headerActions(): array
    {
        return [];
    }

    public function onEventClick(array $event): void
    {
        if (! str_starts_with((string) ($event['id'] ?? ''), 'records-')) {
            return;
        }

        $this->mountAction('view', ['event' => $event]);
    }

    protected function viewAction(): Action
    {
        return Action::make('view')
            ->modalHeading(fn (array $arguments): string => 'Fichajes del '.$this->eventDate($arguments)->format('d/m/Y'))
            ->modalContent(fn (array $arguments) => view('filament.widgets.daily-attendance-modal', [
                'records' => $this->recordsForDate($this->eventDate($arguments)),
            ]))
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    public function fetchEvents(array $info): array
    {
        $start = Carbon::parse($info['start'])->startOfDay();
        $end = Carbon::parse($info['end'])->endOfDay();
        $events = [];

        Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->each(function (Holiday $holiday) use (&$events): void {
                $events[] = EventData::make()
                    ->id('holiday-'.$holiday->id)
                    ->title('Festivo: '.$holiday->name)
                    ->start($holiday->date->toDateString())
                    ->allDay()
                    ->backgroundColor('#f59e0b')
                    ->borderColor('#f59e0b')
                    ->textColor('#451a03')
                    ->extendedProps(['tipo' => 'Festivo']);
            });

        AbsenceRequest::query()
            ->with('user:id,name')
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('starts_at', '<=', $end)
            ->whereDate('ends_at', '>=', $start)
            ->get()
            ->each(function (AbsenceRequest $absence) use (&$events): void {
                $status = $absence->status === 'approved' ? 'Aprobada' : 'Pendiente';
                $type = match ($absence->type) {
                    'vacation' => 'Vacaciones',
                    'sick_leave' => 'Baja médica',
                    default => 'Ausencia',
                };
                $color = $absence->status === 'approved' ? '#22c55e' : '#a855f7';

                $events[] = EventData::make()
                    ->id('absence-'.$absence->id)
                    ->title($type.' · '.($absence->user?->name ?? 'Empleado'))
                    ->start($absence->starts_at->toDateString())
                    ->end($absence->ends_at->copy()->addDay()->toDateString())
                    ->allDay()
                    ->backgroundColor($color)
                    ->borderColor($color)
                    ->extendedProps(['tipo' => $type, 'estado' => $status]);
            });

        WorkTimeRecord::query()
            ->whereNotNull('started_at')
            ->whereBetween('started_at', [$start, $end])
            ->get()
            ->groupBy(fn (WorkTimeRecord $record): string => $record->started_at->toDateString())
            ->each(function ($records, string $date) use (&$events): void {
                $count = $records->count();

                $events[] = EventData::make()
                    ->id('records-'.$date)
                    ->title($count.' '.($count === 1 ? 'fichaje' : 'fichajes'))
                    ->start($date)
                    ->allDay()
                    ->backgroundColor('#3b82f6')
                    ->borderColor('#3b82f6')
                    ->extendedProps(['tipo' => 'Fichajes', 'cantidad' => $count]);
            });

        // Return plain arrays so Livewire serializes the event payload consistently.
        return array_map(
            static fn (EventData $event): array => $event->toArray(),
            $events,
        );
    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listMonth',
            ],
            'height' => 'auto',
        ];
    }

    protected function eventDate(array $arguments): Carbon
    {
        return Carbon::parse($arguments['event']['start'] ?? now())->startOfDay();
    }

    protected function recordsForDate(Carbon $date): Collection
    {
        return WorkTimeRecord::query()
            ->with(['user:id,name', 'latestApprovedCorrection'])
            ->whereBetween('started_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('started_at')
            ->get()
            ->map(function (WorkTimeRecord $record): array {
                $correction = $record->latestApprovedCorrection;
                $startedAt = $correction?->corrected_started_at ?? $record->started_at;
                $endedAt = $correction?->corrected_ended_at ?? $record->ended_at;
                $minutes = $endedAt ? (int) $startedAt->diffInMinutes($endedAt) : null;

                return [
                    'employee' => $record->user?->name ?? 'Empleado',
                    'type' => match ($record->record_type) {
                        WorkTimeRecord::TYPE_JUSTIFIED_EXIT => 'Salida justificada',
                        WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => 'Salida no justificada',
                        default => 'Trabajo',
                    },
                    'started_at' => $startedAt->format('H:i'),
                    'ended_at' => $endedAt?->format('H:i') ?? 'Abierto',
                    'duration' => $minutes === null
                        ? '—'
                        : sprintf('%d h %02d min', intdiv($minutes, 60), $minutes % 60),
                    'status' => $record->requires_review
                        ? 'Requiere revisión'
                        : ($record->closed_automatically ? 'Cierre automático' : ($endedAt ? 'Cerrado' : 'En curso')),
                    'corrected' => $correction !== null,
                ];
            });
    }
}
