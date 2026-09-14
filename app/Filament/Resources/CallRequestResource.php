<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CallRequestResource\Pages;
use App\Filament\Resources\CallRequestResource\RelationManagers;
use App\Models\CallRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CallRequestResource extends Resource
{
    protected static ?string $model = CallRequest::class;
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Call Request')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'called' => 'Called',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'called',
                        'danger' => 'cancelled',
                    ]),
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
            'index' => Pages\ListCallRequests::route('/'),
            'create' => Pages\CreateCallRequest::route('/create'),
            'edit' => Pages\EditCallRequest::route('/{record}/edit'),
        ];
    }
}
