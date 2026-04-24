{{-- resources/views/admin/scheduler/partials/modal/toast.blade.php --}}
<div id="modal-toast"
    class="hidden flex items-center gap-3 p-4 rounded-2xl bg-success-50 dark:bg-success-900/20 border border-success-100 dark:border-success-800/50 text-success-700 dark:text-success-400 text-sm font-bold shadow-sm">
    <div class="w-6 h-6 rounded-full bg-success-500 text-white flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
        </svg>
    </div>
    <span id="modal-toast-msg"></span>
</div>