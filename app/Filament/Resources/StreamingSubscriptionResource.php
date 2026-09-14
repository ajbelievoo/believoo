<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StreamingSubscriptionResource\Pages;
use App\Models\StreamingSubscription;
use App\Models\StreamingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StreamingSubscriptionResource extends Resource
{
    protected static ?string $model = StreamingSubscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Streaming';
    protected static ?string $navigationLabel = 'Subscriptions';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('streaming_plan_id')
                ->relationship('plan', 'name')
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'active'    => 'Active',
                    'suspended' => 'Suspended',
                    'cancelled' => 'Cancelled',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger'  => 'suspended',
                        'gray'    => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID')
                    ->placeholder('Standalone'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(function ($record) {
                        $record->update(['status' => 'suspended']);
                        $record->projects()->update(['status' => 'suspended']);
                        Log::info('streaming_admin_action', [
                            'admin_user_id'  => Auth::id(),
                            'target_user_id' => $record->user_id,
                            'action'         => 'suspend',
                            'timestamp'      => now()->toIso8601String(),
                        ]);
                    }),

                Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'suspended')
                    ->action(function ($record) {
                        $record->update(['status' => 'active']);
                        $record->projects()->update(['status' => 'active']);
                        Log::info('streaming_admin_action', [
                            'admin_user_id'  => Auth::id(),
                            'target_user_id' => $record->user_id,
                            'action'         => 'reactivate',
                            'timestamp'      => now()->toIso8601String(),
                        ]);
                    }),

                Action::make('changePlan')
                    ->label('Change Plan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('streaming_plan_id')
                            ->label('New Plan')
                            ->options(StreamingPlan::active()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['streaming_plan_id' => $data['streaming_plan_id']]);
                        Log::info('streaming_admin_action', [
                            'admin_user_id'  => Auth::id(),
                            'target_user_id' => $record->user_id,
                            'action'         => 'change_plan',
                            'new_plan_id'    => $data['streaming_plan_id'],
                            'timestamp'      => now()->toIso8601String(),
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStreamingSubscriptions::route('/'),
            'create' => Pages\CreateStreamingSubscription::route('/create'),
            'edit'   => Pages\EditStreamingSubscription::route('/{record}/edit'),
        ];
    }
}
