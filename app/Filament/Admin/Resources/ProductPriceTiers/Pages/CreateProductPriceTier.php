<?php

namespace App\Filament\Admin\Resources\ProductPriceTiers\Pages;

use App\Filament\Admin\Resources\ProductPriceTiers\ProductPriceTierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductPriceTier extends CreateRecord
{
    protected static string $resource = ProductPriceTierResource::class;
}
