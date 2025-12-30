<?php

namespace App\Filament\Admin\Resources\CustomerPriceLists\Pages;

use App\Filament\Admin\Resources\CustomerPriceLists\CustomerPriceListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPriceLists extends ListRecords
{
    protected static string $resource = CustomerPriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
