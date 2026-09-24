<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Models\Currency;
use Illuminate\View\View;

final class OperationsIndexAction
{
    public function __invoke(): View
    {
        $locale = app()->getLocale();

        return view('admin.operations.index', [
            'activeCurrencies' => Currency::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->map(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->getTranslations('name'),
                    'display_name' => $currency->getTranslation('name', $locale, false)
                        ?: $currency->getTranslation('name', 'en'),
                    'symbol' => $currency->symbol,
                    'decimal_places' => $currency->decimal_places,
                    'exchange_rate' => $currency->exchange_rate,
                ])
                ->values(),
        ]);
    }
}
