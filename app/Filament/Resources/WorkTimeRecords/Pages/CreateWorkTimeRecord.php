<?php

namespace App\Filament\Resources\WorkTimeRecords\Pages;

use App\Filament\Resources\WorkTimeRecords\WorkTimeRecordResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkTimeRecord extends CreateRecord
{
    protected static string $resource = WorkTimeRecordResource::class;

    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')
            ->hidden();
    }

    protected function getRedirectUrl(): string
    {
        return WorkTimeRecordResource::getUrl('index');
    }
}