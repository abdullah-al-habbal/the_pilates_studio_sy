{{-- resources/views/admin/scheduler/partials/modal/walkin-tab.blade.php --}}
<div x-show="modal.tab === 'walkin'" x-cloak class="space-y-6">
    <div x-show="modal.session?.is_full"
        class="flex items-center gap-4 p-5 rounded-3xl bg-danger-50 dark:bg-danger-900/20 border border-danger-100 dark:border-danger-800 text-danger-700 dark:text-danger-400 shadow-sm">
        <div class="w-10 h-10 rounded-2xl bg-danger-500 text-white flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="font-black text-sm uppercase tracking-tight">Full Capacity Reached</p>
            <p class="text-xs font-medium opacity-80 mt-0.5">Please check for cancellations or adjust total spots.</p>
        </div>
    </div>

    <div x-show="!modal.session?.is_full" class="space-y-6 transition-all duration-500">
        {{-- Mode Selection --}}
        <div class="flex p-1.5 bg-gray-100 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
            <button @click="walkin.mode = 'existing'" type="button"
                :class="walkin.mode === 'existing' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xl shadow-black/10' : 'text-gray-500 dark:text-gray-400'"
                class="flex-1 py-3 text-xs font-black uppercase tracking-widest rounded-xl transition-all duration-300">
                Existing
            </button>
            <button @click="walkin.mode = 'new'" type="button"
                :class="walkin.mode === 'new' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-xl shadow-black/10' : 'text-gray-500 dark:text-gray-400'"
                class="flex-1 py-3 text-xs font-black uppercase tracking-widest rounded-xl transition-all duration-300">
                New Member
            </button>
        </div>

        @include('admin.scheduler.partials.modal.walkin-existing-member')
        @include('admin.scheduler.partials.modal.walkin-new-member')
    </div>
</div>
