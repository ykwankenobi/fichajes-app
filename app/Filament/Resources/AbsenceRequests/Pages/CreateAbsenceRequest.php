<?php

namespace App\Filament\Resources\AbsenceRequests\Pages;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateAbsenceRequest extends CreateRecord
{
    protected static string $resource = AbsenceRequestResource::class;

    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')
            ->hidden();
    }

    protected function getRedirectUrl(): string
    {
        return AbsenceRequestResource::getUrl('index');
    }
}