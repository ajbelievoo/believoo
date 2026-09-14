<?php

namespace App\Filament\Resources\StreamingSubscriptionResource\Pages;

use App\Filament\Resources\StreamingSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStreamingSubscriptions extends ListRecords
{
    protected static string $resource = StreamingSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
