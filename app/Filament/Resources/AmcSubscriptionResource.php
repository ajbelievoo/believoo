<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmcSubscriptionResource\Pages;
use App\Models\AmcSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmcSubscriptionResource extends Resource
{
    protected static ?string $model = AmcSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?string $label = 'AMC Subscriptions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Information')
                    ->schema([
                        Forms\Components\Select::make('agreement_id')
                            ->relationship('agreement', 'project_name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('subscription_number')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('plan_type')
                            ->options([
                                'basic' => 'Basic ($150/month)',
                                'standard' => 'Standard ($200/month)',
                                'premium' => 'Premium ($350/month)',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('monthly_amount')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subscription_number')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('agreement.project_name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('plan_type')
                    ->colors([
                        'gray' => 'basic',
                        'info' => 'standard',
                        'warning' => 'premium',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'basic' => 'Basic',
                        'standard' => 'Standard',
                        'premium' => 'Premium',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => 'expired',
                        'gray' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('monthly_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpiringSoon() ? 'danger' : null),
                Tables\Columns\TextColumn::make('reminder_count')
                    ->label('Reminders Sent'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('plan_type')
                    ->options([
                        'basic' => 'Basic',
                        'standard' => 'Standard',
                        'premium' => 'Premium',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AmcSubscription $record): bool => $record->status === 'pending')
                    ->action(function (AmcSubscription $record) {
                        $record->update(['status' => 'active']);
                        
                        // Notify client
                        $record->client->notify(new \App\Notifications\AmcSubscribedNotification($record));

                        Notification::make()
                            ->title('AMC Subscription activated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('sendReminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (AmcSubscription $record): bool => $record->status === 'active' && $record->isExpiringSoon(30))
                    ->action(function (AmcSubscription $record) {
                        $record->client->notify(new \App\Notifications\AmcWarrantyExpiringNotification(
                            $record->agreement,
                            $record->daysUntilExpiry(),
                            $record->monthly_amount
                        ));
                        
                        $record->update([
                            'last_reminder_sent' => now(),
                            'reminder_count' => $record->reminder_count + 1,
                        ]);

                        Notification::make()
                            ->title('Reminder sent to client')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmcSubscriptions::route('/'),
            'create' => Pages\CreateAmcSubscription::route('/create'),
            'edit' => Pages\EditAmcSubscription::route('/{record}/edit'),
        ];
    }
}
