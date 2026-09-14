<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgreementResource\Pages;
use App\Models\Agreement;
use App\Models\AgreementHistory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgreementResource extends Resource
{
    protected static ?string $model = Agreement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $label = 'Agreements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Agreement Information')
                    ->schema([
                        Forms\Components\TextInput::make('agreement_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->placeholder('e.g., Software Development Agreement'),
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->options(User::where('is_admin', false)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                $user = User::find($state);
                                if ($user) {
                                    $set('client_name', $user->name);
                                }
                            }),
                        Forms\Components\TextInput::make('client_name')
                            ->required()
                            ->placeholder('Client full name'),
                        Forms\Components\TextInput::make('service_provider_name')
                            ->default('Believoo')
                            ->required(),
                        Forms\Components\TextInput::make('lead_developer')
                            ->default('AJ (Founder, Believoo)')
                            ->placeholder('Lead developer name'),
                    ])->columns(2),

                Forms\Components\Section::make('Project Details')
                    ->schema([
                        Forms\Components\TextInput::make('project_name')
                            ->required()
                            ->placeholder('e.g., ChillnMeet Mobile Ecosystem'),
                        Forms\Components\RichEditor::make('project_overview')
                            ->placeholder('Detailed project overview and scope...'),
                        Forms\Components\RichEditor::make('technical_specs')
                            ->label('Technical Specifications')
                            ->placeholder('Framework, authentication, deployment details...'),
                    ]),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->live()
                            ->placeholder('12000'),
                        Forms\Components\TextInput::make('currency')
                            ->default('USD')
                            ->required(),
                        Forms\Components\TextInput::make('upfront_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->placeholder('1500'),
                        Forms\Components\TextInput::make('timeline_months')
                            ->numeric()
                            ->default(4)
                            ->required(),
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date'),
                    ])->columns(3),

                Forms\Components\Section::make('Work Items & Costing')
                    ->schema([
                        Forms\Components\Repeater::make('workItems')
                            ->relationship('workItems')
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->required()
                                    ->placeholder('e.g., iOS Application Development'),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2)
                                    ->placeholder('Details about this work item...'),
                                Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                Forms\Components\TextInput::make('timeline_days')
                                    ->numeric()
                                    ->placeholder('Days required'),
                                Forms\Components\Hidden::make('sort_order')
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['item_name'] ?? null)
                            ->addActionLabel('Add Work Item'),
                    ]),

                Forms\Components\Section::make('Milestones & Payments')
                    ->schema([
                        Forms\Components\Repeater::make('milestones')
                            ->relationship('milestones')
                            ->schema([
                                Forms\Components\TextInput::make('phase_name')
                                    ->required()
                                    ->placeholder('e.g., Phase 1: UI/UX Design'),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2)
                                    ->placeholder('What will be delivered in this phase...'),
                                Forms\Components\TextInput::make('payment_amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\TextInput::make('timeline_month')
                                    ->numeric()
                                    ->default(1)
                                    ->helperText('Month number (1, 2, 3...)'),
                                Forms\Components\DatePicker::make('due_date'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'completed' => 'Completed',
                                        'paid' => 'Paid',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Forms\Components\Hidden::make('sort_order')
                                    ->default(0),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['phase_name'] ?? null)
                            ->addActionLabel('Add Milestone'),
                    ]),

                Forms\Components\Section::make('Terms & Deliverables')
                    ->schema([
                        Forms\Components\RichEditor::make('payment_terms')
                            ->placeholder('Payment terms description...'),
                        Forms\Components\RichEditor::make('deliverables')
                            ->placeholder('List of deliverables...'),
                        Forms\Components\RichEditor::make('support_terms')
                            ->placeholder('Support and maintenance terms...'),
                        Forms\Components\Textarea::make('additional_terms')
                            ->rows(4)
                            ->placeholder('Any additional terms or conditions...'),
                    ])->columns(1),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent to Client',
                                'viewed' => 'Viewed by Client',
                                'signed' => 'Signed & Active',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required()
                            ->live(),
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['sent', 'viewed', 'signed'])),
                        Forms\Components\DateTimePicker::make('signed_at')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'signed'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agreement_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project_name')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('formatted_total')
                    ->label('Total Amount')
                    ->sortable('total_amount'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'blue' => 'sent',
                        'warning' => 'viewed',
                        'success' => 'signed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('signed_at')
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
                        'draft' => 'Draft',
                        'sent' => 'Sent to Client',
                        'viewed' => 'Viewed by Client',
                        'signed' => 'Signed & Active',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send')
                    ->label('Send to Client')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Agreement $record): bool => $record->status === 'draft')
                    ->action(function (Agreement $record) {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                        
                        AgreementHistory::create([
                            'agreement_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'sent',
                            'description' => 'Agreement sent to client',
                        ]);

                        // Notify client
                        $record->client->notify(new \App\Notifications\AgreementSentNotification($record));

                        Notification::make()
                            ->title('Agreement Sent')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('signAdmin')
                    ->label('Sign as Admin')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Agreement $record): bool => $record->status === 'sent' || $record->status === 'viewed')
                    ->form([
                        Forms\Components\Textarea::make('signature_data')
                            ->label('Admin Signature')
                            ->placeholder('Type your full name as signature')
                            ->required(),
                    ])
                    ->action(function (Agreement $record, array $data) {
                        $record->update([
                            'admin_signature_data' => $data['signature_data'],
                        ]);

                        AgreementHistory::create([
                            'agreement_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'updated',
                            'description' => 'Admin signature added',
                        ]);

                        Notification::make()
                            ->title('Signature Added')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('markSigned')
                    ->label('Mark as Signed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Agreement $record): bool => $record->status !== 'signed' && $record->status !== 'cancelled')
                    ->action(function (Agreement $record) {
                        $record->update([
                            'status' => 'signed',
                            'signed_at' => now(),
                            'client_signed_at' => now(),
                        ]);

                        AgreementHistory::create([
                            'agreement_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'signed',
                            'description' => 'Agreement marked as fully signed',
                        ]);

                        Notification::make()
                            ->title('Agreement Signed')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Agreement $record): bool => $record->status !== 'cancelled')
                    ->action(function (Agreement $record) {
                        $record->update(['status' => 'cancelled']);

                        AgreementHistory::create([
                            'agreement_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'cancelled',
                            'description' => 'Agreement cancelled',
                        ]);

                        Notification::make()
                            ->title('Agreement Cancelled')
                            ->danger()
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
            'index' => Pages\ListAgreements::route('/'),
            'create' => Pages\CreateAgreement::route('/create'),
            'edit' => Pages\EditAgreement::route('/{record}/edit'),
            'view' => Pages\ViewAgreement::route('/{record}'),
        ];
    }
}
