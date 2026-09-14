<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;

use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
