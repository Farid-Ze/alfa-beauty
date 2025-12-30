<?php

namespace App\Filament\Admin\Resources\CustomerPaymentTerms;

use App\Filament\Admin\Resources\CustomerPaymentTerms\Pages\CreateCustomerPaymentTerm;
use App\Filament\Admin\Resources\CustomerPaymentTerms\Pages\EditCustomerPaymentTerm;
use App\Filament\Admin\Resources\CustomerPaymentTerms\Pages\ListCustomerPaymentTerms;
use App\Filament\Admin\Resources\CustomerPaymentTerms\Schemas\CustomerPaymentTermForm;
use App\Filament\Admin\Resources\CustomerPaymentTerms\Tables\CustomerPaymentTermsTable;
use App\Models\CustomerPaymentTerm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerPaymentTermResource extends Resource
{
    protected static ?string $model = CustomerPaymentTerm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return CustomerPaymentTermForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerPaymentTermsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerPaymentTerms::route('/'),
            'create' => CreateCustomerPaymentTerm::route('/create'),
            'edit' => EditCustomerPaymentTerm::route('/{record}/edit'),
        ];
    }
}
