<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Filament\Resources\MessageResource\RelationManagers;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Support';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Message Details')
                    ->schema([
                        Forms\Components\TextInput::make('sender_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sender_email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('admin_response')
                            ->label('Admin Response')
                            ->placeholder('Type your reply here...')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_read')
                            ->label('Mark as Read')
                            ->default(false)
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sender_email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListMessages::route('/'),
        ];
    }
}
