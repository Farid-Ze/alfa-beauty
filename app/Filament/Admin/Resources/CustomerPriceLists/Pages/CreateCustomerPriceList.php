<?php

namespace App\Filament\Admin\Resources\CustomerPriceLists\Pages;

use App\Filament\Admin\Resources\CustomerPriceLists\CustomerPriceListResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPriceList extends CreateRecord
{
    protected static string $resource = CustomerPriceListResource::class;
}
