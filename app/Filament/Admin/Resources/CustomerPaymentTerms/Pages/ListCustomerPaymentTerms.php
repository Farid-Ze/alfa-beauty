<?php

namespace App\Filament\Admin\Resources\CustomerPaymentTerms\Pages;

use App\Filament\Admin\Resources\CustomerPaymentTerms\CustomerPaymentTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPaymentTerms extends ListRecords
{
    protected static string $resource = CustomerPaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
