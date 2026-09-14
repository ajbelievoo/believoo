<?php

namespace App\Filament\Resources\ProjectAssetResource\Pages;

use App\Filament\Resources\ProjectAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectAssets extends ListRecords
{
    protected static string $resource = ProjectAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
