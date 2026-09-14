<?php

namespace App\Filament\Resources\StreamingPlanResource\Pages;

use App\Filament\Resources\StreamingPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStreamingPlans extends ListRecords
{
    protected static string $resource = StreamingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Plan'),
        ];
    }
}
