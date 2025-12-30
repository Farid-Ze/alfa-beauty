<?php

namespace App\Filament\Admin\Resources\LoyaltyTiers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LoyaltyTierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('min_spend')
                    ->numeric(),
                TextEntry::make('discount_percent')
                    ->numeric(),
                TextEntry::make('point_multiplier')
                    ->numeric(),
                IconEntry::make('free_shipping')
                    ->boolean(),
                TextEntry::make('badge_color')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
