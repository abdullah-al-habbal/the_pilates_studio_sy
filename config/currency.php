<?php
// filePath: config\currency.php
declare(strict_types=1);

return [
    'base_currency' => env('CURRENCY_BASE', 'USD'),
    'snapshot_precision' => 6,
    'cache_ttl_production' => 600,
    'cache_ttl_dev' => 3600,
    'validation' => [
        'base_currency_required' => true,
        'base_currency_must_be_active' => true,
        'snapshot_precision_min' => 4,
        'snapshot_precision_max' => 8,
    ],
];