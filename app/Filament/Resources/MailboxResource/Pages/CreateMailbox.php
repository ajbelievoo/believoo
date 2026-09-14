<?php

namespace App\Filament\Resources\MailboxResource\Pages;

use App\Filament\Resources\MailboxResource;
use App\Services\MailboxService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMailbox extends CreateRecord
{
    protected static string $resource = MailboxResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(MailboxService::class)->createMailbox(
            localPart: $data['local_part'],
            domain: $data['domain'],
            password: $data['password'],
            fullName: $data['full_name'] ?? '',
            quotaGb: isset($data['quota_gb']) && $data['quota_gb'] !== '' ? (float) $data['quota_gb'] : null,
            active: (bool) ($data['active'] ?? true),
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Email account created';
    }
}
