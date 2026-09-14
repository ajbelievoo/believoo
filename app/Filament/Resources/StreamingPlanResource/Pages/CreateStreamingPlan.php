<?php

namespace App\Filament\Resources\StreamingPlanResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\StreamingPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStreamingPlan extends CreateRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = StreamingPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        return $data;
    }
}
