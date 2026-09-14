<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectTaskResource\Pages;
use App\Models\ProjectTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectTaskResource extends Resource
{
    protected static ?string $model = ProjectTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Projects';
    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Project Tasks';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Information')
                    ->schema([
                        Forms\Components\Select::make('agreement_id')
                            ->label('Agreement')
                            ->relationship('agreement', 'title')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('milestone_id')
                            ->label('Milestone (Optional)')
                            ->relationship('milestone', 'phase_name')
                            ->searchable(),
                        Forms\Components\TextInput::make('task_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'on_hold' => 'On Hold',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                            ])
                            ->required()
                            ->default('medium'),
                        Forms\Components\TextInput::make('assigned_to')
                            ->label('Assigned To')
                            ->placeholder('e.g., John Doe'),
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('due_date'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('Progress')
                    ->schema([
                        Forms\Components\Slider::make('progress_percentage')
                            ->label('Completion Percentage')
                            ->min(0)
                            ->max(100)
                            ->step(5)
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agreement.title')
                    ->label('Agreement')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'on_hold',
                    ]),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ]),
                Tables\Columns\TextColumn::make('assigned_to')
                    ->searchable(),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn ($state) => $state >= 75 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'on_hold' => 'On Hold',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('markComplete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProjectTask $record): bool => $record->status !== 'completed')
                    ->action(function (ProjectTask $record) {
                        $record->update([
                            'status' => 'completed',
                            'progress_percentage' => 100,
                            'completed_at' => now(),
                        ]);
                        
                        // Notify client
                        $record->agreement->client->notify(new \App\Notifications\TaskCompletedNotification($record));

                        Notification::make()
                            ->title('Task marked as complete')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markComplete')
                        ->label('Mark as Complete')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'completed',
                                    'progress_percentage' => 100,
                                    'completed_at' => now(),
                                ]);
                            }
                            Notification::make()
                                ->title(count($records) . ' tasks marked as complete')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProjectTasks::route('/'),
            'create' => Pages\CreateProjectTask::route('/create'),
            'edit' => Pages\EditProjectTask::route('/{record}/edit'),
        ];
    }
}
