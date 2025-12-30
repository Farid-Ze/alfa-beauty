<?php

namespace App\Filament\Admin\Resources\CustomerPriceLists\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerPriceListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('product_id')
                    ->relationship('product', 'name'),
                Select::make('brand_id')
                    ->relationship('brand', 'name'),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('custom_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('discount_percent')
                    ->numeric(),
                TextInput::make('min_quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                DatePicker::make('valid_from'),
                DatePicker::make('valid_until'),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('notes'),
            ]);
    }
}
