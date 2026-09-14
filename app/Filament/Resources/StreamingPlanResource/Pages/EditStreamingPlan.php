<?php

namespace App\Filament\Resources\StreamingPlanResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\StreamingPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStreamingPlan extends EditRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = StreamingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
