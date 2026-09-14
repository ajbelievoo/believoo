<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserHostingResource\Pages;
use App\Models\UserHosting;
use App\Models\Service;
use App\Models\User;
use App\Services\HostingProvisioningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class UserHostingResource extends Resource
{
    protected static ?string $model = UserHosting::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $label = 'User Hostings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->label('Client'),
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->label('Order (Optional - for linked purchases)')
                            ->helperText('Leave empty for manual/manual allocations')
                            ->nullable(),
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'title')
                            ->required()
                            ->searchable()
                            ->label('Service')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $service = \App\Models\Service::find($state);
                                if ($service) {
                                    $set('plan_name', $service->title);
                                    $set('price', $service->price);
                                    $hostingType = strtolower($service->category ?? '');
                                    $type = match($hostingType) {
                                        'vps' => 'vps',
                                        'dedicated' => 'dedicated',
                                        'cloud' => 'cloud',
                                        'shared' => 'shared',
                                        default => 'shared',
                                    };
                                    $set('hosting_type', $type);
                                }
                            }),
                        Forms\Components\Select::make('plan_name')
                            ->label('Plan Name')
                            ->options(function (callable $get) {
                                $hostingType = $get('hosting_type');
                                
                                $query = \App\Models\Service::where('is_active', true);
                                
                                // Filter by hosting type based on title keywords
                                if ($hostingType) {
                                    $query->where(function($q) use ($hostingType) {
                                        $q->where('title', 'like', '%' . $hostingType . '%')
                                          ->orWhere('slug', 'like', '%' . $hostingType . '%');
                                    });
                                }
                                
                                // Only show hosting category services
                                $query->where('category', 'Hosting');
                                
                                return $query->pluck('title', 'title');
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Select a plan')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $service = \App\Models\Service::where('title', $state)->first();
                                if ($service) {
                                    $set('service_id', $service->id);
                                    $set('price', $service->price);
                                    $hostingType = strtolower($service->category ?? '');
                                    $type = match($hostingType) {
                                        'vps' => 'vps',
                                        'dedicated' => 'dedicated',
                                        'cloud' => 'cloud',
                                        'shared' => 'shared',
                                        default => 'shared',
                                    };
                                    $set('hosting_type', $type);
                                }
                            }),
                        Forms\Components\Select::make('hosting_type')
                            ->required()
                            ->options([
                                'shared' => 'Shared Hosting',
                                'vps' => 'VPS',
                                'dedicated' => 'Dedicated Server',
                                'cloud' => 'Cloud Hosting',
                            ])
                            ->label('Hosting Type'),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->default('pending')
                            ->options([
                                'pending' => 'Pending (Setup in Progress)',
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->label('Status'),
                    ])->columns(2),

                Forms\Components\Section::make('Server Specifications (OVH Style)')
                    ->description('Enter server specs manually from OVH dashboard')
                    ->schema([
                        Forms\Components\TextInput::make('cpu_cores')
                            ->numeric()
                            ->placeholder('e.g., 6')
                            ->suffix('vCores')
                            ->label('CPU Cores'),
                        Forms\Components\TextInput::make('ram_size')
                            ->placeholder('e.g., 12 GB')
                            ->label('RAM Size'),
                        Forms\Components\TextInput::make('storage_size')
                            ->placeholder('e.g., 100 GB NVMe')
                            ->label('Storage Size'),
                        Forms\Components\TextInput::make('storage_used')
                            ->placeholder('e.g., 45 GB')
                            ->label('Storage Used (for progress bar)'),
                        Forms\Components\TextInput::make('bandwidth')
                            ->placeholder('e.g., 1 TB')
                            ->label('Bandwidth Limit'),
                        Forms\Components\TextInput::make('bandwidth_used')
                            ->placeholder('e.g., 250 GB')
                            ->label('Bandwidth Used'),
                        Forms\Components\TextInput::make('os_name')
                            ->placeholder('e.g., Ubuntu 22.04 LTS')
                            ->label('Operating System'),
                        Forms\Components\TextInput::make('datacenter_location')
                            ->placeholder('e.g., Singapore (SGP)')
                            ->label('Datacenter Location'),
                        Forms\Components\TextInput::make('server_hostname')
                            ->placeholder('e.g., vps-1ce75d7.vps.ovh.ca')
                            ->label('Server Hostname'),
                    ])->columns(2),

                Forms\Components\Section::make('Network & Access Details')
                    ->schema([
                        Forms\Components\TextInput::make('server_ip')
                            ->placeholder('e.g., 139.99.43.203')
                            ->label('IPv4 Address'),
                        Forms\Components\TextInput::make('ipv6')
                            ->placeholder('e.g., 2402:1100:8000:800::43cf')
                            ->label('IPv6 Address'),
                        Forms\Components\TextInput::make('gateway')
                            ->placeholder('e.g., 139.99.43.1')
                            ->label('Gateway'),
                        Forms\Components\TextInput::make('primary_domain')
                            ->placeholder('e.g., cloud.blievoo.com')
                            ->label('Primary Domain'),
                        Forms\Components\TextInput::make('root_password')
                            ->password()
                            ->revealable()
                            ->placeholder('Root/Admin Password')
                            ->label('Root Password'),
                        Forms\Components\TextInput::make('control_panel_url')
                            ->placeholder('e.g., https://cpanel.example.com')
                            ->label('Control Panel URL'),
                        Forms\Components\TextInput::make('control_panel_username')
                            ->placeholder('e.g., admin')
                            ->label('Control Panel Username'),
                        Forms\Components\TextInput::make('control_panel_password')
                            ->password()
                            ->revealable()
                            ->label('Control Panel Password'),
                    ])->columns(2),

                Forms\Components\Section::make('Billing & Dates')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('₹')
                            ->required()
                            ->label('Price'),
                        Forms\Components\Select::make('billing_cycle')
                            ->required()
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'half_yearly' => 'Half Yearly',
                                'yearly' => 'Yearly',
                            ])
                            ->label('Billing Cycle'),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Start Date'),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->required()
                            ->label('Expiry Date'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Settings')
                    ->schema([
                        Forms\Components\Select::make('boot_mode')
                            ->default('LOCAL')
                            ->options([
                                'LOCAL' => 'LOCAL (Normal Boot)',
                                'RESCUE' => 'RESCUE (Recovery Mode)',
                            ])
                            ->label('Boot Mode'),
                        Forms\Components\Toggle::make('automated_backup')
                            ->label('Automated Backup Enabled'),
                        Forms\Components\TextInput::make('backup_status')
                            ->default('Disabled')
                            ->placeholder('e.g., Active, Disabled, Snapshot Available')
                            ->label('Backup Status'),
                        Forms\Components\TextInput::make('uptime_percentage')
                            ->numeric()
                            ->default(99.99)
                            ->suffix('%')
                            ->label('Uptime Percentage'),
                        Forms\Components\DateTimePicker::make('last_reboot')
                            ->label('Last Reboot Time'),
                        Forms\Components\Toggle::make('ssl_enabled')
                            ->default(true)
                            ->label('SSL Enabled'),
                        Forms\Components\DatePicker::make('ssl_expiry')
                            ->label('SSL Expiry Date'),
                    ])->columns(3),

                Forms\Components\Section::make('Admin Notes')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->rows(3)
                            ->placeholder('Internal notes for admin...')
                            ->label('Admin Notes'),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('hosting_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'shared',
                        'success' => 'vps',
                        'warning' => 'dedicated',
                        'info' => 'cloud',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => ['suspended', 'expired'],
                        'gray' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('server_ip')
                    ->label('IP Address')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cpu_cores')
                    ->label('CPU')
                    ->suffix(' vCores'),
                Tables\Columns\TextColumn::make('ram_size')
                    ->label('RAM'),
                Tables\Columns\TextColumn::make('storage_size')
                    ->label('Storage'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('isActive')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isActive()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\SelectFilter::make('hosting_type')
                    ->options([
                        'shared' => 'Shared Hosting',
                        'vps' => 'VPS',
                        'dedicated' => 'Dedicated Server',
                        'cloud' => 'Cloud Hosting',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (UserHosting $record): bool => $record->status === 'pending')
                    ->action(function (UserHosting $record) {
                        $record->update(['status' => 'active']);
                        Notification::make()->title('Hosting activated successfully')->success()->send();
                    }),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (UserHosting $record): bool => $record->status === 'active')
                    ->action(function (UserHosting $record) {
                        $record->update(['status' => 'suspended']);
                        Notification::make()->title('Hosting suspended')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkActivate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'active']);
                            }
                            Notification::make()->title(count($records) . ' hostings activated')->success()->send();
                        }),
                    Tables\Actions\BulkAction::make('bulkSuspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'suspended']);
                            }
                            Notification::make()->title(count($records) . ' hostings suspended')->warning()->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('quickCreate')
                    ->label('Quick Create + Invoice')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->form([
                        Forms\Components\Section::make('Customer & Plan')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Customer')
                                    ->options(User::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('service_id')
                                    ->label('Service (Optional)')
                                    ->options(Service::pluck('title', 'id'))
                                    ->searchable()
                                    ->nullable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $service = Service::find($state);
                                        if ($service) {
                                            $set('plan_name', $service->title);
                                            $set('price', $service->price);
                                            $hostingType = strtolower($service->category ?? '');
                                            $type = match($hostingType) {
                                                'vps' => 'vps',
                                                'dedicated' => 'dedicated',
                                                'cloud' => 'cloud',
                                                'shared' => 'shared',
                                                default => 'shared',
                                            };
                                            $set('hosting_type', $type);
                                        }
                                    }),
                                Forms\Components\Select::make('plan_name')
                                    ->label('Plan Name')
                                    ->options(function (callable $get) {
                                        $hostingType = $get('hosting_type');
                                        
                                        $query = Service::where('is_active', true);
                                        
                                        // Filter by hosting type based on title keywords
                                        if ($hostingType) {
                                            $query->where(function($q) use ($hostingType) {
                                                $q->where('title', 'like', '%' . $hostingType . '%')
                                                  ->orWhere('slug', 'like', '%' . $hostingType . '%');
                                            });
                                        }
                                        
                                        // Only show hosting category services
                                        $query->where('category', 'Hosting');
                                        
                                        return $query->pluck('title', 'title');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select a plan')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $service = Service::where('title', $state)->first();
                                        if ($service) {
                                            $set('service_id', $service->id);
                                            $set('price', $service->price);
                                            $hostingType = strtolower($service->category ?? '');
                                            $type = match($hostingType) {
                                                'vps' => 'vps',
                                                'dedicated' => 'dedicated',
                                                'cloud' => 'cloud',
                                                'shared' => 'shared',
                                                default => 'shared',
                                            };
                                            $set('hosting_type', $type);
                                        }
                                    }),
                                Forms\Components\Select::make('hosting_type')
                                    ->required()
                                    ->default('shared')
                                    ->options([
                                        'shared' => 'Shared Hosting',
                                        'vps' => 'VPS',
                                        'dedicated' => 'Dedicated Server',
                                        'cloud' => 'Cloud Hosting',
                                    ]),
                            ])->columns(2),
                        
                        Forms\Components\Section::make('Billing')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->required()
                                    ->default(0),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(0),
                                Forms\Components\Select::make('billing_cycle')
                                    ->required()
                                    ->default('monthly')
                                    ->options([
                                        'monthly' => 'Monthly',
                                        'quarterly' => 'Quarterly',
                                        'half_yearly' => 'Half Yearly',
                                        'yearly' => 'Yearly',
                                    ]),
                                Forms\Components\TextInput::make('billing_months')
                                    ->numeric()
                                    ->default(1)
                                    ->helperText('Number of months for this billing period'),
                                Forms\Components\DatePicker::make('start_date')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->required()
                                    ->default(now()->addMonth()),
                            ])->columns(3),
                        
                        Forms\Components\Section::make('Server Details (Optional)')
                            ->schema([
                                Forms\Components\TextInput::make('server_ip')
                                    ->placeholder('e.g., 192.168.1.1'),
                                Forms\Components\TextInput::make('server_hostname')
                                    ->placeholder('e.g., vps.example.com'),
                                Forms\Components\TextInput::make('primary_domain')
                                    ->placeholder('e.g., example.com'),
                            ])->columns(3),
                        
                        Forms\Components\Section::make('Payment Status')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->required()
                                    ->default('active')
                                    ->options([
                                        'pending' => 'Pending Setup',
                                        'active' => 'Active',
                                    ]),
                                Forms\Components\Select::make('payment_status')
                                    ->label('Invoice Payment Status')
                                    ->required()
                                    ->default('pending')
                                    ->options([
                                        'pending' => 'Pending Payment',
                                        'paid' => 'Paid',
                                    ]),
                                Forms\Components\Toggle::make('create_order')
                                    ->label('Create Linked Order')
                                    ->default(true)
                                    ->helperText('Create an order record for this allocation'),
                            ])->columns(3),
                        
                        Forms\Components\Section::make('Admin Notes')
                            ->schema([
                                Forms\Components\Textarea::make('admin_notes')
                                    ->rows(2)
                                    ->placeholder('Any additional notes...'),
                            ]),
                    ])
                    ->action(function (array $data) {
                        $service = app(HostingProvisioningService::class);
                        $result = $service->createManualHosting($data, auth()->id());
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Hosting created successfully!')
                                ->body('Invoice #' . ($result['invoice']?->invoice_number ?? 'N/A') . ' generated.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to create hosting')
                                ->body($result['error'])
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListUserHostings::route('/'),
            'create' => Pages\CreateUserHosting::route('/create'),
            'edit' => Pages\EditUserHosting::route('/{record}/edit'),
            'view' => Pages\ViewUserHosting::route('/{record}'),
        ];
    }
}
