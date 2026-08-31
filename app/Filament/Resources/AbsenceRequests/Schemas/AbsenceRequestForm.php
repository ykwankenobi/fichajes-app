<?php

namespace App\Filament\Resources\AbsenceRequests\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AbsenceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Solicitud')
                    ->schema([
                        Select::make('user_id')
                            ->label('Empleado')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'vacation' => 'Vacaciones',
                                'sick_leave' => 'Baja médica',
                                'medical_leave' => 'Baja médica',
                                'leave_of_absence' => 'Excedencia',
                                'personal' => 'Asunto personal',
                                'personal_leave' => 'Asunto personal',
                                'other' => 'Otro',
                            ])
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

                        DatePicker::make('starts_at')
                            ->label('Fecha inicio')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

                        DatePicker::make('ends_at')
                            ->label('Fecha fin')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

                        Textarea::make('reason')
                            ->label('Motivo del empleado')
                            ->rows(4)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->placeholder('Sin motivo indicado')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Revisión')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'approved' => 'Aprobada',
                                        'rejected' => 'Rechazada',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, callable $set): void {
                                        if (in_array($state, ['approved', 'rejected'], true)) {
                                            $set('reviewed_by', Filament::auth()->id());
                                            $set('reviewed_at', now());
                                        }

                                        if ($state === 'pending') {
                                            $set('reviewed_by', null);
                                            $set('reviewed_at', null);
                                        }
                                    }),

                                Select::make('reviewed_by')
                                    ->label('Revisado por')
                                    ->relationship('reviewer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('reviewed_at')
                                    ->label('Fecha de revisión')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->disabled()
                                    ->dehydrated(),

                                DateTimePicker::make('review_notification_read_at')
                                    ->label('Notificación leída')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Textarea::make('admin_notes')
                            ->label('Comentarios')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }
}
