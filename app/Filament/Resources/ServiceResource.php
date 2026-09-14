<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('category')
                            ->placeholder('e.g., Development, Hosting, Marketing')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('icon')
                            ->helperText('Use Heroicon name or FontAwesome class')
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content')
                            ->label('Full Page Content')
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Pricing & Features')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('price_label')
                            ->required()
                            ->default('Starting From'),
                        Forms\Components\Repeater::make('features')
                            ->schema([
                                Forms\Components\TextInput::make('feature')
                                    ->required(),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('pricing_tiers')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->placeholder('e.g., Basic, Pro, Enterprise')
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\TextInput::make('billing_cycle')
                                    ->placeholder('e.g., per month, one-time')
                                    ->required(),
                                Forms\Components\Repeater::make('tier_features')
                                    ->schema([
                                        Forms\Components\TextInput::make('feature')
                                            ->required(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                        Forms\Components\Section::make('Billing Cycles & Discounts')
                            ->description('Set up monthly, quarterly, half-yearly and yearly pricing with discounts. Higher discounts for longer terms encourage customer retention.')
                            ->schema([
                                Forms\Components\Repeater::make('billing_cycles')
                                    ->schema([
                                        Forms\Components\TextInput::make('months')
                                            ->numeric()
                                            ->required()
                                            ->label('Months')
                                            ->helperText('e.g., 1, 3, 6, 12'),
                                        Forms\Components\TextInput::make('label')
                                            ->required()
                                            ->label('Label')
                                            ->helperText('e.g., Monthly, 3 Months, 6 Months, Yearly'),
                                        Forms\Components\TextInput::make('discount_percent')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%')
                                            ->label('Discount %')
                                            ->helperText('Enter 0 for no discount, 10 for 10% off, 20 for 20% off, etc.'),
                                        Forms\Components\Toggle::make('recommended')
                                            ->label('Recommended')
                                            ->helperText('Mark as recommended (shows badge on frontend)'),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull()
                                    ->default([
                                        ['months' => 1, 'label' => 'Monthly', 'discount_percent' => 0, 'recommended' => false],
                                        ['months' => 3, 'label' => '3 Months', 'discount_percent' => 5, 'recommended' => false],
                                        ['months' => 6, 'label' => '6 Months', 'discount_percent' => 10, 'recommended' => true],
                                        ['months' => 12, 'label' => 'Yearly', 'discount_percent' => 20, 'recommended' => true],
                                    ])
                                    ->addable()
                                    ->reorderable()
                                    ->collapsible(),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Is Visible')
                            ->default(true)
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon Class/Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Visible'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
