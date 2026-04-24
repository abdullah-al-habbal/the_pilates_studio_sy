{{-- resources/views/admin/scheduler/partials/main/empty-state.blade.php --}}
<div x-show="!loading && !error && sessions.length === 0" x-cloak
    class="flex flex-col items-center justify-center py-24 gap-4 text-gray-400 dark:text-gray-600">
    <div
        class="w-20 h-20 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center border border-gray-100 dark:border-gray-700">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
        </svg>
    </div>
    <div class="text-center">
        <p class="text-base font-medium text-gray-700 dark:text-gray-300">Quiet day ahead</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">No sessions scheduled for this date.</p>
    </div>
</div>