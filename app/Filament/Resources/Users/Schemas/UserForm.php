<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos personales')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('dni')
                            ->label('DNI')
                            ->maxLength(20),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),

                        TextInput::make('pin_hash')
                            ->label('PIN fichaje')
                            ->password()
                            ->helperText('Déjalo vacío para mantener el PIN actual. Debe tener entre 4 y 12 dígitos.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->rules(['nullable', 'digits_between:4,12']),
                    ])
                    ->columns(2),

                Section::make('Datos laborales')
                    ->schema([
                        TextInput::make('puesto')
                            ->label('Puesto')
                            ->maxLength(255),

                        DatePicker::make('fecha_alta')
                            ->label('Fecha de alta')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('fecha_baja')
                            ->label('Fecha de baja')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(3),

                Section::make('Horario')
                    ->schema([
                        TextInput::make('horas_semanales')
                            ->label('Horas semanales (referencia)')
                            ->helperText('Con horario semanal, las horas esperadas se calculan a partir de las franjas indicadas abajo.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(80),

                        Section::make('Horario semanal')
                            ->description('Añade las franjas de cada día. Un día sin franjas no se considera laborable.')
                            ->schema([
                                self::scheduleRepeater('monday', 'Lunes'),
                                self::scheduleRepeater('tuesday', 'Martes'),
                                self::scheduleRepeater('wednesday', 'Miércoles'),
                                self::scheduleRepeater('thursday', 'Jueves'),
                                self::scheduleRepeater('friday', 'Viernes'),
                                self::scheduleRepeater('saturday', 'Sábado'),
                                self::scheduleRepeater('sunday', 'Domingo'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Observaciones')
                    ->schema([
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Grid::make(1)
                    ->schema([
                        Checkbox::make('activo')
                            ->label('Activo'),

                        Checkbox::make('is_admin')
                            ->label('Administrador')
                            ->disabled(fn ($record): bool => $record?->id === Filament::auth()->id())
                            ->dehydrated(fn ($record): bool => $record?->id !== Filament::auth()->id()),
                    ]),
            ]);
    }

    private static function scheduleRepeater(string $day, string $label): Repeater
    {
        return Repeater::make("weekly_schedule.{$day}")
            ->label($label)
            ->schema([
                TimePicker::make('desde')
                    ->label('Desde')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('hasta')
                    ->label('Hasta')
                    ->seconds(false)
                    ->after('desde')
                    ->required(),
            ])
            ->columns(2)
            ->addActionLabel('Añadir franja')
            ->defaultItems(0);
    }
}
