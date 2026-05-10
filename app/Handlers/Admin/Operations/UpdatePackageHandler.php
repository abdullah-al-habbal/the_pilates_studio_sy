<?php
declare(strict_types=1);
namespace App\Handlers\Admin\Operations;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
final readonly class UpdatePackageHandler
{
    public function handle(
        int $packageId,
        string $name,
        int $totalCredits,
        ?int $validityDays,
        int $currencyId,
        int $amount,
    ): Package {
        return DB::transaction(function () use ($packageId, $name, $totalCredits, $validityDays, $currencyId, $amount) {
            $package = Package::findOrFail($packageId);
            $package->update([
                'name'          => ['en' => $name],
                'total_credits' => $totalCredits,
                'validity_days' => $validityDays,
            ]);
            $package->prices()->updateOrCreate(
                ['currency_id' => $currencyId],
                ['amount' => $amount]
            );
            return $package->load('prices');
        });
    }
}
