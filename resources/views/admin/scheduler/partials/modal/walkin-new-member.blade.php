{{-- resources/views/admin/scheduler/partials/modal/walkin-new-member.blade.php --}}
<div id="walkin-new-section" class="hidden space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 ml-1">Full Identity <span
                    class="text-danger-500">*</span></label>
            <input type="text" id="input-fullname" placeholder="Name"
                class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl px-5 py-3.5 text-sm font-bold text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 outline-none transition-all" />
            <p id="err-fullname" class="hidden text-[10px] font-bold text-danger-500 ml-1"></p>
        </div>
        <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 ml-1">Mobile Line <span
                    class="text-danger-500">*</span></label>
            <input type="text" id="input-phone" placeholder="Number"
                class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl px-5 py-3.5 text-sm font-bold text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 outline-none transition-all" />
            <p id="err-phone" class="hidden text-[10px] font-bold text-danger-500 ml-1"></p>
        </div>
        <div class="space-y-2 sm:col-span-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 ml-1">Email (Optional)</label>
            <input type="email" id="input-email" placeholder="Email Address"
                class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl px-5 py-3.5 text-sm font-bold text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 outline-none transition-all" />
            <p id="err-email" class="hidden text-[10px] font-bold text-danger-500 ml-1"></p>
        </div>
    </div>
    <div id="err-general"
        class="hidden p-4 rounded-xl bg-danger-50 dark:bg-danger-900/20 border border-danger-100 dark:border-danger-800 text-danger-700 dark:text-danger-400 text-[10px] font-bold">
    </div>
    <button id="btn-submit-new" type="button"
        class="w-full py-4 bg-primary-600 hover:bg-primary-500 disabled:opacity-30 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-primary-500/20 active:scale-[0.98] transition-all">
        <div class="flex items-center justify-center gap-3">
            <span id="btn-submit-new-text">Register & Add Walk-in</span>
        </div>
    </button>
</div>