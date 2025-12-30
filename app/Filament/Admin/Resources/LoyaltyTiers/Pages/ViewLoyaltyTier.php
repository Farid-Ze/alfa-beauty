<?php

namespace App\Filament\Admin\Resources\LoyaltyTiers\Pages;

use App\Filament\Admin\Resources\LoyaltyTiers\LoyaltyTierResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLoyaltyTier extends ViewRecord
{
    protected static string $resource = LoyaltyTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
