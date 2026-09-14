<?php

namespace App\Filament\Resources\StreamingPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApiKeysRelationManager extends RelationManager
{
    protected static string $relationship = 'apiKeys';
    protected static ?string $title = 'API Keys';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('app_id')
                    ->disabled()
                    ->dehydrated(false),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expiration Date'),
                
                Forms\Components\Toggle::make('regeneration_locked')
                    ->label('Lock Key Regeneration')
                    ->helperText('Prevent user from regenerating keys'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('app_id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('app_id')
                    ->searchable()
                    ->copyable()
                    ->limit(20),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'expired',
                        'danger' => 'suspended',
                        'gray' => 'cancelled',
                    ]),
                
                Tables\Columns\TextColumn::make('current_viewers')
                    ->label('Viewers'),
                
                Tables\Columns\TextColumn::make('bandwidth_used_gb')
                    ->label('Bandwidth Used')
                    ->formatStateUsing(fn ($state) => round($state, 2) . ' GB'),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                // No create action - keys are generated automatically
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(fn ($record) => $record->suspend()),
                
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'suspended')
                    ->action(fn ($record) => $record->activate()),
                
                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate Keys')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $service = app(\App\Services\StreamingApiService::class);
                        $service->regenerateKeys($record, true);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('suspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->suspend()),
                ]),
            ]);
    }
}
