<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\LoyaltyTier;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),
                                
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20),
                                
                                TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('Security & Access')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // CRITICAL: Role field for access control
                                Select::make('role')
                                    ->label('User Role')
                                    ->options([
                                        'customer' => 'Customer',
                                        'staff' => 'Staff',
                                        'admin' => 'Admin',
                                    ])
                                    ->required()
                                    ->default('customer')
                                    ->helperText('Admin/Staff can access admin panel'),
                                
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->dehydrated(fn (?string $state) => filled($state))
                                    ->label(fn (string $context): string => 
                                        $context === 'create' ? 'Password' : 'New Password (optional)')
                                    ->helperText(fn (string $context): ?string => 
                                        $context === 'edit' ? 'Leave blank to keep current password' : null),
                            ]),
                        
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->helperText('Set to verify email manually'),
                    ]),

                Section::make('Loyalty Program')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('loyalty_tier_id')
                                    ->label('Loyalty Tier')
                                    ->relationship('loyaltyTier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Tier determines discounts and perks'),
                                
                                TextInput::make('points')
                                    ->label('Points Balance')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Current redeemable points'),
                                
                                TextInput::make('total_spend')
                                    ->label('Total Spend')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->helperText('Lifetime spending (auto-calculated)'),
                            ]),
                    ]),

                Section::make('Address')
                    ->schema([
                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Default shipping address'),
                    ])
                    ->collapsible(),
            ]);
    }
}
