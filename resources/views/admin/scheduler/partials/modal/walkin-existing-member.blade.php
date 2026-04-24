{{-- resources/views/admin/scheduler/partials/modal/walkin-existing-member.blade.php --}}
<div x-show="walkin.mode === 'existing'" class="space-y-4">
    <div class="relative">
        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 ml-1 mb-2 block">Quick Search &
            Add</label>
        <div
            class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl px-5 py-4 focus-within:border-primary-500 focus-within:bg-white dark:focus-within:bg-gray-900 transition-all shadow-inner">
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
            </svg>
            <input type="text" x-model="walkin.search" @focus="walkin.dropdownOpen = true"
                @input="walkin.dropdownOpen = true" placeholder="Type member name or phone..."
                class="flex-1 bg-transparent text-sm font-bold text-gray-900 dark:text-white placeholder-gray-400 outline-none" />
            <div x-show="walkin.usersLoading"
                class="animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full"></div>
        </div>
        <div x-show="walkin.dropdownOpen && filteredUsers().length > 0" @click.outside="walkin.dropdownOpen = false"
            class="absolute z-20 mt-2 w-full max-h-56 overflow-y-auto bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl p-2 divide-y dark:divide-gray-700">
            <template x-for="u in filteredUsers()" :key="u.id">
                <button @click="selectUser(u)" type="button"
                    class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-primary-900/30 hover:text-primary-600 rounded-xl transition-all">
                    <span x-text="u.label"></span>
                </button>
            </template>
        </div>
    </div>
    <div x-show="walkin.selected.length > 0" class="flex flex-wrap gap-2 pt-2 transition-all">
        <template x-for="u in walkin.selected" :key="u.id">
            <div
                class="inline-flex items-center gap-2 bg-primary-600 text-white text-[11px] font-black uppercase px-4 py-2 rounded-xl shadow-lg shadow-primary-500/20">
                <span x-text="u.label"></span>
                <button @click="removeSelectedUser(u.id)" type="button"
                    class="hover:text-primary-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>
    <button @click="submitExistingWalkIn()" type="button" :disabled="walkin.selected.length === 0 || walkin.submitting"
        class="w-full py-4 bg-primary-600 hover:bg-primary-500 disabled:opacity-30 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-primary-500/20 active:scale-[0.98] transition-all">
        <div class="flex items-center justify-center gap-3">
            <svg x-show="walkin.submitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span
                x-text="walkin.submitting ? 'Adding...' : (walkin.selected.length > 0 ? 'Confirm ' + walkin.selected.length + ' Walk-ins' : 'Select Members')"></span>
        </div>
    </button>
</div>