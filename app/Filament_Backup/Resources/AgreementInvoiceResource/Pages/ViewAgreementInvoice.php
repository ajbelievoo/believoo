<?php

namespace App\Filament\Resources\AgreementInvoiceResource\Pages;

use App\Filament\Resources\AgreementInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgreementInvoice extends ViewRecord
{
    protected static string $resource = AgreementInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->url(fn ($record) => route('admin.invoices.download', $record))
                ->openUrlInNewTab(),
        ];
    }
}
