<?php

namespace App\Filament\Admin\Resources\CustomerPriceLists;

use App\Filament\Admin\Resources\CustomerPriceLists\Pages\CreateCustomerPriceList;
use App\Filament\Admin\Resources\CustomerPriceLists\Pages\EditCustomerPriceList;
use App\Filament\Admin\Resources\CustomerPriceLists\Pages\ListCustomerPriceLists;
use App\Filament\Admin\Resources\CustomerPriceLists\Schemas\CustomerPriceListForm;
use App\Filament\Admin\Resources\CustomerPriceLists\Tables\CustomerPriceListsTable;
use App\Models\CustomerPriceList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerPriceListResource extends Resource
{
    protected static ?string $model = CustomerPriceList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return CustomerPriceListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerPriceListsTable::configure($table);
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
            'index' => ListCustomerPriceLists::route('/'),
            'create' => CreateCustomerPriceList::route('/create'),
            'edit' => EditCustomerPriceList::route('/{record}/edit'),
        ];
    }
}
