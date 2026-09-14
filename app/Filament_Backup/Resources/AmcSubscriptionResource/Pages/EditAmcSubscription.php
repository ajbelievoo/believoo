<?php

namespace App\Filament\Resources\AmcSubscriptionResource\Pages;

use App\Filament\Resources\AmcSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmcSubscription extends EditRecord
{
    protected static string $resource = AmcSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
