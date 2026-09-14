<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-calculate total if not set
        if (empty($data['total_amount'])) {
            $data['total_amount'] = ($data['amount'] ?? 0) + ($data['tax_amount'] ?? 0);
        }
        
        return $data;
    }
}
