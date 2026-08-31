<?php

namespace App\Filament\Resources\WorkTimeRecords\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkTimeRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['corrections' => fn ($corrections) => $corrections->latest()])
                ->orderByDesc('requires_review')
                ->latest('started_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('ended_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('record_type')
                    ->label('Tipo de incidencia')
                    ->badge()
                    ->formatStateUsing(function (?string $state, $record): string {
                        if ($record->closed_automatically) {
                            return 'Cierre automático';
                        }

                        return match ($state) {
                            'work' => 'Trabajo',
                            'justified_exit' => 'Salida justificada',
                            'unjustified_exit' => 'Salida no justificada',
                            default => $state ?? '-',
                        };
                    })
                    ->color(function (?string $state, $record): string {
                        if ($record->closed_automatically) {
                            return 'warning';
                        }

                        return match ($state) {
                            'work' => 'success',
                            'justified_exit' => 'info',
                            'unjustified_exit' => 'danger',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('latest_correction_status')
                    ->label('Corrección')
                    ->badge()
                    ->state(function ($record): string {
                        $correction = $record->corrections->first();

                        return $correction?->status ?? 'none';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        default => 'Sin corrección',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('worked_minutes')
                    ->label('Min. trabajo')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unjustified_exit_minutes')
                    ->label('Min. no justificados')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('closed_automatically')
                    ->label('Cierre automático')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('requires_review')
                    ->label('Pendiente de revisión')
                    ->boolean()
                    ->sortable(),

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
                TernaryFilter::make('requires_review')
                    ->label('Requiere revisión')
                    ->default(true)
                    ->placeholder('Todas')
                    ->trueLabel('Pendientes de revisión')
                    ->falseLabel('Ya revisadas'),

                SelectFilter::make('user_id')
                    ->label('Empleado')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('record_type')
                    ->label('Tipo de incidencia')
                    ->options([
                        'work' => 'Trabajo',
                        'justified_exit' => 'Salida justificada',
                        'unjustified_exit' => 'Salida no justificada',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar'),
            ])
            ->toolbarActions([]);
    }
}
