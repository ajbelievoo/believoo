<?php

namespace App\Filament\Resources\AgreementResource\Pages;

use App\Filament\Resources\AgreementResource;
use App\Models\AgreementHistory;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgreement extends EditRecord
{
    protected static string $resource = AgreementResource::class;

    protected function afterSave(): void
    {
        $agreement = $this->record;

        AgreementHistory::create([
            'agreement_id' => $agreement->id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'description' => 'Agreement details updated',
        ]);
    }

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
