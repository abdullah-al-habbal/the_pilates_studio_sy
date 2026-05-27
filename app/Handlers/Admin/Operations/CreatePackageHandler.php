<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Package;
use App\Enums\PackageTypeEnum;
use App\Services\Currency\CurrencyService;
use Illuminate\Support\Facades\DB;

final readonly class CreatePackageHandler
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    public function handle(
        string $name,
        int $totalCredits,
        ?int $validityDays,
        int $amount,
    ): Package {
        $baseCurrencyId = $this->currencyService->getBaseCurrency()->id;

        return DB::transaction(function () use ($name, $totalCredits, $validityDays, $baseCurrencyId, $amount) {
            $package = Package::create([
                'name'          => ['en' => $name],
                'total_credits' => $totalCredits,
                'validity_days' => $validityDays,
                'is_active'     => true,
                'type'          => PackageTypeEnum::STANDARD,
            ]);
            $package->prices()->create([
                'currency_id' => $baseCurrencyId,
                'amount'      => $amount,
            ]);
            return $package->load('prices');
        });
    }
}
