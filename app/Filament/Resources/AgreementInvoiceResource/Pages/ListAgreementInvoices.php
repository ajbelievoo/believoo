<?php

namespace App\Filament\Resources\AgreementInvoiceResource\Pages;

use App\Filament\Resources\AgreementInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgreementInvoices extends ListRecords
{
    protected static string $resource = AgreementInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
