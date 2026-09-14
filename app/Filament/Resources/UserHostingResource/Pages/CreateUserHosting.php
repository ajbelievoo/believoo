<?php

namespace App\Filament\Resources\UserHostingResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\UserHostingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserHosting extends CreateRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = UserHostingResource::class;
}
