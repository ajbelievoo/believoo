<?php

namespace App\Filament\Resources\UserHostingResource\Pages;

use App\Filament\Resources\UserHostingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserHosting extends CreateRecord
{
    protected static string $resource = UserHostingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
