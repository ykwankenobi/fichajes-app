<?php

namespace App\Filament\Widgets;

use App\Models\AbsenceRequest;
use App\Models\Holiday;
use App\Models\WorkTimeRecord;
use Carbon\Carbon;
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
            ->with('user:id,name')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $end)
            ->where(function ($query) use ($start): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', $start);
            })
            ->get()
            ->each(function (WorkTimeRecord $record) use (&$events): void {
                $events[] = EventData::make()
                    ->id('record-'.$record->id)
                    ->title('Fichaje · '.($record->user?->name ?? 'Empleado'))
                    ->start($record->started_at)
                    ->end($record->ended_at)
                    ->backgroundColor('#3b82f6')
                    ->borderColor('#3b82f6')
                    ->extendedProps(['tipo' => 'Fichaje']);
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
}
