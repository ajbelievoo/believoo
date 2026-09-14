<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageContentResource\Pages;
use App\Filament\Resources\PageContentResource\RelationManagers;
use App\Models\PageContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageContentResource extends Resource
{
    protected static ?string $model = PageContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('page_name')
                    ->options([
                        'home' => 'Home Page',
                        'services' => 'Services Page',
                        'portfolio' => 'Portfolio Page',
                        'contact' => 'Contact Page',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('section_name')
                    ->required()
                    ->placeholder('e.g., hero, why_choose_us, footer'),
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('e.g., hero_title, bento_1_text'),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Plain Text',
                        'rich_text' => 'Rich Text',
                        'image' => 'Image',
                        'json' => 'JSON/Repeater',
                    ])
                    ->required()
                    ->live(),
                Forms\Components\RichEditor::make('value')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'rich_text')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('value')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'text')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('value')
                    ->image()
                    ->previewable()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'image')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('value')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'json')
                    ->helperText('Enter valid JSON data')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('page_name')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('section_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('page_name')
                    ->options([
                        'home' => 'Home Page',
                        'services' => 'Services Page',
                        'portfolio' => 'Portfolio Page',
                    ]),
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
            'index' => Pages\ListPageContents::route('/'),
            'create' => Pages\CreatePageContent::route('/create'),
            'edit' => Pages\EditPageContent::route('/{record}/edit'),
        ];
    }
}
