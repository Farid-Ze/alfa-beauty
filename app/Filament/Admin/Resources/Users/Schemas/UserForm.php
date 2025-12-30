<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\LoyaltyTier;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('company_name')
                    ->label('Company Name')
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->maxLength(20),
                DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At'),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->label(fn (string $context): string => $context === 'create' ? 'Password' : 'New Password (leave blank to keep current)'),
                Select::make('loyalty_tier_id')
                    ->label('Loyalty Tier')
                    ->relationship('loyaltyTier', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('points')
                    ->label('Points Balance')
                    ->numeric()
                    ->default(0)
                    ->disabled(fn (string $context): bool => $context === 'create'),
                TextInput::make('total_spend')
                    ->label('Total Spend (Rp)')
                    ->numeric()
                    ->default(0)
                    ->disabled(fn (string $context): bool => $context === 'create'),
            ]);
    }
}
