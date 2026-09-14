<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgreementRequestResource\Pages;
use App\Models\Agreement;
use App\Models\AgreementRequest;
use App\Models\AgreementHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgreementRequestResource extends Resource
{
    protected static ?string $model = AgreementRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?string $label = 'Agreement Requests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('project_name')
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending Review',
                                'under_review' => 'Under Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'converted' => 'Converted to Agreement',
                            ])
                            ->required()
                            ->live(),
                    ])->columns(2),

                Forms\Components\Section::make('Project Details (From Client)')
                    ->schema([
                        Forms\Components\RichEditor::make('project_description')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('requirements')
                            ->label('Specific Requirements')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('budget_range')
                            ->disabled(),
                        Forms\Components\TextInput::make('timeline_expectation')
                            ->disabled(),
                        Forms\Components\TextInput::make('preferred_technology')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Admin Review')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Internal Notes / Review Comments')
                            ->rows(4)
                            ->placeholder('Add your review notes here...'),
                        Forms\Components\Select::make('agreement_id')
                            ->label('Linked Agreement')
                            ->relationship('agreement', 'title')
                            ->searchable()
                            ->disabled()
                            ->helperText('This will be auto-populated when converted to agreement'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project_name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'primary' => 'converted',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'converted' => 'Converted',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('budget_range')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('timeline_expectation')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_agreement')
                    ->label('Agreement')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->agreement_id !== null),
                Tables\Columns\TextColumn::make('submitted_at')
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
                        'pending' => 'Pending Review',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'converted' => 'Converted',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // Mark as Under Review
                Tables\Actions\Action::make('markUnderReview')
                    ->label('Mark Under Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (AgreementRequest $record): bool => $record->status === 'pending')
                    ->action(function (AgreementRequest $record) {
                        $record->update([
                            'status' => 'under_review',
                            'reviewed_at' => now(),
                        ]);
                        
                        // Notify client
                        $record->client->notify(new \App\Notifications\AgreementRequestStatusNotification($record, 'under_review'));

                        Notification::make()
                            ->title('Marked as Under Review')
                            ->success()
                            ->send();
                    }),

                // Approve Request
                Tables\Actions\Action::make('approve')
                    ->label('Approve Request')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AgreementRequest $record): bool => in_array($record->status, ['pending', 'under_review']))
                    ->action(function (AgreementRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_at' => now(),
                        ]);
                        
                        // Notify client
                        $record->client->notify(new \App\Notifications\AgreementRequestStatusNotification($record, 'approved'));

                        Notification::make()
                            ->title('Request Approved')
                            ->success()
                            ->send();
                    }),

                // Reject Request
                Tables\Actions\Action::make('reject')
                    ->label('Reject Request')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AgreementRequest $record): bool => $record->status !== 'rejected' && $record->status !== 'converted')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Explain why this request is being rejected...'),
                    ])
                    ->action(function (AgreementRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'admin_notes' => $data['rejection_reason'],
                            'reviewed_at' => now(),
                        ]);
                        
                        // Notify client
                        $record->client->notify(new \App\Notifications\AgreementRequestStatusNotification($record, 'rejected', $data['rejection_reason']));

                        Notification::make()
                            ->title('Request Rejected')
                            ->danger()
                            ->send();
                    }),

                // Convert to Agreement (Main Action)
                Tables\Actions\Action::make('convertToAgreement')
                    ->label('Create Agreement')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(fn (AgreementRequest $record): bool => in_array($record->status, ['pending', 'under_review', 'approved']))
                    ->url(fn (AgreementRequest $record): string => route('filament.admin.resources.agreement-requests.convert', ['record' => $record]))
                    ->openUrlInNewTab(false),
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
            'index' => Pages\ListAgreementRequests::route('/'),
            'create' => Pages\CreateAgreementRequest::route('/create'),
            'edit' => Pages\EditAgreementRequest::route('/{record}/edit'),
            'view' => Pages\ViewAgreementRequest::route('/{record}'),
            'convert' => Pages\ConvertToAgreement::route('/{record}/convert'),
        ];
    }
}
