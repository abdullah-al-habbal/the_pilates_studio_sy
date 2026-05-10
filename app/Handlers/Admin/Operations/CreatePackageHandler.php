<?php
declare(strict_types=1);
namespace App\Handlers\Admin\Operations;
use App\Models\Package;
use App\Enums\PackageTypeEnum;
use Illuminate\Support\Facades\DB;
final readonly class CreatePackageHandler
{
    public function handle(
        string $name,
        int $totalCredits,
        ?int $validityDays,
        int $currencyId,
        int $amount,
    ): Package {
        return DB::transaction(function () use ($name, $totalCredits, $validityDays, $currencyId, $amount) {
            $package = Package::create([
                'name'          => ['en' => $name],
                'total_credits' => $totalCredits,
                'validity_days' => $validityDays,
                'is_active'     => true,
                'type'          => PackageTypeEnum::STANDARD,
            ]);
            $package->prices()->create([
                'currency_id' => $currencyId,
                'amount'      => $amount,
            ]);
            return $package->load('prices');
        });
    }
}
