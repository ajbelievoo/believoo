<?php

namespace App\Filament\Resources\AgreementRequestResource\Pages;

use App\Filament\Resources\AgreementRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgreementRequest extends CreateRecord
{
    protected static string $resource = AgreementRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
