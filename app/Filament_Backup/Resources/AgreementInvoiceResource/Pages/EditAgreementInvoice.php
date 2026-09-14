<?php

namespace App\Filament\Resources\AgreementInvoiceResource\Pages;

use App\Filament\Resources\AgreementInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgreementInvoice extends EditRecord
{
    protected static string $resource = AgreementInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
