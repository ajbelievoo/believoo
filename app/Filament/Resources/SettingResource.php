<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Filament\Resources\SettingResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Metadata')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn ($record) => $record !== null),
                                Forms\Components\Select::make('group')
                                    ->options([
                                        'General' => 'General',
                                        'SEO' => 'SEO',
                                        'Contact' => 'Contact',
                                        'Social Media' => 'Social Media',
                                        'Mail' => 'Mail',
                                        'Google Social' => 'Google Social',
                                        'Authentication' => 'Authentication',
                                        'Payment' => 'Payment',
                                    ])
                                    ->required()
                                    ->live(),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'text' => 'Short Text',
                                        'image' => 'Image Upload',
                                        'textarea' => 'Long Text/Code',
                                    ])
                                    ->required()
                                    ->live(),
                            ]),
                    ]),

                Forms\Components\Section::make('Setting Value')
                    ->description('Edit the value of this setting.')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'text')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'textarea')
                            ->rows(8)
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('value')
                            ->label('Image')
                            ->image()
                            ->previewable()
                            ->directory('settings')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'image')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting Name')
                    ->description(fn (Setting $record) => $record->group)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Current Value')
                    ->limit(50)
                    ->wrap()
                    ->color('gray'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'text',
                        'success' => 'image',
                        'warning' => 'textarea',
                    ])
                    ->label('Type'),
            ])
            ->defaultGroup('group')
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label('Category')
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'General' => 'General',
                        'SEO' => 'SEO',
                        'Contact' => 'Contact',
                        'Social Media' => 'Social Media',
                        'Mail' => 'Mail',
                        'Google Social' => 'Google Social',
                        'Authentication' => 'Authentication',
                        'Payment' => 'Payment',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->label('Update'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
