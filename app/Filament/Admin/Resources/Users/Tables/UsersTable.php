<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                
                // Role - Critical for admin management
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'staff' => 'warning',
                        'customer' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->toggleable(),
                
                // Loyalty tier with relationship
                TextColumn::make('loyaltyTier.name')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Platinum' => 'primary',
                        'Gold' => 'warning',
                        'Silver' => 'gray',
                        'Bronze' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('No Tier')
                    ->sortable(),
                
                TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                
                TextColumn::make('total_spend')
                    ->label('Total Spend')
                    ->money('IDR')
                    ->sortable(),
                
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->placeholder('Not verified')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Role filter
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                        'customer' => 'Customer',
                    ]),
                
                // Loyalty tier filter
                SelectFilter::make('loyalty_tier_id')
                    ->label('Loyalty Tier')
                    ->relationship('loyaltyTier', 'name')
                    ->preload(),
                
                // Email verification filter
                SelectFilter::make('verified')
                    ->label('Email Status')
                    ->options([
                        'verified' => 'Verified',
                        'unverified' => 'Not Verified',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'verified' => $query->whereNotNull('email_verified_at'),
                            'unverified' => $query->whereNull('email_verified_at'),
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
