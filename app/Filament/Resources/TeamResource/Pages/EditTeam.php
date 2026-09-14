<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
