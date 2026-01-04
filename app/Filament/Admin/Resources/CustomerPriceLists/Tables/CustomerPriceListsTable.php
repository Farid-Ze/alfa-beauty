<?php

namespace App\Filament\Admin\Resources\CustomerPriceLists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerPriceListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->placeholder('-'),
                
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->placeholder('-'),
                
                TextColumn::make('custom_price')
                    ->label('Custom Price')
                    ->money('IDR')
                    ->sortable(),
                
                TextColumn::make('discount_percent')
                    ->label('Discount')
                    ->suffix('%')
                    ->sortable(),
                
                TextColumn::make('min_quantity')
                    ->label('Min Qty')
                    ->numeric()
                    ->sortable(),
                
                TextColumn::make('priority')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // Validity status
                TextColumn::make('valid_from')
                    ->label('Valid From')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable()
                    ->color(fn ($state): string => 
                        $state && $state < now() ? 'danger' : 'success'
                    ),
                
                TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->preload(),
                
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->preload(),
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
