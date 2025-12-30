<?php

namespace App\Filament\Admin\Resources\ProductPriceTiers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductPriceTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('min_quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('max_quantity')
                    ->numeric(),
                TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('discount_percent')
                    ->numeric(),
            ]);
    }
}
