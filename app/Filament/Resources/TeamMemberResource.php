<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamMemberResource\Pages;
use App\Filament\Resources\TeamMemberResource\RelationManagers;
use App\Models\TeamMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('role')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->options([
                                'founder' => 'Founder',
                                'co-founder' => 'Co-Founder',
                                'team' => 'Team Member',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('team'),
                    ])->columns(3),
                
                Forms\Components\Section::make('Bio & Media')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->columnSpanFull()
                            ->rows(4),
                        Forms\Components\FileUpload::make('photo_path')
                            ->image()
                            ->previewable()
                            ->directory('team-photos')
                            ->label('Profile Photo'),
                    ]),

                Forms\Components\Section::make('Social Links')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('twitter_url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('instagram_url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('linkedin_url')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Order')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->circular()
                    ->label('Photo'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'founder' => 'danger',
                        'co-founder' => 'warning',
                        'team' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
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
            'index' => Pages\ListTeamMembers::route('/'),
            'create' => Pages\CreateTeamMember::route('/create'),
            'edit' => Pages\EditTeamMember::route('/{record}/edit'),
        ];
    }
}
