<?php

namespace App\Filament\Admin\Resources\ProductPriceTiers\Pages;

use App\Filament\Admin\Resources\ProductPriceTiers\ProductPriceTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductPriceTier extends EditRecord
{
    protected static string $resource = ProductPriceTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
