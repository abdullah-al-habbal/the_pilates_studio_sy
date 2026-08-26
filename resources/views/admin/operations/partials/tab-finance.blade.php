{{-- resources/views/admin/operations/partials/tab-finance.blade.php --}}
@php
    use App\Services\Currency\CurrencyService;
    /** @var CurrencyService $currencyService */
    $currencyService    = app(CurrencyService::class);
    $defaultCurrency    = $currencyService->getDefaultCurrency();
    $activeCurrencies   = $currencyService->getAllActiveCurrencies();
@endphp

<div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">

    <div class="flex flex-wrap justify-between items-end gap-4">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold tracking-tight">{{ __('dashboard::dashboard.operations_ui.finance.title') }}</h2>
            <p class="text-slate-500">{{ __('dashboard::dashboard.operations_ui.finance.description') }}</p>
        </div>
        <input type="date" id="balance-date" value="{{ date('Y-m-d') }}"
               class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
    </div>

    <div class="flex flex-wrap gap-2 items-center" id="currency-filters">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mr-1">{{ __('dashboard::dashboard.operations_ui.finance.show_label') }}</span>
        @foreach($activeCurrencies as $currency)
            <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold cursor-pointer
                          bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300
                          has-[:checked]:bg-primary-600 has-[:checked]:text-white transition-all select-none">
                <input type="checkbox" class="currency-filter-cb sr-only"
                       value="{{ $currency->code }}"
                       {{ $loop->first ? 'checked' : '' }}
                       onchange="OperationsFinance.applyCurrencyFilter()">
                {{ $currency->code }} {{ $currency->symbol }}
            </label>
        @endforeach
        <button onclick="document.querySelectorAll('.currency-filter-cb').forEach(c=>c.checked=true);OperationsFinance.applyCurrencyFilter()"
                class="text-xs text-primary-600 underline ml-1 hover:text-primary-700">{{ __('dashboard::dashboard.operations_ui.finance.all_label') }}</button>
        <label class="flex items-center gap-2 ml-4 text-xs font-medium text-slate-600 dark:text-slate-300 cursor-pointer">
            <input type="checkbox" id="convert-to-base" class="rounded border-slate-300"
                   onchange="OperationsFinance.applyCurrencyFilter()">
            {{ __('dashboard::dashboard.operations_ui.finance.convert_to_base') }} ({{ $defaultCurrency->code }})
        </label>
    </div>

    <div id="balance-container"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ __('dashboard::dashboard.operations_ui.finance.record_expense') }}</h3>
            <form id="expense-form" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1 relative">
                        <label class="text-xs font-semibold text-slate-500 uppercase">{{ __('dashboard::dashboard.operations_ui.finance.category_label') }}</label>
                        <input type="text" name="category_name" id="category-input" required
                               autocomplete="off"
                               placeholder="{{ __('dashboard::dashboard.operations_ui.finance.category_placeholder') }}"
                               class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                        <div id="category-dropdown"
                             class="hidden absolute z-20 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500 uppercase">{{ __('dashboard::dashboard.operations_ui.finance.amount_label') }}</label>
                        <input type="number" name="amount" required placeholder="0" step="1" min="0"
                               class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">{{ __('dashboard::dashboard.operations_ui.finance.currency_label') }}</label>
                    <select name="currency_id" required
                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                        @foreach($activeCurrencies as $currency)
                            <option value="{{ $currency->id }}" {{ $currency->id === $defaultCurrency->id ? 'selected' : '' }}>
                                {{ $currency->code }} — {{ $currency->symbol }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">{{ __('dashboard::dashboard.operations_ui.finance.expense_date_label') }}</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">{{ __('dashboard::dashboard.operations_ui.finance.notes_label') }}</label>
                    <textarea name="notes" rows="2"
                              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none"></textarea>
                </div>
                <button type="submit"
                        class="w-full bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold py-3 rounded-xl hover:scale-[1.02] active:scale-[0.98] transition-all btn-single-action">
                    {{ __('dashboard::dashboard.operations_ui.finance.save_expense') }}
                </button>
            </form>
        </div>

        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ __('dashboard::dashboard.operations_ui.finance.expense_breakdown') }}</h3>
            <div id="expense-breakdown" class="h-64 overflow-y-auto py-2"></div>
        </div>

    </div>
</div>