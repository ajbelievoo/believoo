<?php

namespace App\Filament\Resources\ProjectAssetResource\Pages;

use App\Filament\Resources\ProjectAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectAsset extends EditRecord
{
    protected static string $resource = ProjectAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
