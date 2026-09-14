<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
