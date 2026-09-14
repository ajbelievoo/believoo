<?php

namespace App\Filament\Resources\MailboxResource\Pages;

use App\Filament\Resources\MailboxResource;
use App\Services\MailboxService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailbox extends EditRecord
{
    protected static string $resource = MailboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalDescription('This removes the account from the mail server. Existing mail data on disk is preserved but the account can no longer log in.'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['new_password'] ?? null)) {
            app(MailboxService::class)->resetPassword($this->getRecord(), $data['new_password']);
        }

        unset($data['new_password'], $data['local_part'], $data['password']);

        $data['quota'] = isset($data['quota_gb']) && $data['quota_gb'] !== ''
            ? (int) ((float) $data['quota_gb'] * 1073741824)
            : 0;
        unset($data['quota_gb']);

        $data['modified'] = now()->format('Y-m-d H:i:s');

        return $data;
    }
}
