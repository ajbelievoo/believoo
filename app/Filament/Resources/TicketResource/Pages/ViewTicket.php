<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = TicketResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        if ($this->record->status === 'in_progress') {
            $this->record->update(['status' => 'open']);
        }
    }

    protected function getListeners(): array
    {
        return [
            "echo:ticket.{$this->record->id},TicketMessageSent" => 'refreshInfolist',
        ];
    }

    public function refreshInfolist(): void
    {
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
