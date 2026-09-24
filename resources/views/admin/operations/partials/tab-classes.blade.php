<div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex flex-wrap justify-between items-end gap-4">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold tracking-tight">{{ __('dashboard.operations_ui.classes.title') }}</h2>
            <p class="text-slate-500">{{ __('dashboard.operations_ui.classes.description') }}</p>
        </div>
        <button type="button" id="classes-create-button"
            class="bg-primary-600 text-white px-5 py-3 rounded-xl font-bold text-sm hover:bg-primary-700 transition-colors">
            + {{ __('dashboard.operations_ui.classes.create') }}
        </button>
    </div>
    <div class="glass-card rounded-2xl p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <input id="classes-search" type="search" placeholder="{{ __('dashboard.operations_ui.classes.search') }}"
            class="md:col-span-2 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary-500">
        <select id="classes-status-filter" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5"></select>
        <select id="classes-scope-filter" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5">
            <option value="active">{{ __('dashboard.operations_ui.classes.scope_active') }}</option>
            <option value="all">{{ __('dashboard.operations_ui.classes.scope_all') }}</option>
            <option value="trashed">{{ __('dashboard.operations_ui.classes.scope_trashed') }}</option>
        </select>
        <select id="classes-per-page" aria-label="{{ __('dashboard.operations_ui.classes.per_page') }}"
            class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5">
            <option value="10">10 {{ __('dashboard.operations_ui.classes.per_page') }}</option>
            <option value="20" selected>20 {{ __('dashboard.operations_ui.classes.per_page') }}</option>
            <option value="50">50 {{ __('dashboard.operations_ui.classes.per_page') }}</option>
        </select>
    </div>
    <div class="glass-card rounded-2xl overflow-x-auto shadow-sm">
        <table class="w-full text-left min-w-[760px]">
            <thead><tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                <th class="px-5 py-4 text-xs font-bold text-slate-500 uppercase">{{ __('dashboard.operations_ui.classes.table_class') }}</th>
                <th class="px-5 py-4 text-xs font-bold text-slate-500 uppercase">{{ __('dashboard.operations_ui.classes.table_schedule') }}</th>
                <th class="px-5 py-4 text-xs font-bold text-slate-500 uppercase">{{ __('dashboard.operations_ui.classes.table_capacity') }}</th>
                <th class="px-5 py-4 text-xs font-bold text-slate-500 uppercase">{{ __('dashboard.operations_ui.classes.table_status') }}</th>
                <th class="px-5 py-4 text-xs font-bold text-slate-500 uppercase text-right">{{ __('dashboard.operations_ui.classes.table_actions') }}</th>
            </tr></thead>
            <tbody id="classes-table-body"></tbody>
        </table>
        <div id="classes-pagination" class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center"></div>
    </div>
</div>
