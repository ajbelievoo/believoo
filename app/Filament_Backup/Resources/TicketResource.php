<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationGroup = 'Support';
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?int $navigationSort = 2;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Ticket Info')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('ticket_id')
                                    ->label('Ticket ID')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('name'),
                                TextEntry::make('email'),
                                TextEntry::make('subject'),
                                TextEntry::make('priority')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'low' => 'gray',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        'urgent' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'open' => 'danger',
                                        'in_progress' => 'warning',
                                        'resolved' => 'success',
                                        'closed' => 'gray',
                                        default => 'gray',
                                    }),
                            ]),
                        TextEntry::make('attachments')
                            ->label('Files Attached')
                            ->listWithLineBreaks()
                            ->limitList(3)
                            ->expandableLimitedList()
                            ->url(fn ($state) => $state ? Storage::url($state) : null)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record->attachments)),
                    ]),

                Section::make('Messages')
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('reply')
                            ->label('Reply to Ticket')
                            ->icon('heroicon-o-chat-bubble-left')
                            ->color('primary')
                            ->form([
                                Forms\Components\Select::make('template')
                                    ->label('Use Template')
                                    ->options([
                                        'default' => 'Default Reply',
                                        'close' => 'Close Ticket',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Ticket $record) {
                                        if ($state === 'default') {
                                            $set('message', "Namaste,

Thank you for reaching out to Believoo. We have reviewed your inquiry regarding {$record->subject}.

Our team is already looking into the best possible infrastructure/software solution for you. We will get back to you with a detailed proposal shortly.

Best Regards,
AJ
Founder, Believoo");
                                        } elseif ($state === 'close') {
                                            $set('message', "Namaste,

We hope you are satisfied with our support. We are now closing this ticket as the issue has been addressed.

If you have any further questions, feel free to open a new ticket or reach out to us.

Best Regards,
Team Believoo");
                                            $set('status_to_set', 'closed');
                                        }
                                    }),
                                Forms\Components\Textarea::make('message')
                                    ->label('Your Message')
                                    ->default(fn (Ticket $record) => "Namaste,

Thank you for reaching out to Believoo. We have reviewed your inquiry regarding {$record->subject}.

Our team is already looking into the best possible infrastructure/software solution for you. We will get back to you with a detailed proposal shortly.

Best Regards,
AJ
Founder, Believoo")
                                    ->required_without('attachment')
                                    ->rows(10),
                                Forms\Components\Hidden::make('status_to_set')
                                    ->default('open'),
                                Forms\Components\FileUpload::make('attachment')
                                    ->disk('public')
                                    ->directory('ticket-attachments'),
                            ])
                            ->action(function (Ticket $record, array $data) {
                                $msg = $record->messages()->create([
                                    'sender_type' => 'admin',
                                    'sender_name' => auth()->user()->name,
                                    'message' => $data['message'],
                                    'attachment' => $data['attachment'] ?? null,
                                ]);

                                try {
                                    broadcast(new \App\Events\TicketMessageSent($msg))->toOthers();
                                } catch (\Exception $e) {
                                    \Log::error('Ticket broadcast from admin failed: ' . $e->getMessage());
                                }

                                // Update status based on action or default to 'open' if it was 'in_progress' and team viewed it
                                $newStatus = $data['status_to_set'] ?? 'open';
                                $record->update(['status' => $newStatus]);

                                // Notify Client via Database and Email
                                $client = \App\Models\User::where('email', $record->email)->first();
                                if ($client) {
                                    $client->notify(new \App\Notifications\TicketReplyNotification($record, $msg));
                                } else {
                                    // If no user account, we still want to send email if possible
                                    // In Laravel, you can use Notification::route('mail', 'email@example.com')->notify(...)
                                    \Illuminate\Support\Facades\Notification::route('mail', $record->email)
                                        ->notify(new \App\Notifications\TicketReplyNotification($record, $msg));
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Reply sent and Status Updated to ' . ucfirst($newStatus))
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        ViewEntry::make('messages')
                            ->view('filament.resources.tickets.messages-history')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Info')
                    ->schema([
                        Forms\Components\TextInput::make('ticket_id')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Ticket ID'),
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('subject')
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'in_progress' => 'In Progress',
                                'resolved' => 'Resolved',
                                'closed' => 'Closed',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('category'),
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Attachments (Photos/PDFs)')
                            ->multiple()
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->disk('public')
                            ->directory('ticket-attachments')
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->searchable()
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => ['high', 'urgent'],
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'open',
                        'warning' => 'in_progress',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
