<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MailboxResource\Pages;
use App\Models\MailDomain;
use App\Models\Mailbox;
use App\Services\MailboxService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MailboxResource extends Resource
{
    protected static ?string $model = Mailbox::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Email';
    protected static ?string $navigationLabel = 'Email Accounts';
    protected static ?string $modelLabel = 'Email Account';
    protected static ?string $pluralModelLabel = 'Email Accounts';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mailbox Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('local_part')
                                    ->label('Email Username')
                                    ->required()
                                    ->maxLength(64)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('help')
                                    ->helperText('Only lowercase letters, numbers, dot, dash, underscore.')
                                    ->visibleOn('create'),
                                Forms\Components\Select::make('domain')
                                    ->label('Domain')
                                    ->required()
                                    ->options(
                                        MailDomain::where('active', 1)->pluck('domain', 'domain')
                                    )
                                    ->default('believoo.com')
                                    ->prefixIcon('heroicon-o-at-symbol')
                                    ->visibleOn('create'),
                            ]),
                        Forms\Components\TextInput::make('username')
                            ->label('Email Address')
                            ->disabled()
                            ->prefixIcon('heroicon-o-envelope')
                            ->visibleOn('edit'),
                        Forms\Components\TextInput::make('full_name')
                            ->label('Display Name')
                            ->maxLength(255)
                            ->placeholder('e.g. Support Team'),
                    ]),

                Forms\Components\Section::make('Password')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->rule('regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/')
                            ->helperText('Min 8 characters with uppercase, lowercase and a number.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Generate strong password')
                                    ->action(fn (Forms\Set $set) => $set('password', MailboxService::generatePassword()))
                            )
                            ->visibleOn('create'),
                        Forms\Components\TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->minLength(8)
                            ->rule('regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/')
                            ->helperText('Leave blank to keep the current password.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Generate strong password')
                                    ->action(fn (Forms\Set $set) => $set('new_password', MailboxService::generatePassword()))
                            )
                            ->visibleOn('edit'),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('quota_gb')
                            ->label('Mailbox Quota (GB)')
                            ->numeric()
                            ->minValue(0.5)
                            ->step(0.5)
                            ->default(5)
                            ->suffix('GB')
                            ->helperText('0 or empty = unlimited')
                            ->formatStateUsing(fn (?Mailbox $record) => $record?->quota ? round($record->quota / 1073741824, 1) : 5),
                        Forms\Components\Toggle::make('active')
                            ->label('Active (can send/receive)')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Email Address')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quota_formatted')
                    ->label('Quota')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('quota', $direction)),
                Tables\Columns\IconColumn::make('active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('domain')
                    ->options(MailDomain::pluck('domain', 'domain')),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Disabled only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('webmail')
                    ->label('Open Webmail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url('https://mail.believoo.com', shouldOpenInNewTab: true),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('This removes the account from the mail server. Existing mail data on disk is preserved but the account can no longer log in.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No email accounts yet')
            ->emptyStateDescription('Create your first mailbox like help@believoo.com')
            ->emptyStateIcon('heroicon-o-envelope');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailboxes::route('/'),
            'create' => Pages\CreateMailbox::route('/create'),
            'edit' => Pages\EditMailbox::route('/{record}/edit'),
        ];
    }
}
