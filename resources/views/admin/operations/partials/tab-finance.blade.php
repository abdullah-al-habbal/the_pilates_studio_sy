<div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex justify-between items-end">
        <div class="space-y-2">
            <h2 class="text-2xl font-bold tracking-tight">Financial Balance</h2>
            <p class="text-slate-500">Real-time revenue and expense tracking.</p>
        </div>
        <input type="date" id="balance-date" value="{{ date('Y-m-d') }}"
            class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
    </div>

    <div id="balance-container">
        <!-- Balance cards injected here -->
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold">Record Expense</h3>
            <form id="expense-form" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500 uppercase">Category</label>
                        <input type="text" name="category_name" required placeholder="Rent, Electricity..."
                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500 uppercase">Amount</label>
                        <input type="number" name="amount" required placeholder="0"
                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-primary-500 outline-none"></textarea>
                </div>
                <button type="submit"
                    class="w-full bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold py-3 rounded-xl hover:scale-[1.02] active:scale-[0.98] transition-all">Save
                    Expense</button>
            </form>
        </div>

        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold">Revenue Breakdown</h3>
            <div id="revenue-chart-placeholder" class="h-48 flex items-end gap-2 px-4">
                <!-- Fake chart bars -->
            </div>
        </div>
    </div>
</div>
