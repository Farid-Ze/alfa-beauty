<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('order_number')
                                    ->label('Order Number')
                                    ->weight('bold')
                                    ->size('lg'),
                                TextEntry::make('user.name')
                                    ->label('Customer')
                                    ->placeholder('Guest'),
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->placeholder('-'),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed', 'paid', 'delivered' => 'success',
                                        'processing', 'shipped' => 'info',
                                        'pending', 'pending_payment' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('payment_status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('payment_method')
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->label('Order Date')
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->label('Qty'),
                                TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->money('IDR'),
                                TextEntry::make('total_price')
                                    ->label('Total')
                                    ->money('IDR'),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Pricing')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->money('IDR'),
                                TextEntry::make('discount_amount')
                                    ->label('Discount')
                                    ->money('IDR')
                                    ->placeholder('Rp 0'),
                                TextEntry::make('shipping_cost')
                                    ->money('IDR')
                                    ->placeholder('Rp 0'),
                                TextEntry::make('total_amount')
                                    ->label('Total')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->size('lg'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('amount_paid')
                                    ->money('IDR')
                                    ->placeholder('Rp 0'),
                                TextEntry::make('balance_due')
                                    ->money('IDR')
                                    ->placeholder('Rp 0'),
                                TextEntry::make('payment_due_date')
                                    ->date()
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Shipping')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('shipping_address')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                                TextEntry::make('shipping_method')
                                    ->placeholder('-'),
                            ]),
                        TextEntry::make('notes')
                            ->label('Order Notes')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
