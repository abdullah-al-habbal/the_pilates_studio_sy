{{-- resources/views/admin/scheduler/partials/modal/toast.blade.php --}}
<div x-show="modal.successMsg" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
    class="flex items-center gap-3 p-4 rounded-2xl bg-success-50 dark:bg-success-900/20 border border-success-100 dark:border-success-800/50 text-success-700 dark:text-success-400 text-sm font-bold shadow-sm">
    <div class="w-6 h-6 rounded-full bg-success-500 text-white flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
        </svg>
    </div>
    <span x-text="modal.successMsg"></span>
</div>