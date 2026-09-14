<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StreamingPlanResource\Pages;
use App\Filament\Resources\StreamingPlanResource\RelationManagers\ApiKeysRelationManager;
use App\Models\StreamingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StreamingPlanResource extends Resource
{
    protected static ?string $model = StreamingPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationGroup = 'Services';
    protected static ?string $navigationLabel = 'Streaming Plans';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Live Stream Pro'),
                        
                        Forms\Components\TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generated from name'),
                        
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3)
                            ->placeholder('Describe the streaming plan features...'),
                    ]),

                Forms\Components\Section::make('Pricing')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(49.99),
                        
                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->default('monthly'),
                        
                        Forms\Components\Toggle::make('is_addon')
                            ->label('VPS Add-on Mode')
                            ->helperText('Can be added to VPS plans')
                            ->live()
                            ->default(false),
                        
                        Forms\Components\TextInput::make('addon_price')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->visible(fn (Forms\Get $get) => $get('is_addon'))
                            ->helperText('Price when added as VPS addon'),
                    ]),

                Forms\Components\Section::make('Streaming Limits')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('max_viewers')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->helperText('Concurrent viewers'),
                        
                        Forms\Components\TextInput::make('max_bitrate')
                            ->required()
                            ->numeric()
                            ->suffix('kbps')
                            ->default(5000)
                            ->helperText('Maximum bitrate'),
                        
                        Forms\Components\Select::make('max_resolution')
                            ->options([
                                480 => '480p (SD)',
                                720 => '720p (HD)',
                                1080 => '1080p (Full HD)',
                                1440 => '1440p (2K)',
                                2160 => '2160p (4K)',
                            ])
                            ->default(1080),
                        
                        Forms\Components\TextInput::make('bandwidth_gb')
                            ->required()
                            ->numeric()
                            ->suffix('GB/month')
                            ->default(1000),
                        
                        Forms\Components\TextInput::make('storage_gb')
                            ->required()
                            ->numeric()
                            ->suffix('GB')
                            ->default(50)
                            ->helperText('Recording storage'),
                        
                        Forms\Components\TextInput::make('stream_count')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->helperText('Concurrent streams allowed'),
                    ]),

                Forms\Components\Section::make('Features & Protocols')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Toggle::make('rtmp_support')
                            ->label('RTMP Ingest')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('webrtc_support')
                            ->label('WebRTC')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('hls_support')
                            ->label('HLS Playback')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('dash_support')
                            ->label('DASH Playback')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('Cloud Recording')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('transcoding_enabled')
                            ->label('Live Transcoding')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('adaptive_bitrate')
                            ->label('Adaptive Bitrate (ABR)')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('low_latency')
                            ->label('Low Latency Mode')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('Plan Settings')
                    ->columns(2)
                    ->schema([
                        Forms\Components\KeyValue::make('features')
                            ->label('Additional Features')
                            ->keyLabel('Feature')
                            ->valueLabel('Description')
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('font-bold'),
                
                Tables\Columns\BadgeColumn::make('price')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 2))
                    ->color('success'),
                
                Tables\Columns\IconColumn::make('is_addon')
                    ->label('Add-on')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                
                Tables\Columns\TextColumn::make('max_viewers')
                    ->label('Viewers')
                    ->formatStateUsing(fn ($state) => number_format($state)),
                
                Tables\Columns\TextColumn::make('max_resolution')
                    ->label('Resolution')
                    ->formatStateUsing(fn ($state) => $state . 'p'),
                
                Tables\Columns\TextColumn::make('bandwidth_gb')
                    ->label('Bandwidth')
                    ->formatStateUsing(fn ($state) => $state . ' GB/mo'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('apiKeys_count')
                    ->label('Active Keys')
                    ->counts('apiKeys'),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active Plans')
                    ->query(fn ($query) => $query->where('is_active', true)),
                
                Tables\Filters\Filter::make('addon')
                    ->label('VPS Add-ons')
                    ->query(fn ($query) => $query->where('is_addon', true)),
                
                Tables\Filters\Filter::make('standalone')
                    ->label('Standalone Plans')
                    ->query(fn ($query) => $query->where('is_addon', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (StreamingPlan $record) {
                        $newPlan = $record->replicate();
                        $newPlan->name = $record->name . ' (Copy)';
                        $newPlan->slug = $record->slug . '-copy-' . uniqid();
                        $newPlan->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            ApiKeysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStreamingPlans::route('/'),
            'create' => Pages\CreateStreamingPlan::route('/create'),
            'edit' => Pages\EditStreamingPlan::route('/{record}/edit'),
        ];
    }
}
