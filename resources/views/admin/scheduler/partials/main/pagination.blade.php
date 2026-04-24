{{-- resources/views/admin/scheduler/partials/main/pagination.blade.php --}}
<div id="pagination"
    class="hidden flex items-center justify-between bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
    <button id="btn-prev"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M15 19l-7-7 7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Prev
    </button>

    <div class="flex items-center gap-1.5">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Page</span>
        <span id="page-current"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 font-bold text-sm">1</span>
        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">of</span>
        <span id="page-last" class="text-sm font-bold text-gray-700 dark:text-gray-300">1</span>
    </div>

    <button id="btn-next"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed transition-all">
        Next
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
</div>