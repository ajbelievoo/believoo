<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectFeedbackResource\Pages;
use App\Models\ProjectFeedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectFeedbackResource extends Resource
{
    protected static ?string $model = ProjectFeedback::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Projects';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feedback Details')
                    ->schema([
                        Forms\Components\Select::make('agreement_id')
                            ->relationship('agreement', 'project_name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->disabled(),
                        Forms\Components\Textarea::make('review')
                            ->rows(5)
                            ->disabled(),
                        Forms\Components\TextInput::make('nps_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->label('NPS Score')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Admin Review')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approve for Display'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Feature on Homepage'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agreement.project_name')
                    ->label('Project')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->color('warning'),
                Tables\Columns\TextColumn::make('review')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->review),
                Tables\Columns\BadgeColumn::make('nps_score')
                    ->colors([
                        'success' => fn ($state) => $state >= 9,
                        'warning' => fn ($state) => $state >= 7 && $state < 9,
                        'danger' => fn ($state) => $state < 7,
                    ])
                    ->label('NPS'),
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !$record->is_approved)
                    ->action(function ($record) {
                        $record->approve();
                        Notification::make()->title('Feedback approved')->success()->send();
                    }),
                Tables\Actions\Action::make('feature')
                    ->label('Feature')
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->is_approved && !$record->is_featured)
                    ->action(function ($record) {
                        $record->feature();
                        Notification::make()->title('Feedback featured')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectFeedback::route('/'),
            'view' => Pages\ViewProjectFeedback::route('/{record}'),
        ];
    }
}
