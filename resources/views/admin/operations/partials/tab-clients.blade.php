{{-- resources/views/admin/operations/partials/tab-clients.blade.php --}}
<div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex flex-wrap justify-between items-end gap-4">
        <div class="flex-1 space-y-1 min-w-0">
            <h2 class="text-2xl font-bold tracking-tight">{{ __('dashboard.operations_ui.clients.title') }}</h2>
            <p class="text-slate-500">{{ __('dashboard.operations_ui.clients.description') }}</p>
        </div>
        <div class="flex items-center gap-3 w-full max-w-xl">
            <button type="button" onclick="window.showCreateClient()"
                class="shrink-0 bg-primary-600 text-white px-5 py-3 rounded-xl font-bold text-sm hover:bg-primary-700 transition-colors">
                + {{ __('dashboard.operations_ui.clients.add_client_button') }}
            </button>
            <div class="relative flex-1">
            <input type="text" id="client-search" placeholder="{{ __('dashboard.operations_ui.clients.search_placeholder') }}"
                class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none shadow-sm transition-all">
            <svg class="w-5 h-5 absolute left-3 top-3.5 text-slate-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap gap-2" id="client-filter-pills">
        <button data-filter="best_user" onclick="applyClientFilter(this)"
            class="filter-pill px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
            🏆 {{ __('dashboard.operations_ui.clients.stats_best_user') }}
        </button>
        <button data-filter="most_active_booking" onclick="applyClientFilter(this)"
            class="filter-pill px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
            📦 {{ __('dashboard.operations_ui.clients.stats_most_active_booking') }}
        </button>
        <button data-filter="best_seller" onclick="applyClientFilter(this)"
            class="filter-pill px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
            💰 {{ __('dashboard.operations_ui.clients.stats_best_seller') }}
        </button>
        <button data-filter="most_attended" onclick="applyClientFilter(this)"
            class="filter-pill px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
            ✅ {{ __('dashboard.operations_ui.clients.stats_most_attended') }}
        </button>
    </div>
    <div class="glass-card rounded-2xl overflow-hidden shadow-sm">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('dashboard.operations_ui.clients.table_client') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('dashboard.operations_ui.clients.table_phone') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('dashboard.operations_ui.clients.table_status') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('dashboard.operations_ui.clients.table_current_package') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('dashboard.operations_ui.clients.table_sessions') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">{{ __('dashboard.operations_ui.clients.table_actions') }}
                    </th>
                </tr>
            </thead>
            <tbody id="client-table-body">
            </tbody>
        </table>
        <div id="client-pagination"
            class="px-6 py-4 bg-slate-50/30 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
        </div>
    </div>
</div>