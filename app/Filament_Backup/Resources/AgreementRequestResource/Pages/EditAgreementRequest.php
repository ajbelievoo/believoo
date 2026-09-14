<?php

namespace App\Filament\Resources\AgreementRequestResource\Pages;

use App\Filament\Resources\AgreementRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgreementRequest extends EditRecord
{
    protected static string $resource = AgreementRequestResource::class;

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
