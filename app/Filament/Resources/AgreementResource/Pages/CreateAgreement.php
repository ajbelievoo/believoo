<?php

namespace App\Filament\Resources\AgreementResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\AgreementResource;
use App\Models\AgreementHistory;
use Filament\Resources\Pages\CreateRecord;

class CreateAgreement extends CreateRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = AgreementResource::class;

    protected function afterCreate(): void
    {
        $agreement = $this->record;

        AgreementHistory::create([
            'agreement_id' => $agreement->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => 'Agreement created',
        ]);
    }
}
