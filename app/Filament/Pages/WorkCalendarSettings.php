<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WorkCalendarSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static ?string $navigationLabel = 'Configuración laboral';
    protected static UnitEnum|string|null $navigationGroup = 'Calendario laboral';
    protected static ?string $title = 'Configuración laboral';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.work-calendar-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(CompanySetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Días laborables')
                ->description('Los festivos importados se excluyen automáticamente aunque coincidan con un día laborable.')
                ->schema([
                    CheckboxList::make('working_days')
                        ->label('Días laborables')
                        ->options([
                            'monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miércoles',
                            'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sábado', 'sunday' => 'Domingo',
                        ])
                        ->columns(4)
                        ->default(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                        ->required(),
                ]),
        ]);
    }

    public function save(): void
    {
        CompanySetting::current()->update($this->form->getState());
        Notification::make()->title('Configuración laboral guardada')->success()->send();
    }
}
