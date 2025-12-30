<?php

namespace App\Filament\Admin\Resources\LoyaltyTiers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LoyaltyTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('min_spend')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_percent')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('point_multiplier')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('free_shipping')
                    ->required(),
                TextInput::make('badge_color'),
            ]);
    }
}
