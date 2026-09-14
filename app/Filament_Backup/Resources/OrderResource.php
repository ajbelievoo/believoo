<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\HostingProvisioningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(),
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->options(Service::pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('service_name')
                            ->required(),
                        Forms\Components\TextInput::make('tier_name')
                            ->placeholder('e.g., Basic, Pro, Enterprise'),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('₹')
                            ->label('Subtotal (Without GST)'),
                        Forms\Components\TextInput::make('gst_amount')
                            ->numeric()
                            ->prefix('₹')
                            ->label('GST Amount (18%)'),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('₹')
                            ->required()
                            ->label('Total (Incl. GST)'),
                        Forms\Components\TextInput::make('currency')
                            ->default('INR')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Select::make('payment_gateway')
                            ->options([
                                'razorpay' => 'Razorpay',
                                'cashfree' => 'Cashfree',
                            ]),
                        Forms\Components\TextInput::make('payment_id')
                            ->label('Payment ID'),
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction ID'),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                        Forms\Components\KeyValue::make('payment_response')
                            ->label('Payment Response Data')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('tier_name')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('INR')
                    ->sortable()
                    ->label('Total'),
                Tables\Columns\TextColumn::make('gst_amount')
                    ->money('INR')
                    ->sortable()
                    ->label('GST')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                        'primary' => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('payment_gateway')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'razorpay' => 'primary',
                        'cashfree' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_gateway')
                    ->options([
                        'razorpay' => 'Razorpay',
                        'cashfree' => 'Cashfree',
                    ]),
                Tables\Filters\Filter::make('paid_at')
                    ->form([
                        Forms\Components\DatePicker::make('paid_from'),
                        Forms\Components\DatePicker::make('paid_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['paid_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '>=', $date),
                            )
                            ->when(
                                $data['paid_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Orders as Paid')
                        ->modalDescription('Are you sure you want to mark selected orders as paid? This will create hosting and invoices.')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!$record->isPaid()) {
                                    $record->markAsPaid('admin', 'MANUAL-' . time() . '-' . $record->id);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("$count orders marked as paid")
                                ->body('Hosting and invoices have been created automatically.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('provisionHosting')
                        ->label('Provision Hosting Only')
                        ->icon('heroicon-o-server')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->is_admin)
                        ->action(function ($records) {
                            $count = 0;
                            $service = app(HostingProvisioningService::class);
                            foreach ($records as $record) {
                                if ($record->isPaid() && !$record->hosting) {
                                    $service->provisionFromOrder($record);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("$count hostings provisioned")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('quickOrder')
                    ->label('Quick Create + Provision')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->visible(fn () => auth()->user()?->is_admin)
                    ->form([
                        Forms\Components\Section::make('Customer & Service')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Customer')
                                    ->options(User::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('service_id')
                                    ->label('Service')
                                    ->options(Service::pluck('title', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('tier_name')
                                    ->placeholder('e.g., Basic, Pro, Enterprise'),
                                Forms\Components\TextInput::make('billing_months')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                            ])->columns(2),
                        Forms\Components\Section::make('Payment')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->options(['pending' => 'Pending', 'paid' => 'Paid'])
                                    ->default('paid')
                                    ->required(),
                                Forms\Components\Toggle::make('auto_provision')
                                    ->label('Auto-provision hosting')
                                    ->default(true)
                                    ->helperText('Automatically create hosting if order is paid'),
                            ])->columns(3),
                    ])
                    ->action(function (array $data) {
                        $service = Service::find($data['service_id']);
                        $order = Order::create([
                            'user_id' => $data['user_id'],
                            'service_id' => $data['service_id'],
                            'service_name' => $service->title,
                            'tier_name' => $data['tier_name'],
                            'billing_months' => $data['billing_months'],
                            'amount' => $data['amount'],
                            'currency' => 'INR',
                            'status' => $data['status'],
                            'payment_gateway' => 'admin',
                            'paid_at' => $data['status'] === 'paid' ? now() : null,
                        ]);

                        if ($data['status'] === 'paid' && ($data['auto_provision'] ?? true)) {
                            $order->markAsPaid('admin', 'ADMIN-' . time());
                        }

                        Notification::make()
                            ->title('Order created successfully!')
                            ->body('Order #' . $order->order_number . ' created.')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
