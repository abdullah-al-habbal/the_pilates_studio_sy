<!-- resources/views/admin/scheduler/partial/header.blade.php -->
<header
    class="sticky top-0 z-30 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 shadow-sm px-4 sm:px-6 py-3 flex flex-wrap items-center gap-3">
    <a href="{{ url('/admin') }}"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 transition-colors mr-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Dashboard
    </a>

    <div class="h-5 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>

    <div class="flex items-center gap-2">
        <button id="btn-today" type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium
                   text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Today
        </button>

        <button id="btn-refresh" type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium
                   text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <svg id="refresh-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
        </button>

        <input type="date" id="input-date" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                   text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 shadow-sm
                   focus:border-primary-500 focus:ring-primary-500" />

        <select id="input-instructor" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                   text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 shadow-sm
                   focus:border-primary-500 focus:ring-primary-500">
            <option value="">All Instructors</option>
        </select>
    </div>

    <div class="ml-auto flex items-center gap-4 text-sm">
        <div class="flex items-center gap-3">
            <span id="resolved-date" class="font-bold text-gray-900 dark:text-white"></span>

            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 hidden lg:block"></div>

            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-primary-50 dark:bg-primary-900/30 border border-primary-100 dark:border-primary-800/50 transition-all">
                <div class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                </div>
                <span
                    class="text-xs font-black text-primary-700 dark:text-primary-300 tabular-nums uppercase tracking-tight">
                    <span id="sessions-count">0</span> Sessions
                </span>
            </div>
        </div>

        <div id="loading-badge"
            class="hidden flex items-center gap-2 text-primary-500 bg-primary-50 dark:bg-primary-900/20 px-3 py-1.5 rounded-xl border border-primary-100 dark:border-primary-800/50">
            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-[10px] font-black uppercase tracking-widest animate-pulse">Syncing</span>
        </div>
    </div>
</header>