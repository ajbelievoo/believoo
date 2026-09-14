<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectDocumentResource\Pages;
use App\Models\ProjectDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectDocumentResource extends Resource
{
    protected static ?string $model = ProjectDocument::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Projects';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\Select::make('agreement_id')
                            ->relationship('agreement', 'project_name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Document File')
                            ->required()
                            ->preserveFilenames()
                            ->disk('public')
                            ->directory('project-documents'),
                        Forms\Components\Select::make('document_type')
                            ->options([
                                'source_code' => 'Source Code',
                                'design' => 'Design Files',
                                'documentation' => 'Documentation',
                                'apk' => 'Mobile App (APK/IPA)',
                                'api_docs' => 'API Documentation',
                                'database' => 'Database Files',
                                'other' => 'Other',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Section::make('Visibility')
                    ->schema([
                        Forms\Components\Select::make('visibility')
                            ->options([
                                'client' => 'Client Only',
                                'admin' => 'Admin Only',
                                'both' => 'Both',
                            ])
                            ->default('both')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('agreement.project_name')
                    ->label('Project')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('document_type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'source_code' => 'Source Code',
                        'design' => 'Design',
                        'documentation' => 'Docs',
                        'apk' => 'Mobile App',
                        'api_docs' => 'API',
                        'database' => 'Database',
                        default => 'Other',
                    }),
                Tables\Columns\TextColumn::make('file_size_formatted')
                    ->label('Size'),
                Tables\Columns\TextColumn::make('download_count')
                    ->label('Downloads'),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->colors([
                        'info' => 'client',
                        'warning' => 'admin',
                        'success' => 'both',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => $record->file_url)
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectDocuments::route('/'),
            'create' => Pages\CreateProjectDocument::route('/create'),
            'edit' => Pages\EditProjectDocument::route('/{record}/edit'),
        ];
    }
}
