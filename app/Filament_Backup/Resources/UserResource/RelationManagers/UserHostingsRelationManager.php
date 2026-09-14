<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Service;

class UserHostingsRelationManager extends RelationManager
{
    protected static string $relationship = 'hostings';

    protected static ?string $title = 'Hosting Services';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('hosting_type')
                    ->options([
                        'shared' => 'Shared Hosting',
                        'vps' => 'VPS Hosting',
                        'dedicated' => 'Dedicated Server',
                        'cloud' => 'Cloud Hosting',
                        'reseller' => 'Reseller Hosting',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $set('plan_name', null);
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
                            $set('price', $service->price);
                            $hostingType = strtolower($service->category ?? '');
                            $type = match($hostingType) {
                                'vps' => 'vps',
                                'dedicated' => 'dedicated',
                                'cloud' => 'cloud',
                                'shared' => 'shared',
                                'reseller' => 'reseller',
                                default => 'shared',
                            };
                            $set('hosting_type', $type);
                        }
                    }),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'pending' => 'Pending',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('primary_domain')
                    ->maxLength(255),
                Forms\Components\TextInput::make('server_ip')
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('₹'),
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\DatePicker::make('expiry_date'),
                Forms\Components\TextInput::make('cpu_cores')
                    ->numeric(),
                Forms\Components\TextInput::make('ram_size')
                    ->maxLength(255),
                Forms\Components\TextInput::make('storage_size')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plan_name')
            ->columns([
                Tables\Columns\TextColumn::make('plan_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('hosting_type')
                    ->colors([
                        'primary' => 'shared',
                        'success' => 'vps',
                        'warning' => 'dedicated',
                        'info' => 'cloud',
                        'danger' => 'reseller',
                    ])
                    ->icons([
                        'heroicon-o-cloud' => 'shared',
                        'heroicon-o-server' => 'vps',
                        'heroicon-o-database' => 'dedicated',
                        'heroicon-o-globe-alt' => 'cloud',
                    ]),
                Tables\Columns\TextColumn::make('primary_domain')
                    ->searchable()
                    ->icon('heroicon-o-globe-alt')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('server_ip')
                    ->searchable()
                    ->icon('heroicon-o-signal')
                    ->placeholder('N/A'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => ['suspended', 'expired'],
                        'warning' => 'cancelled',
                        'info' => 'pending',
                    ]),
                Tables\Columns\TextColumn::make('price')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color(fn ($record) => $record->isExpiringSoon() ? 'danger' : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'pending' => 'Pending',
                    ]),
                Tables\Filters\SelectFilter::make('hosting_type')
                    ->options([
                        'shared' => 'Shared Hosting',
                        'vps' => 'VPS Hosting',
                        'dedicated' => 'Dedicated Server',
                        'cloud' => 'Cloud Hosting',
                        'reseller' => 'Reseller Hosting',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Hosting'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hosting services')
            ->emptyStateDescription('This client has not purchased any hosting services yet.')
            ->emptyStateIcon('heroicon-o-server');
    }
}
