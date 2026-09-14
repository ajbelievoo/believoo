<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortfolioResource\Pages;
use App\Filament\Resources\PortfolioResource\RelationManagers;
use App\Models\Portfolio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PortfolioResource extends Resource
{
    protected static ?string $model = Portfolio::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Overview')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->previewable()
                            ->directory('portfolio')
                            ->required(),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Case Study Details')
                    ->schema([
                        Forms\Components\RichEditor::make('problem')
                            ->label('The Problem')
                            ->placeholder('Describe the challenges the client faced...')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('solution')
                            ->label('Our Solution')
                            ->placeholder('Describe how we solved the problem...')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('result')
                            ->label('The Result')
                            ->placeholder('Describe the outcome and impact...')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('tech_stack')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->placeholder('e.g., React, Laravel, AWS')
                                    ->required(),
                                Forms\Components\TextInput::make('icon')
                                    ->placeholder('e.g., fa-brands fa-react'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_visible')
                            ->label('Show on Website')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean()
                    ->label('Visible'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListPortfolios::route('/'),
            'create' => Pages\CreatePortfolio::route('/create'),
            'edit' => Pages\EditPortfolio::route('/{record}/edit'),
        ];
    }
}
