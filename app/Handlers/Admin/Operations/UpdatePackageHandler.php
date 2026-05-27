<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Package;
use App\Services\Currency\CurrencyService;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePackageHandler
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    public function handle(
        int $packageId,
        string $name,
        int $totalCredits,
        ?int $validityDays,
        int $amount,
    ): Package {
        $baseCurrencyId = $this->currencyService->getBaseCurrency()->id;

        return DB::transaction(function () use ($packageId, $name, $totalCredits, $validityDays, $baseCurrencyId, $amount) {
            $package = Package::findOrFail($packageId);
            $package->update([
                'name'          => ['en' => $name],
                'total_credits' => $totalCredits,
                'validity_days' => $validityDays,
            ]);
            $package->prices()->updateOrCreate(
                ['currency_id' => $baseCurrencyId],
                ['amount' => $amount]
            );
            return $package->load('prices');
        });
    }
}
