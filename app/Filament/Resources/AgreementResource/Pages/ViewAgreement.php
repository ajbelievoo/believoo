<?php

namespace App\Filament\Resources\AgreementResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\AgreementResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAgreement extends ViewRecord
{
    use RedirectsToAdminIndex;
    protected static string $resource = AgreementResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Agreement Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('agreement_number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('title'),
                        Infolists\Components\TextEntry::make('client.name')
                            ->label('Client'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'draft' => 'gray',
                                'sent' => 'blue',
                                'viewed' => 'warning',
                                'signed' => 'success',
                                'cancelled' => 'danger',
                            }),
                    ])->columns(2),

                Infolists\Components\Section::make('Project Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('project_name'),
                        Infolists\Components\TextEntry::make('service_provider_name'),
                        Infolists\Components\TextEntry::make('lead_developer'),
                        Infolists\Components\TextEntry::make('project_overview')
                            ->html()
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Financial Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_amount')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('upfront_amount')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('timeline_months')
                            ->suffix(' months'),
                        Infolists\Components\TextEntry::make('start_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('end_date')
                            ->date(),
                    ])->columns(3),

                Infolists\Components\Section::make('Work Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('workItems')
                            ->schema([
                                Infolists\Components\TextEntry::make('item_name'),
                                Infolists\Components\TextEntry::make('description'),
                                Infolists\Components\TextEntry::make('amount')
                                    ->money('USD'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn ($record) => $record->workItems->isNotEmpty()),

                Infolists\Components\Section::make('Milestones')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('milestones')
                            ->schema([
                                Infolists\Components\TextEntry::make('phase_name'),
                                Infolists\Components\TextEntry::make('payment_amount')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make('timeline_month')
                                    ->suffix(' month'),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge(),
                            ])
                            ->columns(4),
                    ])
                    ->visible(fn ($record) => $record->milestones->isNotEmpty()),

                Infolists\Components\Section::make('History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('histories')
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('action')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('description'),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('By'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
