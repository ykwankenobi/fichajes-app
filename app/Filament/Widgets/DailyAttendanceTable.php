<?php

namespace App\Filament\Widgets;

use App\Models\WorkTimeRecord;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class DailyAttendanceTable extends TableWidget
{
    protected static ?string $heading = null;

    public string $date;

    public function mount(string $date): void
    {
        $this->date = $date;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getDailyRecords())
            ->columns([
                TextColumn::make('employee')
                    ->label('Empleado'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Salida justificada' => 'info',
                        'Salida no justificada' => 'danger',
                        default => 'success',
                    }),

                TextColumn::make('started_at')
                    ->label('Entrada'),

                TextColumn::make('ended_at')
                    ->label('Salida'),

                TextColumn::make('duration')
                    ->label('Duración'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'En curso' => 'info',
                        'Requiere revisión', 'Cierre automático' => 'warning',
                        default => 'success',
                    }),

                IconColumn::make('corrected')
                    ->label('Corregido')
                    ->boolean(),
            ])
            ->emptyStateHeading('No hay fichajes para este día')
            ->paginated([10, 25, 50]);
    }

    protected function getDailyRecords(): Collection
    {
        $date = Carbon::parse($this->date);

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
                    'id' => $record->id,
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
