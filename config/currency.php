<?php
declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Base Currency Configuration
    |--------------------------------------------------------------------------
    |
    | The canonical base currency for Option B pricing architecture.
    | All package/merchandise base prices are stored in this currency.
    |
    */

    'base_currency' => env('CURRENCY_BASE', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Precision
    |--------------------------------------------------------------------------
    |
    | Decimal places used when storing exchange_rate_snapshot values.
    | Must match database column precision (decimal(12,6)).
    |
    */

    'snapshot_precision' => 6,

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'cache_ttl_production' => 600, // 10 minutes
    'cache_ttl_dev' => 3600, // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | These are checked by `php artisan config:validate-financial` custom command.
    |
    */

    'validation' => [
        'base_currency_required' => true,
        'base_currency_must_be_active' => true,
        'snapshot_precision_min' => 4,
        'snapshot_precision_max' => 8,
    ],
];