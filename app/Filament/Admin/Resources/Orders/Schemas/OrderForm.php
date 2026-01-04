<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('order_number')
                                    ->required()
                                    ->disabled(),
                                TextInput::make('user_id')
                                    ->label('Customer ID')
                                    ->numeric()
                                    ->disabled(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'pending_payment' => 'Pending Payment',
                                        'processing' => 'Processing',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required(),
                                Select::make('payment_status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'failed' => 'Failed',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->required(),
                            ]),
                    ]),

                Section::make('Pricing')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled(),
                                TextInput::make('discount_amount')
                                    ->numeric()
                                    ->prefix('Rp'),
                                TextInput::make('shipping_cost')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                TextInput::make('amount_paid')
                                    ->numeric()
                                    ->prefix('Rp'),
                                TextInput::make('balance_due')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Payment')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('payment_method')
                                    ->options([
                                        'bank_transfer' => 'Bank Transfer',
                                        'whatsapp' => 'WhatsApp',
                                        'cod' => 'Cash on Delivery',
                                        'credit' => 'Credit Terms',
                                    ]),
                                DatePicker::make('payment_due_date')
                                    ->label('Payment Due Date'),
                            ]),
                    ]),

                Section::make('Shipping')
                    ->schema([
                        Textarea::make('shipping_address')
                            ->rows(3)
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('shipping_method'),
                            ]),
                        Textarea::make('notes')
                            ->label('Order Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
