<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportAgentResource\Pages;
use App\Models\SupportAgent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportAgentResource extends Resource
{
    protected static ?string $model = SupportAgent::class;
    protected static ?string $navigationGroup = 'Support';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Support Team';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team Member')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Linked Admin Login')
                            ->relationship('user', 'name', fn ($q) => $q->where('is_admin', true))
                            ->searchable()
                            ->helperText('Link this member to an admin account so they can reply from Live Chat.')
                            ->nullable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Real Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('display_name')
                            ->label('Display Name (shown to clients)')
                            ->helperText('e.g. "Priya - Believoo Support". Defaults to real name.')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')->email()->maxLength(255)
                            ->helperText('Agent uses this email to log in at /agent/login'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->helperText('Leave blank to keep current password. Agent logs in with email + this password.')
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')->tel()->maxLength(255),
                        Forms\Components\TextInput::make('max_chats')
                            ->numeric()->default(3)->minValue(1)->maxValue(20)
                            ->helperText('Max simultaneous live chats'),
                        Forms\Components\Toggle::make('is_active')->default(true),
                        Forms\Components\Toggle::make('is_online')
                            ->label('Online (available for live chat)')
                            ->helperText('Automatically turns off if the agent is inactive for 5 minutes.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Agent')
                    ->getStateUsing(fn ($r) => $r->display_name ?: $r->name)
                    ->searchable(['name', 'display_name']),
                Tables\Columns\TextColumn::make('user.name')->label('Login')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_online')->boolean()->label('Online'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('active_chats')->label('Chats')->badge()
                    ->color(fn ($r) => $r->active_chats >= $r->max_chats ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('total_chats')->label('Total'),
                Tables\Columns\TextColumn::make('late_replies')->label('Late Replies')->badge()->color('warning'),
                Tables\Columns\TextColumn::make('unpermitted_closes')->label('ZTP Flags')->badge()->color('danger'),
                Tables\Columns\TextColumn::make('avg_rating')->label('Rating')->formatStateUsing(fn ($s) => $s ? $s . ' ★' : '—'),
                Tables\Columns\TextColumn::make('last_seen_at')->since()->label('Last Seen')->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_online')->label('Online'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportAgents::route('/'),
            'create' => Pages\CreateSupportAgent::route('/create'),
            'edit' => Pages\EditSupportAgent::route('/{record}/edit'),
        ];
    }
}
