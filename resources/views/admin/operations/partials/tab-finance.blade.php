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
            <h2 class="text-2xl font-bold tracking-tight">Financial Balance</h2>
            <p class="text-slate-500">Real-time revenue and expense tracking, per currency.</p>
        </div>
        <input type="date" id="balance-date" value="{{ date('Y-m-d') }}"
               class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
    </div>

    <div class="flex flex-wrap gap-2 items-center" id="currency-filters">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mr-1">Show:</span>
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
                class="text-xs text-primary-600 underline ml-1 hover:text-primary-700">All</button>
    </div>

    <div id="balance-container"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold">Record Expense</h3>
            <form id="expense-form" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500 uppercase">Category</label>
                        <input type="text" name="category_name" required placeholder="Rent, Electricity…"
                               class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500 uppercase">Amount <span class="text-slate-400">(smallest unit)</span></label>
                        <input type="number" name="amount" required placeholder="0" min="1"
                               class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Currency</label>
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
                    <label class="text-xs font-semibold text-slate-500 uppercase">Expense Date</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none"></textarea>
                </div>
                <button type="submit"
                        class="w-full bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold py-3 rounded-xl hover:scale-[1.02] active:scale-[0.98] transition-all btn-single-action">
                    Save Expense
                </button>
            </form>
        </div>

        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold">Revenue Breakdown</h3>
            <div id="revenue-chart-placeholder" class="h-48 flex items-end gap-2 px-4"></div>
        </div>

    </div>
</div>