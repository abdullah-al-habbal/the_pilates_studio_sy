<?php
declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Minimum Refund Amount
    |--------------------------------------------------------------------------
    |
    | The minimum amount that can be refunded to a customer.
    |
    */
    'min_refund_amount' => env('FINANCIAL_MIN_REFUND_AMOUNT', 100),

    /*
    |--------------------------------------------------------------------------
    | Rounding Mode
    |--------------------------------------------------------------------------
    |
    | The default rounding mode for financial calculations (e.g. PHP_ROUND_HALF_UP).
    |
    */
    'rounding_mode' => PHP_ROUND_HALF_UP,

    /*
    |--------------------------------------------------------------------------
    | Partial Refunds
    |--------------------------------------------------------------------------
    |
    | Whether or not to allow partial refunds.
    |
    */
    'allow_partial_refunds' => env('FINANCIAL_ALLOW_PARTIAL_REFUNDS', true),
];
