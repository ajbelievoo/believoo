<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SupportChat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'filament.pages.support-chat';

    protected static ?string $navigationLabel = 'Live Chat';

    protected static ?string $title = 'Real-Time Support';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;
}
