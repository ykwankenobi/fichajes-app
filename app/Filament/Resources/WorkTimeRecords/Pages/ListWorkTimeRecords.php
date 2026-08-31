<?php

namespace App\Filament\Resources\WorkTimeRecords\Pages;

use App\Filament\Resources\WorkTimeRecords\WorkTimeRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkTimeRecords extends ListRecords
{
    protected static string $resource = WorkTimeRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
