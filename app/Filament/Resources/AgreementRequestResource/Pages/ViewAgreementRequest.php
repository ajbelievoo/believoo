<?php

namespace App\Filament\Resources\AgreementRequestResource\Pages;

use App\Filament\Concerns\RedirectsToAdminIndex;
use App\Filament\Resources\AgreementRequestResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAgreementRequest extends ViewRecord
{
    use RedirectsToAdminIndex;

    protected static string $resource = AgreementRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Request Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('request_number'),
                        Infolists\Components\TextEntry::make('client.name')
                            ->label('Client'),
                        Infolists\Components\TextEntry::make('project_name'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'under_review' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'converted' => 'primary',
                            }),
                    ])->columns(2),

                Infolists\Components\Section::make('Project Information (From Client)')
                    ->schema([
                        Infolists\Components\TextEntry::make('project_description')
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('requirements')
                            ->label('Specific Requirements')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('budget_range'),
                        Infolists\Components\TextEntry::make('timeline_expectation'),
                        Infolists\Components\TextEntry::make('preferred_technology'),
                    ])->columns(2),

                Infolists\Components\Section::make('Admin Review')
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('Internal Notes'),
                        Infolists\Components\TextEntry::make('agreement.title')
                            ->label('Linked Agreement'),
                        Infolists\Components\TextEntry::make('submitted_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('reviewed_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('converted_at')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
