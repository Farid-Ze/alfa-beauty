<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Full Name'),
                TextEntry::make('company_name')
                    ->label('Company Name')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email Address'),
                TextEntry::make('phone')
                    ->label('Phone Number')
                    ->placeholder('-'),
                TextEntry::make('email_verified_at')
                    ->label('Email Verified At')
                    ->dateTime()
                    ->placeholder('Not verified'),
                TextEntry::make('loyaltyTier.name')
                    ->label('Loyalty Tier')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Gold' => 'warning',
                        'Silver' => 'gray',
                        default => 'primary',
                    })
                    ->placeholder('Guest'),
                TextEntry::make('points')
                    ->label('Points Balance')
                    ->numeric(),
                TextEntry::make('total_spend')
                    ->label('Total Spend')
                    ->money('IDR'),
                TextEntry::make('created_at')
                    ->label('Member Since')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
