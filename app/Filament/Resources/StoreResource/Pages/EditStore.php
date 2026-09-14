<?php

namespace App\Filament\Resources\StoreResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;

use App\Filament\Resources\StoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStore extends EditRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
