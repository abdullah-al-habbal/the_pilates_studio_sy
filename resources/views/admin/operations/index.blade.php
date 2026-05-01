@extends('layouts.operations')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Sidebar / Nav -->
        <aside class="lg:col-span-3 space-y-4">
            <div class="glass-card rounded-2xl p-4">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 px-2">Navigation</h2>
                <nav class="space-y-1">
                    <button data-tab="clients"
                        class="w-full text-left px-4 py-3 rounded-xl transition-all flex items-center gap-3 bg-primary-600 text-white shadow-lg shadow-primary-500/20 active-tab">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <span>Clients & Packages</span>
                    </button>
                    <button data-tab="store"
                        class="w-full text-left px-4 py-3 rounded-xl transition-all flex items-center gap-3 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <span>Store & Inventory</span>
                    </button>
                    <button data-tab="finance"
                        class="w-full text-left px-4 py-3 rounded-xl transition-all flex items-center gap-3 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        <span>Finance & Balance</span>
                    </button>
                </nav>
            </div>

            <!-- Quick Stats Widget -->
            <div id="quick-stats-container" class="glass-card rounded-2xl p-5 border-l-4 border-primary-500">
                <h3 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase mb-3">Daily Snapshot</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Balance</span>
                        <span id="stat-balance" class="font-bold text-slate-900 dark:text-white">...</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div id="balance-progress" class="bg-primary-500 h-full w-0 transition-all duration-1000"></div>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <!--                                             // fix: use the current price approach
                     -->
                        <span class="text-slate-400">Target: 5,000 SYP</span>
                        <span id="stat-percentage" class="text-primary-600 font-medium">0%</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:col-span-9 space-y-6">
            <div id="tab-content-container" class="transition-all duration-300">
                <!-- Dynamic content will be injected here -->
                <div class="flex items-center justify-center h-64">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-12 h-12 border-4 border-primary-500 border-t-transparent rounded-full animate-spin">
                        </div>
                        <p class="text-slate-500 font-medium">Initializing Dashboard...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template: Clients Tab -->
    <template id="tpl-clients">
        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="flex justify-between items-end gap-4">
                <div class="flex-1 space-y-2">
                    <h2 class="text-2xl font-bold tracking-tight">Client Management</h2>
                    <p class="text-slate-500">Search users, assign packages, and manage freezes.</p>
                </div>
                <div class="relative w-full max-w-sm">
                    <input type="text" id="client-search" placeholder="Search by name or phone..."
                        class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none shadow-sm transition-all">
                    <svg class="w-5 h-5 absolute left-3 top-3.5 text-slate-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="glass-card rounded-2xl overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody id="client-table-body">
                        <!-- Rows injected here -->
                    </tbody>
                </table>
                <div id="client-pagination"
                    class="px-6 py-4 bg-slate-50/30 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                    <!-- Pagination buttons -->
                </div>
            </div>
        </div>
    </template>

    <!-- Template: Store Tab -->
    <template id="tpl-store">
        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="space-y-2">
                <h2 class="text-2xl font-bold tracking-tight">Store & Inventory</h2>
                <p class="text-slate-500">Manage merchandise sales and stock levels.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="store-grid">
                <!-- Product cards injected here -->
            </div>
        </div>
    </template>

    <!-- Template: Finance Tab -->
    <template id="tpl-finance">
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
                                <!--                                             // fix: use the current price approach
             -->
                                <label class="text-xs font-semibold text-slate-500 uppercase">Amount (SYP)</label>
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
    </template>
@endsection