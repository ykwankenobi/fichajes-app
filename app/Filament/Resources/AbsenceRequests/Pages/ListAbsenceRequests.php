<?php

namespace App\Filament\Resources\AbsenceRequests\Pages;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbsenceRequests extends ListRecords
{
    protected static string $resource = AbsenceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
