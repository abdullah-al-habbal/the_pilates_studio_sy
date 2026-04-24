{{-- resources/views/admin/scheduler/partials/main/error-state.blade.php --}}
<div id="error-state" class="hidden flex flex-col items-center justify-center py-24 gap-4 text-center">
    <div
        class="w-16 h-16 bg-danger-50 dark:bg-danger-900/30 text-danger-500 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <div>
        <h3 id="error-title" class="text-lg font-bold text-gray-900 dark:text-white">System Error</h3>
        <p id="error-message" class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-xs">We encountered a problem
            loading sessions. Please try again.</p>
    </div>
    <button id="btn-retry"
        class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-500 transition-colors">
        Retry Connection
    </button>
</div>