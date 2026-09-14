<?php

namespace App\Filament\Resources\CallRequestResource\Pages;

use App\Filament\Resources\CallRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallRequests extends ListRecords
{
    protected static string $resource = CallRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
