<?php

namespace App\Filament\Resources\Holidays\Pages;

use App\Filament\Resources\Holidays\HolidayResource;
use App\Filament\Pages\FestivusImport;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListHolidays extends ListRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Importar calendario laboral')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(FestivusImport::getUrl()),
            CreateAction::make()->label('Nuevo festivo'),
        ];
    }
}
