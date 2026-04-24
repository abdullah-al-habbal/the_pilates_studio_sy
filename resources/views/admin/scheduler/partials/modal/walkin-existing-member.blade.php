{{-- resources/views/admin/scheduler/partials/modal/walkin-existing-member.blade.php --}}
<div id="walkin-existing-section" class="hidden space-y-4">
    <div class="relative">
        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 ml-1 mb-2 block">Quick Search &
            Add</label>
        <div
            class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl px-5 py-4 focus-within:border-primary-500 focus-within:bg-white dark:focus-within:bg-gray-900 transition-all shadow-inner">
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
            </svg>
            <input type="text" id="walkin-search" placeholder="Type member name or phone..."
                class="flex-1 bg-transparent text-sm font-bold text-gray-900 dark:text-white placeholder-gray-400 outline-none" />
            <div id="walkin-users-loading"
                class="hidden animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full"></div>
        </div>
        <div id="walkin-dropdown"
            class="hidden absolute z-20 mt-2 w-full max-h-56 overflow-y-auto bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl p-2 divide-y dark:divide-gray-700">
        </div>
    </div>

    <div id="walkin-selected-tags" class="flex flex-wrap gap-2 pt-2 transition-all"></div>

    <div id="walkin-existing-error"
        class="hidden p-4 rounded-xl bg-danger-50 dark:bg-danger-900/20 border border-danger-100 dark:border-danger-800 text-danger-700 dark:text-danger-400 text-[10px] font-bold">
        <span id="walkin-existing-error-msg"></span>
    </div>

    <button id="btn-submit-existing" type="button" disabled
        class="w-full py-4 bg-primary-600 hover:bg-primary-500 disabled:opacity-30 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-primary-500/20 active:scale-[0.98] transition-all">
        <div class="flex items-center justify-center gap-3">
            <span id="btn-submit-existing-text">Select Members</span>
        </div>
    </button>
</div>