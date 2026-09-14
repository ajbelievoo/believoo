<?php

namespace App\Filament\Resources\AgreementRequestResource\Pages;

use App\Filament\Resources\AgreementRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgreementRequests extends ListRecords
{
    protected static string $resource = AgreementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create button - requests only come from clients
        ];
    }
}
