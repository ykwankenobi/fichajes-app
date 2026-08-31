<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')
            ->hidden();
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }
}