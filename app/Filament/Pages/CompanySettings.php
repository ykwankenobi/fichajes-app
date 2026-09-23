<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CompanySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Ajustes de empresa';

    protected static ?string $title = 'Ajustes de empresa';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.company-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::current()->toArray();
        $settings['mail_password'] = null;

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(1)
            ->components([
                Section::make('Datos principales')
                    ->schema([
                        TextInput::make('commercial_name')
                            ->label('Nombre comercial')
                            ->maxLength(255),

                        TextInput::make('legal_name')
                            ->label('Razón social')
                            ->maxLength(255),

                        TextInput::make('tax_id')
                            ->label('CIF/NIF')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Section::make('Dirección')
                    ->schema([
                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('postal_code')
                            ->label('Código postal')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('Ciudad')
                            ->maxLength(255),

                        TextInput::make('province')
                            ->label('Provincia')
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label('País')
                            ->maxLength(255)
                            ->default('España'),
                    ])
                    ->columns(4),

                Section::make('Contacto')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('holiday_municipality_ine')
                            ->label('Código INE del municipio')
                            ->helperText('Se usa para importar el calendario laboral local.')
                            ->length(5)
                            ->numeric()
                            ->maxLength(5),
                    ])
                    ->columns(2),

                Section::make('Vacaciones')
                    ->description('Configura el modo de cómputo definido por el convenio o acuerdo aplicable a la empresa.')
                    ->schema([
                        Select::make('vacation_counting_method')
                            ->label('Tipo de días de vacaciones')
                            ->options([
                                'working' => 'Días laborables',
                                'calendar' => 'Días naturales',
                            ])
                            ->default('working')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                $set('annual_vacation_days', $state === 'calendar' ? 30 : 22);
                            }),

                        TextInput::make('annual_vacation_days')
                            ->label('Días de vacaciones al año')
                            ->helperText('Valor general; el saldo individual del empleado, si existe, tiene prioridad.')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(366)
                            ->default(22)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Correo electrónico')
                    ->description('Configura el envío y la identidad visible de los correos. La contraseña SMTP se guarda cifrada y no vuelve a mostrarse.')
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Método de envío')
                            ->options([
                                'smtp' => 'SMTP',
                                'log' => 'Solo registro (no envía correos)',
                            ])
                            ->default('log')
                            ->required()
                            ->live(),

                        TextInput::make('mail_from_name')
                            ->label('Nombre del remitente')
                            ->placeholder('Registro Horario {nombre comercial}')
                            ->maxLength(255),

                        TextInput::make('mail_from_address')
                            ->label('Dirección del remitente')
                            ->email()
                            ->placeholder('correo@empresa.es')
                            ->maxLength(255),

                        TextInput::make('mail_reply_to')
                            ->label('Responder a')
                            ->email()
                            ->placeholder('Opcional')
                            ->maxLength(255),

                        TextInput::make('mail_host')
                            ->label('Servidor SMTP')
                            ->placeholder('smtp.empresa.es')
                            ->maxLength(255)
                            ->required(fn (callable $get): bool => $get('mail_mailer') === 'smtp')
                            ->visible(fn (callable $get): bool => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_port')
                            ->label('Puerto SMTP')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->default(587)
                            ->required(fn (callable $get): bool => $get('mail_mailer') === 'smtp')
                            ->visible(fn (callable $get): bool => $get('mail_mailer') === 'smtp'),

                        Select::make('mail_encryption')
                            ->label('Cifrado SMTP')
                            ->options([
                                'tls' => 'TLS / STARTTLS',
                                'ssl' => 'SSL / TLS',
                            ])
                            ->placeholder('Sin cifrado')
                            ->visible(fn (callable $get): bool => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_username')
                            ->label('Usuario SMTP')
                            ->maxLength(255)
                            ->visible(fn (callable $get): bool => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_password')
                            ->label('Contraseña SMTP')
                            ->password()
                            ->revealable()
                            ->helperText('Déjala vacía para conservar la contraseña guardada.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->visible(fn (callable $get): bool => $get('mail_mailer') === 'smtp'),

                        TextInput::make('password_reset_subject')
                            ->label('Asunto: restablecer contraseña')
                            ->default('Restablecer contraseña - Registro Horario {nombre}')
                            ->maxLength(255),

                        TextInput::make('absence_request_subject')
                            ->label('Asunto: nueva ausencia')
                            ->default('Nueva solicitud de ausencia')
                            ->maxLength(255),

                        TextInput::make('absence_approved_subject')
                            ->label('Asunto: ausencia aprobada')
                            ->default('Solicitud de ausencia aprobada')
                            ->maxLength(255),

                        TextInput::make('absence_rejected_subject')
                            ->label('Asunto: ausencia rechazada')
                            ->default('Solicitud de ausencia rechazada')
                            ->maxLength(255),

                        TextInput::make('work_time_incident_subject')
                            ->label('Asunto: incidencia de fichaje')
                            ->default('Nueva incidencia de fichaje')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        CompanySetting::current()->update($this->form->getState());

        Notification::make()
            ->title('Ajustes de empresa guardados')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl());
    }
}
