<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Exchange Rates';
    protected static ?string $modelLabel = 'Exchange Rate';
    protected static ?string $pluralModelLabel = 'Exchange Rates';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Currency Pair')
                    ->schema([
                        Forms\Components\TextInput::make('base_currency')
                            ->required()
                            ->maxLength(3)
                            ->default('USD')
                            ->disabled()
                            ->label('From (Base)'),
                        Forms\Components\TextInput::make('target_currency')
                            ->required()
                            ->maxLength(3)
                            ->default('INR')
                            ->disabled()
                            ->label('To (Target)'),
                    ])->columns(2),

                Forms\Components\Section::make('Exchange Rate')
                    ->schema([
                        Forms\Components\TextInput::make('rate')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->suffix('INR per 1 USD')
                            ->label('Current Rate')
                            ->helperText('Enter how many INR = 1 USD (e.g., 83.00)'),
                        Forms\Components\Placeholder::make('current_value')
                            ->label('Example Conversion')
                            ->content(function (Forms\Get $get) {
                                $rate = $get('rate') ?: 83.00;
                                return "1 USD = ₹" . number_format($rate, 2) . " INR\n$10 USD = ₹" . number_format($rate * 10, 0) . " INR\n$100 USD = ₹" . number_format($rate * 100, 0) . " INR";
                            }),
                    ])->columns(1),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\DateTimePicker::make('fetched_at')
                            ->label('Last Updated')
                            ->default(now())
                            ->disabled(),
                        Forms\Components\TextInput::make('source')
                            ->maxLength(255)
                            ->default('manual')
                            ->disabled()
                            ->label('Source'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('base_currency')
                    ->label('From')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('target_currency')
                    ->label('To')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->money('INR', true)
                    ->sortable()
                    ->description(function (ExchangeRate $record) {
                        return "1 {$record->base_currency} = " . number_format($record->rate, 2) . " {$record->target_currency}";
                    }),
                Tables\Columns\TextColumn::make('fetched_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'warning',
                        'exchangerate-api' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('fetched_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('usd_to_inr')
                    ->label('USD to INR')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('base_currency', 'USD')
                        ->where('target_currency', 'INR')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (ExchangeRate $record) => $record->base_currency === 'USD' && $record->target_currency === 'INR'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('fetch_latest')
                    ->label('Fetch Live Rate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function () {
                        $service = new \App\Services\CurrencyService();
                        $success = $service->fetchLatestRates();
                        
                        if ($success) {
                            return redirect()->back()->with('success', 'Exchange rates updated successfully!');
                        } else {
                            return redirect()->back()->with('error', 'Failed to fetch exchange rates. Please update manually.');
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Fetch Live Exchange Rate')
                    ->modalDescription('This will fetch the latest USD to INR rate from the API. Continue?')
                    ->modalSubmitActionLabel('Yes, Fetch Rate'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit' => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Limit to only one USD to INR record for simplicity
        return !ExchangeRate::where('base_currency', 'USD')
            ->where('target_currency', 'INR')
            ->exists();
    }
}
