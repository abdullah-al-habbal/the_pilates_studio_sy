<div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex justify-between items-end gap-4">
        <div class="flex-1 space-y-2">
            <h2 class="text-2xl font-bold tracking-tight">Client Management</h2>
            <p class="text-slate-500">Search users, assign packages, and manage freezes.</p>
        </div>
        <div class="relative w-full max-w-sm">
            <input type="text" id="client-search" placeholder="Search by name or phone..."
                class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none shadow-sm transition-all">
            <svg class="w-5 h-5 absolute left-3 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="client-table-body">
                <!-- Rows injected here -->
            </tbody>
        </table>
        <div id="client-pagination" class="px-6 py-4 bg-slate-50/30 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
            <!-- Pagination buttons -->
        </div>
    </div>
</div>
