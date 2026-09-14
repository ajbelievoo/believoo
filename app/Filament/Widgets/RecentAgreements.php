<?php

namespace App\Filament\Widgets;

use App\Models\Agreement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAgreements extends BaseWidget
{
    protected static ?string $heading = 'Recent Agreements';
    
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Agreement::query()
                    ->with('client')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('agreement_number')
                    ->label('Agreement #')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('project_name')
                    ->label('Project')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'sent',
                        'success' => 'signed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.resources.agreements.view', $record)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('View All Agreements')
                    ->url(route('admin.agreements.index'))
                    ->icon('heroicon-m-arrow-right'),
            ]);
    }
}
