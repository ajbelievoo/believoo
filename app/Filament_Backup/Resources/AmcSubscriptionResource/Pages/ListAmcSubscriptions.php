<?php

namespace App\Filament\Resources\AmcSubscriptionResource\Pages;

use App\Filament\Resources\AmcSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmcSubscriptions extends ListRecords
{
    protected static string $resource = AmcSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
