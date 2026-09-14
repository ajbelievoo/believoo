<?php

namespace App\Filament\Resources\UserHostingResource\Pages;

use App\Filament\Resources\UserHostingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserHostings extends ListRecords
{
    protected static string $resource = UserHostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Hosting'),
        ];
    }
}
