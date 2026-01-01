<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp Business checkout flow.
    | The business_number should be in international format without + sign.
    | Example: 6281234567890 for Indonesian number +62 812-3456-7890
    |
    */

    'whatsapp' => [
        'business_number' => env('WHATSAPP_BUSINESS_NUMBER', '6281234567890'),
        'business_name' => env('WHATSAPP_BUSINESS_NAME', 'PT. Alfa Beauty Cosmetica'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration (PPN Indonesia)
    |--------------------------------------------------------------------------
    |
    | Indonesian tax rates and settings.
    | Default rate is 11% PPN as of 2024.
    |
    */

    'tax' => [
        'default_rate' => (float) env('TAX_DEFAULT_RATE', 11.00),
        'exempt_rate' => 0.00,
        'is_inclusive' => (bool) env('TAX_IS_INCLUSIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping Configuration
    |--------------------------------------------------------------------------
    |
    | Shipping zones and rates for Indonesian regions.
    | Rates are in IDR per kg.
    |
    */

    'shipping' => [
        'volumetric_divisor' => 5000,
        'default_zone' => 'jabodetabek',
        'zones' => [
            'jabodetabek' => [
                'name' => 'Jabodetabek',
                'base_rate' => (int) env('SHIPPING_JABODETABEK_BASE', 15000),
                'weight_rate' => (int) env('SHIPPING_JABODETABEK_WEIGHT', 5000),
                'min_weight' => 1,
            ],
            'jawa' => [
                'name' => 'Jawa',
                'base_rate' => (int) env('SHIPPING_JAWA_BASE', 20000),
                'weight_rate' => (int) env('SHIPPING_JAWA_WEIGHT', 7000),
                'min_weight' => 1,
            ],
            'sumatera' => [
                'name' => 'Sumatera',
                'base_rate' => (int) env('SHIPPING_SUMATERA_BASE', 35000),
                'weight_rate' => (int) env('SHIPPING_SUMATERA_WEIGHT', 12000),
                'min_weight' => 1,
            ],
            'kalimantan' => [
                'name' => 'Kalimantan',
                'base_rate' => (int) env('SHIPPING_KALIMANTAN_BASE', 40000),
                'weight_rate' => (int) env('SHIPPING_KALIMANTAN_WEIGHT', 15000),
                'min_weight' => 1,
            ],
            'sulawesi' => [
                'name' => 'Sulawesi',
                'base_rate' => (int) env('SHIPPING_SULAWESI_BASE', 45000),
                'weight_rate' => (int) env('SHIPPING_SULAWESI_WEIGHT', 15000),
                'min_weight' => 1,
            ],
            'bali_nusa' => [
                'name' => 'Bali & Nusa Tenggara',
                'base_rate' => (int) env('SHIPPING_BALI_BASE', 35000),
                'weight_rate' => (int) env('SHIPPING_BALI_WEIGHT', 12000),
                'min_weight' => 1,
            ],
            'papua_maluku' => [
                'name' => 'Papua & Maluku',
                'base_rate' => (int) env('SHIPPING_PAPUA_BASE', 65000),
                'weight_rate' => (int) env('SHIPPING_PAPUA_WEIGHT', 25000),
                'min_weight' => 1,
            ],
        ],
    ],

];
