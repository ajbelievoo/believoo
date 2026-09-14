<?php

namespace App\Filament\Resources\UserHostingResource\Pages;

use App\Filament\Resources\UserHostingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserHosting extends EditRecord
{
    protected static string $resource = UserHostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
