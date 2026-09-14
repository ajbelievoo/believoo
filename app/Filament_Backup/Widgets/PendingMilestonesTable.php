<?php

namespace App\Filament\Widgets;

use App\Models\AgreementMilestone;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingMilestonesTable extends BaseWidget
{
    protected static ?string $heading = 'Pending Milestone Payments';
    
    protected static ?int $sort = 4;
    
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AgreementMilestone::query()
                    ->where('status', 'pending')
                    ->with(['agreement', 'agreement.client'])
                    ->orderBy('due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('agreement.project_name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agreement.client.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Milestone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date?->isPast() ? 'danger' : null),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Days Left')
                    ->formatStateUsing(function ($record) {
                        if (!$record->due_date) return 'N/A';
                        $days = now()->diffInDays($record->due_date, false);
                        if ($days < 0) return 'Overdue by ' . abs($days) . ' days';
                        return $days . ' days left';
                    })
                    ->color(fn ($record) => $record->due_date?->isPast() ? 'danger' : 'warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewAgreement')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.resources.agreements.view', $record->agreement_id)),
            ])
            ->defaultSort('due_date', 'asc')
            ->paginated([5, 10, 25])
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('View All Milestones')
                    ->url(route('admin.agreements.index'))
                    ->icon('heroicon-m-arrow-right'),
            ]);
    }
}
