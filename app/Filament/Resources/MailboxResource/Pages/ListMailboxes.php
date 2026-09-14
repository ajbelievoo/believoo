<?php

namespace App\Filament\Resources\MailboxResource\Pages;

use App\Filament\Resources\MailboxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMailboxes extends ListRecords
{
    protected static string $resource = MailboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Email Account'),
            Actions\Action::make('webmail')
                ->label('Open Webmail')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url('https://mail.believoo.com', shouldOpenInNewTab: true),
        ];
    }
}
