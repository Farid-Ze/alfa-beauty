<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Models\Brand;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // Core product identification
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                // Relationships - show names not IDs
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                
                // Price with correct currency
                TextColumn::make('base_price')
                    ->label('Price')
                    ->money('IDR')
                    ->sortable(),
                
                // Stock with visual indicator for low stock
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),
                
                // Status
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                
                // Compliance - important for B2B
                TextColumn::make('bpom_number')
                    ->label('BPOM')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                IconColumn::make('is_halal')
                    ->label('Halal')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // Hidden by default
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('video_url')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('msds_url')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Active/Inactive filter
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All Products')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
                
                // Featured filter
                TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All')
                    ->trueLabel('Featured')
                    ->falseLabel('Not Featured'),
                
                // Brand filter
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                
                // Category filter
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                
                // Low stock filter
                SelectFilter::make('stock')
                    ->label('Stock Level')
                    ->options([
                        'low' => 'Low Stock (< 10)',
                        'out' => 'Out of Stock',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'low' => $query->where('stock', '<', 10)->where('stock', '>', 0),
                            'out' => $query->where('stock', '<=', 0),
                            default => $query,
                        };
                    }),
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
