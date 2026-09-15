<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RequireTwoFactor;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('panel')
            ->brandName('Believoo Admin')
            ->brandLogo(fn () => '<div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center"><span class="text-black font-black text-lg">B</span></div><span class="font-black text-lg tracking-tight">ADMIN</span></div>')
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => asset('favicon.ico'))
            ->login()
            ->colors([
                'primary' => Color::hex('#00d4ff'),
                'gray'    => Color::hex('#1a1a2e'),
                'danger'  => Color::Red,
                'info'    => Color::hex('#0ea5e9'),
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Figtree')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Sales')->icon('heroicon-o-currency-dollar'),
                NavigationGroup::make('Services')->icon('heroicon-o-signal'),
                NavigationGroup::make('Projects')->icon('heroicon-o-cpu-chip'),
                NavigationGroup::make('Billing')->icon('heroicon-o-receipt-percent'),
                NavigationGroup::make('Content')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Support')->icon('heroicon-o-chat-bubble-left-right'),
                NavigationGroup::make('Settings')->icon('heroicon-o-cog-6-tooth'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode()
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->renderHook(
                'panels::head.start',
                fn (): string => '
                <style>
                    /* Dark mode styles - only apply when dark mode is active */
                    .dark .fi-simple-layout,
                    .dark .fi-login-page,
                    .dark .fi-page,
                    .dark [class*="simple-layout"] {
                        background-color: #050505;
                        background: #050505;
                    }
                    
                    /* Target all possible card containers in dark mode */
                    .dark .fi-simple-layout .fi-simple-main,
                    .dark .fi-simple-layout .fi-simple-main-ctn,
                    .dark .fi-simple-layout main,
                    .dark .fi-login-page form,
                    .dark .fi-login-page .fi-form,
                    .dark .fi-simple-layout [class*="main"],
                    .dark .fi-simple-layout [class*="card"] {
                        background: linear-gradient(135deg, rgba(17, 17, 17, 0.98) 0%, rgba(10, 10, 10, 0.99) 100%);
                        background-color: #111111;
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        border-radius: 1.5rem;
                        box-shadow: 0 32px 64px rgba(0, 0, 0, 0.6);
                    }
                    
                    /* White text in dark mode */
                    .dark .fi-simple-layout,
                    .dark .fi-simple-layout h1,
                    .dark .fi-simple-layout h2,
                    .dark .fi-simple-layout h3,
                    .dark .fi-simple-layout p,
                    .dark .fi-simple-layout span,
                    .dark .fi-simple-layout label,
                    .dark .fi-simple-layout .fi-header-heading,
                    .dark .fi-simple-layout .fi-simple-header {
                        color: #ffffff;
                    }
                    
                    /* Dark inputs in dark mode */
                    .dark .fi-simple-layout input,
                    .dark .fi-simple-layout .fi-input,
                    .dark .fi-simple-layout [type="email"],
                    .dark .fi-simple-layout [type="password"] {
                        background-color: rgba(0, 0, 0, 0.3);
                        border-color: rgba(255, 255, 255, 0.15);
                        color: #ffffff;
                    }
                    
                    .dark .fi-simple-layout input:focus,
                    .dark .fi-simple-layout .fi-input:focus {
                        border-color: #00b7ff;
                        box-shadow: 0 0 0 2px rgba(0, 183, 255, 0.2);
                    }
                    
                    /* Blue gradient button in dark mode */
                    .dark .fi-simple-layout button,
                    .dark .fi-simple-layout [type="submit"],
                    .dark .fi-simple-layout .fi-btn-primary {
                        background: linear-gradient(135deg, #00b7ff 0%, #0099ff 100%);
                        color: #000000;
                        font-weight: 900;
                        border: none;
                    }
                    
                    /* Links in dark mode */
                    .dark .fi-simple-layout a {
                        color: #00b7ff;
                    }
                    
                    /* Animated orbs in background - dark mode only */
                    .dark .fi-simple-layout::before {
                        content: "";
                        position: fixed;
                        top: 10%;
                        right: 10%;
                        width: 500px;
                        height: 500px;
                        background: radial-gradient(circle, rgba(0, 183, 255, 0.25) 0%, transparent 70%);
                        filter: blur(60px);
                        animation: orb1 15s ease-in-out infinite;
                        pointer-events: none;
                        z-index: 0;
                    }
                    
                    @keyframes orb1 {
                        0%, 100% { transform: translate(0, 0); }
                        50% { transform: translate(-50px, 50px); }
                    }
                </style>
                ',
            )
            ->renderHook(
                'panels::topbar.start',
                fn (): string => '<a href="' . route('admin.dashboard') . '" class="fi-btn fi-btn-size-md fi-color-primary fi-btn-outlined hidden sm:flex items-center gap-2 px-4 py-2 rounded-lg border border-custom-500 text-custom-600 hover:bg-custom-50 dark:text-custom-400 dark:border-custom-400 dark:hover:bg-custom-900/30 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> Back to Custom Admin</a>'
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => '<div style="display:none">' . \Illuminate\Support\Facades\Blade::render("@livewire('navbar-notifications')") . '</div>'
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => "
                    <script>
                        (function() {
                            const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                            const playAudio = () => audio.play().catch(e => console.log('Audio play blocked'));
                            window.addEventListener('notification-received', playAudio);
                            window.addEventListener('play-notification-sound', playAudio);
                            window.addEventListener('play-ping-sound', playAudio);
                        })();
                    </script>
                ",
            )
            ->favicon(fn () => asset('storage/' . (\App\Models\Setting::where('key', 'favicon')->first()?->value ?? 'favicon.ico')))
            // Filament resources auto-discovery enabled
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                AdminMiddleware::class,
                RequireTwoFactor::class,
            ]);
    }
}
