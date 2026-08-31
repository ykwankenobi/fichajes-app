<?php

namespace App\Filament\Resources\AbsenceRequests\Tables;

use App\Models\AbsenceRequest;
use App\Notifications\AbsenceRequestReviewedNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AbsenceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->orderByRaw("
                    CASE
                        WHEN status = 'pending' THEN 0
                        WHEN status = 'approved' THEN 1
                        WHEN status = 'rejected' THEN 2
                        ELSE 3
                    END
                ")
                ->latest('created_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'vacation' => 'Vacaciones',
                        'sick_leave', 'medical_leave' => 'Baja médica',
                        'leave_of_absence' => 'Excedencia',
                        'personal', 'personal_leave' => 'Asunto personal',
                        'other' => 'Otro',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'vacation' => 'info',
                        'sick_leave', 'medical_leave' => 'danger',
                        'leave_of_absence' => 'warning',
                        'personal', 'personal_leave' => 'warning',
                        'other' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('reviewer.name')
                    ->label('Revisado por')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('reviewed_at')
                    ->label('Fecha revisión')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                    ]),

                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'vacation' => 'Vacaciones',
                        'sick_leave' => 'Baja médica',
                        'medical_leave' => 'Baja médica',
                        'leave_of_absence' => 'Excedencia',
                        'personal' => 'Asunto personal',
                        'personal_leave' => 'Asunto personal',
                        'other' => 'Otro',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Empleado')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->button()
                    ->color('success')
                    ->visible(fn (AbsenceRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar ausencia')
                    ->modalDescription('Se aprobará la ausencia y se enviará un email al empleado.')
                    ->action(function (AbsenceRequest $record): void {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => Filament::auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        $record->refresh()->loadMissing('user');

                        if ($record->user !== null) {
                            $record->user->notify(
                                new AbsenceRequestReviewedNotification($record)
                            );
                        }

                        FilamentNotification::make()
                            ->title('Ausencia aprobada')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->button()
                    ->color('danger')
                    ->visible(fn (AbsenceRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar ausencia')
                    ->modalDescription('Se rechazará la ausencia y se enviará un email al empleado.')
                    ->action(function (AbsenceRequest $record): void {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => Filament::auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        $record->refresh()->loadMissing('user');

                        if ($record->user !== null) {
                            $record->user->notify(
                                new AbsenceRequestReviewedNotification($record)
                            );
                        }

                        FilamentNotification::make()
                            ->title('Ausencia rechazada')
                            ->danger()
                            ->send();
                    }),

                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->button(),
            ])
            ->toolbarActions([]);
    }
}
