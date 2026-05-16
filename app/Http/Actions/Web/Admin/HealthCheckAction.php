<?php
declare(strict_types=1);
namespace App\Http\Actions\Web\Admin;

use App\Models\Currency;
use App\Services\Currency\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class HealthCheckAction
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'exchange_rates' => $this->checkExchangeRates(),
            'base_currency' => $this->checkBaseCurrency(),
        ];

        $status = collect($checks)->every(fn($c) => $c['ok']) ? 'healthy' : 'degraded';

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'version' => config('app.version', 'unknown'),
        ], $status === 'healthy' ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['ok' => true, 'message' => 'Database connection active'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $retrieved = Cache::get('health_check');
            return [
                'ok' => $retrieved === 'ok',
                'message' => $retrieved === 'ok' ? 'Cache operational' : 'Cache retrieval failed',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkExchangeRates(): array
    {
        $activeCurrencies = Currency::where('is_active', true)->get();
        $invalid = $activeCurrencies->filter(fn($c) => $c->exchange_rate <= 0);

        return [
            'ok' => $invalid->isEmpty(),
            'message' => $invalid->isEmpty()
                ? "All {$activeCurrencies->count()} currencies have valid rates"
                : "Invalid rates for: " . $invalid->pluck('code')->join(', '),
            'last_sync' => $activeCurrencies->max('updated_at')?->toIso8601String(),
        ];
    }

    private function checkBaseCurrency(): array
    {
        $baseCode = config('currency.base_currency');
        $baseCurrency = Currency::where('code', $baseCode)->where('is_active', true)->first();

        return [
            'ok' => $baseCurrency !== null,
            'message' => $baseCurrency
                ? "Base currency {$baseCode} active"
                : "Base currency {$baseCode} not found or inactive",
        ];
    }
}
