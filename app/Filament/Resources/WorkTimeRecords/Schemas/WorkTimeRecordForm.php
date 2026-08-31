<?php

namespace App\Filament\Resources\WorkTimeRecords\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkTimeRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Registro original')
                    ->schema([
                        Select::make('user_id')
                            ->label('Empleado')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Select::make('record_type')
                            ->label('Tipo de incidencia')
                            ->options([
                                'work' => 'Trabajo / cierre automático',
                                'justified_exit' => 'Salida justificada',
                                'unjustified_exit' => 'Salida no justificada',
                            ])
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        DateTimePicker::make('started_at')
                            ->label('Inicio original')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        DateTimePicker::make('ended_at')
                            ->label('Fin original')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('worked_minutes')
                            ->label('Minutos trabajados')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('justified_exit_minutes')
                            ->label('Minutos justificados')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('unjustified_exit_minutes')
                            ->label('Minutos no justificados')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Corregir fichaje')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('correction.corrected_started_at')
                                    ->label('Inicio corregido')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i'),

                                DateTimePicker::make('correction.corrected_ended_at')
                                    ->label('Fin corregido')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i'),
                            ]),

                        Textarea::make('correction.reason')
                            ->label('Motivo de la corrección')
                            ->rows(3)
                            ->placeholder('Ejemplo: olvido de fichar salida, hora correcta verificada con responsable.')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Revisión')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('reviewed_by')
                                    ->label('Revisado por')
                                    ->relationship('reviewer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(),

                                DateTimePicker::make('reviewed_at')
                                    ->label('Fecha de revisión')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Textarea::make('review_notes')
                            ->label('Comentarios de revisión')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }
}
