<?php

namespace App\Filament\Admin\Resources\LoyaltyTiers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoyaltyTiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('min_spend', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                // Format min_spend as currency
                TextColumn::make('min_spend')
                    ->label('Min. Spend')
                    ->money('IDR')
                    ->sortable(),
                
                TextColumn::make('discount_percent')
                    ->label('Discount')
                    ->suffix('%')
                    ->sortable(),
                
                TextColumn::make('point_multiplier')
                    ->label('Points Multiplier')
                    ->suffix('x')
                    ->sortable(),
                
                IconColumn::make('free_shipping')
                    ->label('Free Shipping')
                    ->boolean(),
                
                TextColumn::make('badge_color')
                    ->label('Badge')
                    ->badge()
                    ->color(fn (string $state): string => $state)
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                // Show member count
                TextColumn::make('users_count')
                    ->label('Members')
                    ->counts('users')
                    ->sortable(),
                
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
