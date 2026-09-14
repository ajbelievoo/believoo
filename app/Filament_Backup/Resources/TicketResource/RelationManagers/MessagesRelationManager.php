<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->default(fn (RelationManager $livewire) => "Namaste,

Thank you for reaching out to Believoo. We have reviewed your inquiry regarding {$livewire->getOwnerRecord()->subject}.

Our team is already looking into the best possible infrastructure/software solution for you. We will get back to you with a detailed proposal shortly.

Best Regards,
AJ
Founder, Believoo")
                    ->required()
                    ->columnSpanFull()
                    ->rows(10),
                Forms\Components\FileUpload::make('attachment')
                    ->disk('public')
                    ->directory('ticket-attachments'),
                Forms\Components\Hidden::make('sender_type')
                    ->default('admin'),
                Forms\Components\Hidden::make('sender_name')
                    ->default(fn () => auth()->user()->name),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('sender_name')
                    ->badge()
                    ->color(fn (string $state): string => $state === auth()->user()->name ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('message')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (\App\Models\TicketMessage $record) {
                        broadcast(new \App\Events\TicketMessageSent($record))->toOthers();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
