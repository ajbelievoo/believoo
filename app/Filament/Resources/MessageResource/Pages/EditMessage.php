<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMessage extends EditRecord
{
    protected static string $resource = MessageResource::class;

    protected function afterSave(): void
    {
        $record = $this->record;
        
        if ($record->admin_response) {
            // Broadcast the response to the user's session
            broadcast(new \App\Events\MessageSent($record))->toOthers();
        }
    }
}
