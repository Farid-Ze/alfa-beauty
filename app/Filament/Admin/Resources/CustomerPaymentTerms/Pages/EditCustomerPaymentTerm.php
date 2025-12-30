<?php

namespace App\Filament\Admin\Resources\CustomerPaymentTerms\Pages;

use App\Filament\Admin\Resources\CustomerPaymentTerms\CustomerPaymentTermResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerPaymentTerm extends EditRecord
{
    protected static string $resource = CustomerPaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
