<?php

namespace App\Filament\Resources\StreamingSubscriptionResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;

use App\Filament\Resources\StreamingSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStreamingSubscription extends EditRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = StreamingSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
