<?php

namespace App\Filament\Admin\Resources\LoyaltyTiers;

use App\Filament\Admin\Resources\LoyaltyTiers\Pages\CreateLoyaltyTier;
use App\Filament\Admin\Resources\LoyaltyTiers\Pages\EditLoyaltyTier;
use App\Filament\Admin\Resources\LoyaltyTiers\Pages\ListLoyaltyTiers;
use App\Filament\Admin\Resources\LoyaltyTiers\Pages\ViewLoyaltyTier;
use App\Filament\Admin\Resources\LoyaltyTiers\Schemas\LoyaltyTierForm;
use App\Filament\Admin\Resources\LoyaltyTiers\Schemas\LoyaltyTierInfolist;
use App\Filament\Admin\Resources\LoyaltyTiers\Tables\LoyaltyTiersTable;
use App\Models\LoyaltyTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoyaltyTierResource extends Resource
{
    protected static ?string $model = LoyaltyTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LoyaltyTierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LoyaltyTierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyTiersTable::configure($table);
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
            'index' => ListLoyaltyTiers::route('/'),
            'create' => CreateLoyaltyTier::route('/create'),
            'view' => ViewLoyaltyTier::route('/{record}'),
            'edit' => EditLoyaltyTier::route('/{record}/edit'),
        ];
    }
}
