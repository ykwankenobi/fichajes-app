<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable(),

                TextColumn::make('email_verified_at')
                    ->label('Email verificado')
                    ->dateTime()
                    ->sortable(),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('horas_semanales')
                    ->label('Horas semanales')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('puesto')
                    ->label('Puesto')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('horario')
                    ->label('Horario')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Empleados activos',
                        'inactive' => 'Empleados inactivos',
                        'working_now' => 'Trabajando ahora',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query
                                ->where('is_admin', false)
                                ->where('activo', true),
                            'inactive' => $query
                                ->where('is_admin', false)
                                ->where('activo', false),
                            'working_now' => $query
                                ->where('is_admin', false)
                                ->where('activo', true)
                                ->whereHas('activeWorkingRecord'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('passwordResetLink')
                    ->label('Enlace contraseña')
                    ->icon('heroicon-o-link')
                    ->modalHeading(fn (User $record): string => "Enlace de contraseña - {$record->name}")
                    ->modalDescription('Copia este enlace y envíaselo al usuario para que pueda crear o cambiar su contraseña.')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn (Action $action): Action => $action->label('Cerrar'))
                    ->modalContent(fn (User $record) => view('filament.actions.password-reset-link', [
                        'url' => self::createPasswordResetUrl($record),
                    ])),

                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function createPasswordResetUrl(User $user): string
    {
        $token = Password::broker()->createToken($user);

        return route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }
}
