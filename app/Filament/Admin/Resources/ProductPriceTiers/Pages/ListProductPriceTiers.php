<?php

namespace App\Filament\Admin\Resources\ProductPriceTiers\Pages;

use App\Filament\Admin\Resources\ProductPriceTiers\ProductPriceTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductPriceTiers extends ListRecords
{
    protected static string $resource = ProductPriceTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
