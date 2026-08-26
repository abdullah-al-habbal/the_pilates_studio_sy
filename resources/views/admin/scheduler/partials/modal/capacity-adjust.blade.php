{{-- resources/views/admin/scheduler/partials/modal/capacity-adjust.blade.php --}}
<div id="capacity-modal-backdrop" class="hidden fixed inset-0 z-[100] bg-gray-900/70 backdrop-blur-sm"></div>
<div id="capacity-modal-panel" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 pointer-events-none">
    <div class="relative w-full max-w-md pointer-events-auto
                bg-white dark:bg-gray-900 rounded-3xl shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)]
                ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden border border-white/10">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
            <div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">{{ __('dashboard.scheduler_ui.capacity_modal.title') }}</h3>
                <p id="capacity-modal-class-name" class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
            <button id="btn-close-capacity-modal" type="button"
                class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-5">
            {{-- Info cards --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-3 text-center border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('dashboard.scheduler_ui.capacity_modal.current') }}</p>
                    <p id="capacity-modal-current" class="text-xl font-black text-gray-900 dark:text-white">—</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-3 text-center border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('dashboard.scheduler_ui.modal.reserved') }}</p>
                    <p id="capacity-modal-reserved" class="text-xl font-black text-primary-600 dark:text-primary-400">—</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-3 text-center border border-gray-100 dark:border-gray-800">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('dashboard.scheduler_ui.capacity_modal.minimum') }}</p>
                    <p id="capacity-modal-min" class="text-xl font-black text-amber-600 dark:text-amber-400">—</p>
                </div>
            </div>

            {{-- Error message --}}
            <div id="capacity-modal-error" class="hidden flex items-center gap-3 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z" />
                </svg>
                <p id="capacity-modal-error-msg" class="text-xs font-medium text-red-600 dark:text-red-400"></p>
            </div>

            {{-- New capacity input --}}
            <div>
                <label for="capacity-input" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __('dashboard.scheduler_ui.capacity_modal.new_capacity_label') }}</label>
                <input id="capacity-input" type="number" min="1" step="1"
                    class="w-full rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                           px-4 py-3 text-lg font-black text-gray-900 dark:text-white text-center
                           focus:border-primary-500 focus:ring-0 focus:outline-none transition-colors
                           dark:[color-scheme:dark]" />
            </div>

            {{-- Reason textarea --}}
            <div>
                <label for="capacity-reason" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __('dashboard.scheduler_ui.capacity_modal.reason_label') }}</label>
                <textarea id="capacity-reason" rows="2" maxlength="500"
                    class="w-full rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                           px-4 py-3 text-sm font-medium text-gray-900 dark:text-white
                           focus:border-primary-500 focus:ring-0 focus:outline-none transition-colors resize-none"
                    placeholder="{{ __('dashboard.scheduler_ui.capacity_modal.reason_placeholder') }}"></textarea>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
            <button id="btn-cancel-capacity" type="button"
                class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-300
                       hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                {{ __('dashboard.scheduler_ui.capacity_modal.cancel') }}
            </button>
            <button id="btn-save-capacity" type="button"
                class="px-5 py-2.5 rounded-xl text-sm font-bold text-white
                       bg-primary-600 shadow-lg shadow-primary-500/20
                       hover:bg-primary-500 active:scale-95 transition-all
                       disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                <span id="btn-save-capacity-text">{{ __('dashboard.scheduler_ui.capacity_modal.save') }}</span>
            </button>
        </div>
    </div>
</div>
