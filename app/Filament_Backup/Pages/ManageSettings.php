<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ManageSettings extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?string $title = 'Site Settings';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make('Login System')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Authentication Controls')
                                    ->description('Manage how users register and log in to your site.')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                \Filament\Forms\Components\Toggle::make('enable_registration')
                                                    ->label('Allow Registration')
                                                    ->helperText('Enable or disable new user signups')
                                                    ->default(true),
                                                \Filament\Forms\Components\Toggle::make('enable_email_verification')
                                                    ->label('Email Verification')
                                                    ->helperText('Require users to verify their email')
                                                    ->default(false),
                                                \Filament\Forms\Components\Toggle::make('enable_social_login')
                                                    ->label('Social Login')
                                                    ->helperText('Enable Google Login button')
                                                    ->default(true),
                                            ]),
                                    ]),
                                
                                Section::make('Google API Configuration')
                                    ->description('Enter your Google Cloud Console credentials here.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('google_client_id')->label('Google Client ID'),
                                                TextInput::make('google_client_secret')->password()->revealable()->label('Google Client Secret'),
                                                TextInput::make('google_redirect_url')
                                                    ->label('Google Redirect URL')
                                                    ->url()
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('SEO & Meta')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search Engine Optimization')
                                    ->description('Optimize how your site appears in search results.')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Site Title (SEO)')
                                            ->placeholder('e.g. Believoo - Best IT Solutions'),
                                        TextInput::make('meta_keywords')
                                            ->label('Keywords')
                                            ->placeholder('it, software, agency, believoo'),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(3)
                                            ->placeholder('Enter a brief description of your site for search engines...')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        
                        Tabs\Tab::make('General Branding')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Section::make('Visual Identity')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('site_logo')
                                                    ->label('Site Logo Text')
                                                    ->placeholder('BELIEVOO'),
                                                FileUpload::make('favicon')
                                                    ->image()
                                                    ->directory('settings')
                                                    ->label('Site Favicon'),
                                                Textarea::make('footer_text')
                                                    ->label('Footer Copyright Text')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Contact & Support')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Contact Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('contact_phone')->label('Phone Number')->placeholder('+1 (555) 000-0000'),
                                                TextInput::make('contact_email')->label('Support Email')->email(),
                                                Textarea::make('office_location')
                                                    ->label('Physical Address')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Social Links')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make('Social Media Profiles')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('facebook_url')->label('Facebook URL')->url()->prefix('https://'),
                                                TextInput::make('twitter_url')->label('Twitter URL')->url()->prefix('https://'),
                                                TextInput::make('instagram_url')->label('Instagram URL')->url()->prefix('https://'),
                                                TextInput::make('linkedin_url')->label('LinkedIn URL')->url()->prefix('https://'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Email Delivery (.env Settings)')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Section::make('SMTP Configuration')
                                    ->description('Setup your mail server for notifications (Alternative to .env file).')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('mail_host')->label('SMTP Host')->placeholder('smtp.mailtrap.io'),
                                                TextInput::make('mail_port')->label('SMTP Port')->placeholder('587'),
                                                TextInput::make('mail_username')->label('SMTP Username'),
                                                TextInput::make('mail_password')->label('SMTP Password')->password()->revealable(),
                                                TextInput::make('mail_encryption')->label('Encryption')->placeholder('tls/ssl'),
                                                TextInput::make('mail_from_address')->label('From Email')->email(),
                                                TextInput::make('mail_from_name')->label('From Name')->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Server Management')
                            ->icon('heroicon-o-server')
                            ->schema([
                                Section::make('WHMCS API Configuration')
                                    ->description('Configure WHMCS billing API credentials. Get these from WHMCS Admin > Setup > Staff Management > Manage API Credentials')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('whmcs_base_url')
                                                    ->label('WHMCS Base URL')
                                                    ->placeholder('https://billing.yourdomain.com')
                                                    ->url()
                                                    ->prefixIcon('heroicon-o-link'),
                                                TextInput::make('whmcs_api_identifier')
                                                    ->label('API Identifier')
                                                    ->placeholder('Your WHMCS API Identifier'),
                                                TextInput::make('whmcs_api_secret')
                                                    ->label('API Secret')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Your WHMCS API Secret')
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('Virtualizor API Configuration')
                                    ->description('Configure Virtualizor VPS API credentials. Get these from Virtualizor Admin > Configuration > API Credentials')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('virtualizor_base_url')
                                                    ->label('Virtualizor Base URL')
                                                    ->placeholder('https://virtualizor.yourdomain.com')
                                                    ->url()
                                                    ->prefixIcon('heroicon-o-link'),
                                                TextInput::make('virtualizor_port')
                                                    ->label('API Port')
                                                    ->placeholder('4085')
                                                    ->numeric()
                                                    ->default('4085'),
                                                TextInput::make('virtualizor_api_key')
                                                    ->label('API Key')
                                                    ->placeholder('Your Virtualizor API Key'),
                                                TextInput::make('virtualizor_api_pass')
                                                    ->label('API Pass')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Your Virtualizor API Pass'),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('Dashboard Settings')
                                    ->description('Configure dashboard behavior and auto-refresh settings.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                \Filament\Forms\Components\Toggle::make('server_dashboard_auto_refresh')
                                                    ->label('Auto Refresh')
                                                    ->helperText('Automatically refresh server stats')
                                                    ->default(true),
                                                TextInput::make('server_dashboard_refresh_interval')
                                                    ->label('Refresh Interval (seconds)')
                                                    ->placeholder('30')
                                                    ->numeric()
                                                    ->default('30'),
                                                TextInput::make('whmcs_cache_ttl')
                                                    ->label('WHMCS Cache TTL (seconds)')
                                                    ->placeholder('300')
                                                    ->numeric()
                                                    ->default('300'),
                                                TextInput::make('virtualizor_cache_ttl')
                                                    ->label('Virtualizor Cache TTL (seconds)')
                                                    ->placeholder('60')
                                                    ->numeric()
                                                    ->default('60'),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Payment Gateways')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Razorpay Configuration')
                                    ->description('Configure Razorpay for Indian payments (Cards, UPI, NetBanking).')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                \Filament\Forms\Components\Toggle::make('razorpay_enabled')
                                                    ->label('Enable Razorpay')
                                                    ->helperText('Activate Razorpay payment gateway')
                                                    ->default(false)
                                                    ->live(),
                                                TextInput::make('razorpay_key_id')
                                                    ->label('Razorpay Key ID')
                                                    ->placeholder('rzp_test_xxxxxxxxxxxx')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('razorpay_enabled')),
                                                TextInput::make('razorpay_key_secret')
                                                    ->label('Razorpay Key Secret')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Enter your Razorpay secret key')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('razorpay_enabled'))
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('Cashfree Configuration')
                                    ->description('Configure Cashfree for Indian payments (Cards, UPI, Wallets, EMI).')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                \Filament\Forms\Components\Toggle::make('cashfree_enabled')
                                                    ->label('Enable Cashfree')
                                                    ->helperText('Activate Cashfree payment gateway')
                                                    ->default(false)
                                                    ->live(),
                                                TextInput::make('cashfree_app_id')
                                                    ->label('Cashfree App ID')
                                                    ->placeholder('Enter your Cashfree App ID')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('cashfree_enabled')),
                                                TextInput::make('cashfree_secret_key')
                                                    ->label('Cashfree Secret Key')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Enter your Cashfree secret key')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('cashfree_enabled')),
                                                \Filament\Forms\Components\Select::make('cashfree_mode')
                                                    ->label('Cashfree Mode')
                                                    ->options([
                                                        'sandbox' => 'Sandbox (Test)',
                                                        'production' => 'Production (Live)',
                                                    ])
                                                    ->default('sandbox')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('cashfree_enabled'))
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('PayPal Configuration')
                                    ->description('Configure PayPal for international payments (Cards, PayPal Balance).')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                \Filament\Forms\Components\Toggle::make('paypal_enabled')
                                                    ->label('Enable PayPal')
                                                    ->helperText('Activate PayPal payment gateway')
                                                    ->default(false)
                                                    ->live(),
                                                TextInput::make('paypal_client_id')
                                                    ->label('PayPal Client ID')
                                                    ->placeholder('Enter your PayPal Client ID')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('paypal_enabled')),
                                                TextInput::make('paypal_client_secret')
                                                    ->label('PayPal Client Secret')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Enter your PayPal Client Secret')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('paypal_enabled')),
                                                \Filament\Forms\Components\Select::make('paypal_mode')
                                                    ->label('PayPal Mode')
                                                    ->options([
                                                        'sandbox' => 'Sandbox (Test)',
                                                        'production' => 'Production (Live)',
                                                    ])
                                                    ->default('sandbox')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('paypal_enabled'))
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('PayU Configuration')
                                    ->description('Configure PayU for Indian payments (Cards, UPI, NetBanking, Wallets).')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                \Filament\Forms\Components\Toggle::make('payu_enabled')
                                                    ->label('Enable PayU')
                                                    ->helperText('Activate PayU payment gateway')
                                                    ->default(false)
                                                    ->live(),
                                                TextInput::make('payu_key')
                                                    ->label('PayU Key')
                                                    ->placeholder('Enter your PayU Key')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('payu_enabled')),
                                                TextInput::make('payu_salt')
                                                    ->label('PayU Salt')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Enter your PayU Salt')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('payu_enabled')),
                                                \Filament\Forms\Components\Select::make('payu_mode')
                                                    ->label('PayU Mode')
                                                    ->options([
                                                        'sandbox' => 'Sandbox (Test)',
                                                        'production' => 'Production (Live)',
                                                    ])
                                                    ->default('sandbox')
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('payu_enabled'))
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                // Handle file upload array
                if (is_array($value)) {
                    $value = reset($value);
                }
                
                \App\Models\Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            \Filament\Notifications\Notification::make()
                ->title('Settings updated successfully!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error saving settings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
