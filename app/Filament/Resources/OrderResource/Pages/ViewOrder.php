<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
