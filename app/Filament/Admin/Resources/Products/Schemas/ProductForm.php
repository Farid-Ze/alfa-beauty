<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->helperText('Unique product identifier'),
                                
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (empty($state)) return;
                                        $set('slug', \Illuminate\Support\Str::slug($state));
                                    }),
                                
                                TextInput::make('slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                // Use Select with relationship for Brand
                                Select::make('brand_id')
                                    ->label('Brand')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('slug')->required(),
                                    ]),
                                
                                // Use Select with relationship for Category
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('slug')->required(),
                                    ]),
                            ]),
                    ]),

                Section::make('Pricing & Stock')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('base_price')
                                    ->label('Base Price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->helperText('Price before any discounts'),
                                
                                TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Current stock quantity'),
                                
                                TextInput::make('min_order_quantity')
                                    ->label('MOQ')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->helperText('Minimum Order Quantity'),
                            ]),
                    ]),

                Section::make('Description')
                    ->schema([
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Product description for customers'),
                        
                        Textarea::make('inci_list')
                            ->label('INCI List')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('International Nomenclature of Cosmetic Ingredients'),
                        
                        Textarea::make('how_to_use')
                            ->label('How to Use')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Product Images')
                            ->multiple()
                            ->image()
                            ->maxSize(2048)
                            ->maxFiles(5)
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText('Max 5 images, max 2MB each'),
                        
                        TextInput::make('video_url')
                            ->label('Video URL')
                            ->url()
                            ->placeholder('https://youtube.com/...'),
                    ]),

                Section::make('Compliance & Certifications')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('bpom_number')
                                    ->label('BPOM Number')
                                    ->maxLength(50)
                                    ->helperText('Badan Pengawas Obat dan Makanan registration'),
                                
                                Toggle::make('is_halal')
                                    ->label('Halal Certified')
                                    ->default(false),
                                
                                Toggle::make('is_vegan')
                                    ->label('Vegan Friendly')
                                    ->default(false),
                            ]),
                        
                        TextInput::make('msds_url')
                            ->label('MSDS Document URL')
                            ->url()
                            ->helperText('Material Safety Data Sheet'),
                    ]),

                Section::make('Status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Product visible to customers'),
                                
                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->default(false)
                                    ->helperText('Show in featured products section'),
                            ]),
                    ]),
            ]);
    }
}
