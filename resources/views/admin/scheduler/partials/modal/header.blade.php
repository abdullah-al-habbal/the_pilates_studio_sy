{{-- resources/views/admin/scheduler/partials/modal/header.blade.php --}}
<div
    class="flex items-start justify-between px-8 py-6 border-b border-gray-100 dark:border-gray-800 shrink-0 bg-gray-50/50 dark:bg-gray-800/30">
    <div id="modal-header-content" class="transition-all duration-300">
        <h2 id="modal-title" class="text-xl font-black text-gray-900 dark:text-white tracking-tight">Session Management
        </h2>
        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
            <span id="modal-date"
                class="text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/40 px-2 py-0.5 rounded-md"></span>
            <span class="text-gray-300 dark:text-gray-600 text-xs">•</span>
            <span id="modal-time" class="text-xs font-bold text-gray-500 dark:text-gray-400 tabular-nums"></span>
            <span class="text-gray-300 dark:text-gray-600 text-xs">•</span>
            <span id="modal-instructor" class="text-xs font-medium text-gray-500 dark:text-gray-400"></span>
        </div>
    </div>
    <div id="modal-header-skeleton" class="hidden space-y-2 flex-1 mr-8">
        <div class="h-6 bg-gray-100 dark:bg-gray-800 rounded-lg w-2/3 animate-pulse"></div>
        <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded-lg w-1/2 animate-pulse"></div>
    </div>
    <button id="btn-close-modal" type="button"
        class="shrink-0 rounded-xl p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:white transition-all">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>