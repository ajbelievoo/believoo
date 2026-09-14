<?php

namespace App\Filament\Resources\ProjectFeedbackResource\Pages;

use App\Filament\Resources\ProjectFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectFeedback extends ViewRecord
{
    protected static string $resource = ProjectFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
