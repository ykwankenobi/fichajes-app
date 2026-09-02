<?php

namespace App\Filament\Widgets;

use App\Models\AbsenceRequest;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class DailyAbsenceTable extends TableWidget
{
    protected static ?string $heading = 'Ausencias del día';

    public string $date;

    public function mount(string $date): void
    {
        $this->date = $date;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getDailyAbsences())
            ->columns([
                TextColumn::make('employee')
                    ->label('Empleado'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Vacaciones' => 'info',
                        'Baja médica' => 'danger',
                        'Asunto personal' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('starts_at')
                    ->label('Desde'),

                TextColumn::make('ends_at')
                    ->label('Hasta'),

                TextColumn::make('duration')
                    ->label('Duración'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Aprobada' ? 'success' : 'warning'),
            ])
            ->emptyStateHeading('No hay ausencias para este día')
            ->paginated([10, 25, 50]);
    }

    protected function getDailyAbsences(): Collection
    {
        $date = Carbon::parse($this->date);

        return AbsenceRequest::query()
            ->with('user:id,name')
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('ends_at', '>=', $date)
            ->orderBy('starts_at')
            ->get()
            ->map(function (AbsenceRequest $absence): array {
                $days = (int) $absence->starts_at->diffInDays($absence->ends_at) + 1;

                return [
                    'id' => $absence->id,
                    'employee' => $absence->user?->name ?? 'Empleado',
                    'type' => match ($absence->type) {
                        'vacation' => 'Vacaciones',
                        'sick_leave', 'medical_leave' => 'Baja médica',
                        'personal', 'personal_leave' => 'Asunto personal',
                        default => 'Otra ausencia',
                    },
                    'starts_at' => $absence->starts_at->format('d/m/Y'),
                    'ends_at' => $absence->ends_at->format('d/m/Y'),
                    'duration' => $days.' '.($days === 1 ? 'día' : 'días'),
                    'status' => $absence->status === 'approved' ? 'Aprobada' : 'Pendiente',
                ];
            });
    }
}
