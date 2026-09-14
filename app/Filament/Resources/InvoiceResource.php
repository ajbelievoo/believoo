<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $label = 'Invoices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('invoice_type')
                            ->options([
                                'service' => 'Service Purchase',
                                'hosting' => 'Hosting Service',
                                'manual' => 'Manual Invoice',
                                'custom' => 'Custom Plan',
                            ])
                            ->required()
                            ->default('manual'),
                        Forms\Components\Select::make('order_id')
                            ->label('Related Order')
                            ->options(Order::pluck('order_number', 'id'))
                            ->searchable()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->required()
                            ->placeholder('e.g., VPS Hosting - Pro Plan'),
                        Forms\Components\KeyValue::make('line_items')
                            ->label('Line Items')
                            ->keyLabel('Item')
                            ->valueLabel('Details (price|qty)')
                            ->helperText('Format: Item Name => price|quantity')
                            ->nullable(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('₹')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, $get) {
                                $tax = $get('tax_amount') ?? 0;
                                $set('total_amount', ($state ?? 0) + $tax);
                            }),
                        Forms\Components\TextInput::make('tax_amount')
                            ->numeric()
                            ->prefix('₹')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, $get) {
                                $amount = $get('amount') ?? 0;
                                $set('total_amount', $amount + ($state ?? 0));
                            }),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('₹')
                            ->required()
                            ->helperText('Auto-calculated: Amount + Tax'),
                        Forms\Components\TextInput::make('currency')
                            ->default('INR')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Select::make('payment_gateway')
                            ->options([
                                'manual' => 'Manual/Cash',
                                'razorpay' => 'Razorpay',
                                'cashfree' => 'Cashfree',
                                'admin' => 'Admin Allocation',
                            ])
                            ->nullable(),
                        Forms\Components\TextInput::make('payment_id')
                            ->label('Payment Reference ID'),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At'),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(now()->addDays(7)),
                    ])->columns(2),

                Forms\Components\Section::make('Billing Details')
                    ->schema([
                        Forms\Components\KeyValue::make('billing_details')
                            ->label('Billing Information')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->helperText('e.g., GSTIN, Company Name, Address')
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->placeholder('Internal notes...'),
                        Forms\Components\Select::make('created_by')
                            ->label('Created By (Admin)')
                            ->options(User::where('is_admin', true)->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->default(auth()->id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\BadgeColumn::make('invoice_type')
                    ->colors([
                        'primary' => 'service',
                        'success' => 'hosting',
                        'warning' => 'manual',
                        'info' => 'custom',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                        'primary' => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('payment_gateway')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'razorpay' => 'primary',
                        'cashfree' => 'warning',
                        'manual' => 'gray',
                        'admin' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('invoice_type')
                    ->options([
                        'service' => 'Service Purchase',
                        'hosting' => 'Hosting Service',
                        'manual' => 'Manual Invoice',
                        'custom' => 'Custom Plan',
                    ]),
                Tables\Filters\SelectFilter::make('payment_gateway')
                    ->options([
                        'manual' => 'Manual/Cash',
                        'razorpay' => 'Razorpay',
                        'cashfree' => 'Cashfree',
                        'admin' => 'Admin Allocation',
                    ]),
                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_from'),
                        Forms\Components\DatePicker::make('due_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending')->where('due_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Invoice as Paid')
                    ->modalDescription('Are you sure you want to mark this invoice as paid?')
                    ->visible(fn (Invoice $record): bool => $record->status === 'pending')
                    ->action(function (Invoice $record) {
                        $record->markAsPaid('admin');
                    }),
                Tables\Actions\Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(fn (Invoice $record): string => route('invoice.download', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
