<!-- resources\views\admin\operations\partials\sidebar.blade.php -->
<aside class="lg:col-span-3 space-y-4">
    <div class="glass-card rounded-2xl p-4">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 px-2">Navigation</h2>
        <nav class="space-y-1">
            <button data-tab="clients"
                class="w-full text-left px-4 py-3 rounded-xl transition-all flex items-center gap-3 hover:bg-slate-100 dark:hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
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
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Finance & Balance</span>
            </button>
            <button data-tab="notifications"
                class="w-full text-left px-4 py-3 rounded-xl transition-all flex items-center gap-3 hover:bg-slate-100 dark:hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11c0-2.21-1.343-4.088-3.293-4.753A1 1 0 0014 7h-4a1 1 0 00-.707 1.747C7.343 6.912 6 8.79 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z"></path>
                </svg>
                <span>Push Notifications</span>
            </button>
        </nav>
    </div>
</aside>
