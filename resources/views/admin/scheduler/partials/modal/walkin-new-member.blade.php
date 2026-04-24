{{-- resources/views/admin/scheduler/partials/modal/walkin-new-member.blade.php --}}
<div x-show="walkin.mode === 'new'" class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 ml-1">Full Identity <span
                    class="text-danger-500">*</span></label>
            <input type="text" x-model="walkin.newUser.fullname" placeholder="Name"
                class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl px-5 py-3.5 text-sm font-bold text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 outline-none transition-all"
                :class="walkin.newErrors.fullname ? 'border-danger-500' : ''" />
            <p x-show="walkin.newErrors.fullname" class="text-[10px] font-bold text-danger-500 ml-1"
                x-text="walkin.newErrors.fullname?.[0]"></p>
        </div>
        <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 ml-1">Mobile Line <span
                    class="text-danger-500">*</span></label>
            <input type="text" x-model="walkin.newUser.phone_number" placeholder="Number"
                class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl px-5 py-3.5 text-sm font-bold text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 outline-none transition-all"
                :class="walkin.newErrors.phone_number ? 'border-danger-500' : ''" />
            <p x-show="walkin.newErrors.phone_number" class="text-[10px] font-bold text-danger-500 ml-1"
                x-text="walkin.newErrors.phone_number?.[0]"></p>
        </div>
    </div>

    <button @click="submitNewWalkIn()" type="button"
        :disabled="!walkin.newUser.fullname || !walkin.newUser.phone_number || walkin.submitting"
        class="w-full py-4 bg-primary-600 hover:bg-primary-500 disabled:opacity-30 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-primary-500/20 active:scale-[0.98] transition-all">
        <div class="flex items-center justify-center gap-3">
            <svg x-show="walkin.submitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>Register & Add Walk-in</span>
        </div>
    </button>
</div>