<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

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
        $this->form->fill(CompanySetting::current()->toArray());
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
