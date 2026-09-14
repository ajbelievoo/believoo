<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Auto-calculate total if not set
        if (empty($data['total_amount'])) {
            $data['total_amount'] = ($data['amount'] ?? 0) + ($data['tax_amount'] ?? 0);
        }
        
        return $data;
    }
}
