<?php

namespace App\Filament\Admin\Resources\CustomerPaymentTerms\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerPaymentTermForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('term_type')
                    ->required()
                    ->default('cod'),
                TextInput::make('credit_limit')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('current_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('early_payment_discount_percent')
                    ->numeric(),
                TextInput::make('early_payment_days')
                    ->numeric(),
                Toggle::make('is_approved')
                    ->required(),
                TextInput::make('approved_by')
                    ->numeric(),
                DateTimePicker::make('approved_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
