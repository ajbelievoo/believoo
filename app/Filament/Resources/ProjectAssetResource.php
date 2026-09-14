<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectAssetResource\Pages;
use App\Filament\Resources\ProjectAssetResource\RelationManagers;
use App\Models\ProjectAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectAssetResource extends Resource
{
    protected static ?string $model = ProjectAsset::class;
    protected static ?string $navigationGroup = 'Projects';
    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('file_path')
                    ->label('File (Photo/PDF/Asset)')
                    ->directory('project-assets')
                    ->acceptedFileTypes(['image/*', 'application/pdf', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                    ->disk('public')
                    ->maxSize(20480) // 20MB
                    ->required()
                    ->openable()
                    ->downloadable()
                    ->previewable(),
                Forms\Components\Select::make('type')
                    ->options([
                        'asset' => 'Project Asset',
                        'invoice' => 'Invoice',
                        'deliverable' => 'Deliverable',
                    ])
                    ->required(),
                Forms\Components\Select::make('uploaded_by')
                    ->relationship('uploader', 'name')
                    ->searchable()
                    ->required()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('file_path')
                    ->label('Type')
                    ->icon(fn (string $state): string => match (pathinfo($state, PATHINFO_EXTENSION)) {
                        'pdf' => 'heroicon-o-document-text',
                        'zip', 'rar' => 'heroicon-o-archive-box',
                        'jpg', 'jpeg', 'png', 'gif' => 'heroicon-o-camera',
                        default => 'heroicon-o-document',
                    })
                    ->color(fn (string $state): string => match (pathinfo($state, PATHINFO_EXTENSION)) {
                        'pdf' => 'danger',
                        'zip', 'rar' => 'warning',
                        'jpg', 'jpeg', 'png', 'gif' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'asset',
                        'success' => 'invoice',
                        'info' => 'deliverable',
                    ]),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListProjectAssets::route('/'),
            'create' => Pages\CreateProjectAsset::route('/create'),
            'edit' => Pages\EditProjectAsset::route('/{record}/edit'),
        ];
    }
}
