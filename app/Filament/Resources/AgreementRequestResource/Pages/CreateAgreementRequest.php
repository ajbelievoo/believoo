<?php

namespace App\Filament\Resources\AgreementRequestResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\AgreementRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgreementRequest extends CreateRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = AgreementRequestResource::class;
}
