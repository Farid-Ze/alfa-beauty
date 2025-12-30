<?php

namespace App\Filament\Admin\Resources\ProductPriceTiers;

use App\Filament\Admin\Resources\ProductPriceTiers\Pages\CreateProductPriceTier;
use App\Filament\Admin\Resources\ProductPriceTiers\Pages\EditProductPriceTier;
use App\Filament\Admin\Resources\ProductPriceTiers\Pages\ListProductPriceTiers;
use App\Filament\Admin\Resources\ProductPriceTiers\Schemas\ProductPriceTierForm;
use App\Filament\Admin\Resources\ProductPriceTiers\Tables\ProductPriceTiersTable;
use App\Models\ProductPriceTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductPriceTierResource extends Resource
{
    protected static ?string $model = ProductPriceTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ProductPriceTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductPriceTiersTable::configure($table);
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
            'index' => ListProductPriceTiers::route('/'),
            'create' => CreateProductPriceTier::route('/create'),
            'edit' => EditProductPriceTier::route('/{record}/edit'),
        ];
    }
}
