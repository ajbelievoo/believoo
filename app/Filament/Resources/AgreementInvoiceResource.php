<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgreementInvoiceResource\Pages;
use App\Models\AgreementInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgreementInvoiceResource extends Resource
{
    protected static ?string $model = AgreementInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?string $label = 'Agreement Invoices';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\Select::make('agreement_id')
                            ->relationship('agreement', 'project_name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('milestone_id')
                            ->relationship('milestone', 'title')
                            ->searchable(),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('invoice_number')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('invoice_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addDays(7)),
                    ])->columns(2),

                Forms\Components\Section::make('Amounts')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('tax_rate')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                        Forms\Components\TextInput::make('tax_amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Status & Payment')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'razorpay' => 'Razorpay',
                                'cashfree' => 'Cashfree',
                                'bank_transfer' => 'Bank Transfer',
                                'easypaisa' => 'EasyPaisa',
                                'jazzcash' => 'JazzCash',
                                'cash' => 'Cash',
                                'other' => 'Other',
                            ]),
                        Forms\Components\DateTimePicker::make('paid_at'),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                        Forms\Components\Textarea::make('terms_conditions')
                            ->rows(3)
                            ->default('Payment is due within 7 days. Late payments may incur additional charges.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
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
                Tables\Columns\TextColumn::make('milestone.title')
                    ->label('Milestone')
                    ->searchable()
                    ->placeholder('N/A'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'sent',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'warning' => 'cancelled',
                    ])
                    ->icons([
                        'draft' => 'heroicon-m-pencil',
                        'sent' => 'heroicon-m-paper-airplane',
                        'paid' => 'heroicon-m-check-circle',
                        'overdue' => 'heroicon-m-exclamation-triangle',
                        'cancelled' => 'heroicon-m-x-circle',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('USD')
                    ->sortable()
                    ->placeholder('$0.00'),
                Tables\Columns\TextColumn::make('balance_due')
                    ->money('USD')
                    ->sortable()
                    ->color(fn ($record) => $record->balance_due > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $query): Builder => $query->overdue()),
                Tables\Filters\Filter::make('unpaid')
                    ->label('Unpaid Only')
                    ->query(fn (Builder $query): Builder => $query->where('status', '!=', 'paid')->where('status', '!=', 'cancelled')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => route('admin.invoices.download', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('markAsSent')
                    ->label('Mark Sent')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->markAsSent();
                        // Send email notification
                        $record->client->notify(new \App\Notifications\InvoiceSentNotification($record));
                    }),
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['sent', 'overdue']))
                    ->form([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'razorpay' => 'Razorpay',
                                'cashfree' => 'Cashfree',
                                'bank_transfer' => 'Bank Transfer',
                                'easypaisa' => 'EasyPaisa',
                                'jazzcash' => 'JazzCash',
                                'cash' => 'Cash',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsPaid($data['payment_method']);
                        // Send thank you notification
                        $record->client->notify(new \App\Notifications\InvoicePaidNotification($record));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsSent')
                        ->label('Mark as Sent')
                        ->icon('heroicon-m-paper-airplane')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'draft') {
                                    $record->markAsSent();
                                }
                            }
                        }),
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
            'index' => Pages\ListAgreementInvoices::route('/'),
            'create' => Pages\CreateAgreementInvoice::route('/create'),
            'view' => Pages\ViewAgreementInvoice::route('/{record}'),
            'edit' => Pages\EditAgreementInvoice::route('/{record}/edit'),
        ];
    }
}
