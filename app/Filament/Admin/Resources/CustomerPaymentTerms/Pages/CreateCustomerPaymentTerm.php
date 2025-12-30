<?php

namespace App\Filament\Admin\Resources\CustomerPaymentTerms\Pages;

use App\Filament\Admin\Resources\CustomerPaymentTerms\CustomerPaymentTermResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPaymentTerm extends CreateRecord
{
    protected static string $resource = CustomerPaymentTermResource::class;
}
