{{-- resources/views/admin/scheduler/partials/modal/attendees-booking-card.blade.php --}}
<div class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl shadow-sm hover:shadow-md hover:border-primary-100 dark:hover:border-primary-900/50 transition-all group">
    <div class="flex items-center gap-4 min-w-0">
        <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/40 flex items-center justify-center text-primary-700 dark:text-primary-300 font-black text-sm shrink-0 border border-primary-100 dark:border-primary-800"
            x-text="b.user?.initial ?? '?'"></div>
        <div class="min-w-0">
            <p class="font-bold text-gray-900 dark:text-white truncate group-hover:text-primary-600 transition-colors" x-text="b.user?.name ?? 'Anonymous User'"></p>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <p class="text-[10px] font-bold text-gray-400 tabular-nums" x-text="b.user?.phone ?? ''"></p>
                <span x-show="b.user?.credits > 0" class="text-[9px] font-black uppercase tracking-tighter bg-success-50 dark:bg-success-900/30 text-success-600 dark:text-success-400 px-2 py-0.5 rounded-lg border border-success-100 dark:border-success-800/50">
                    <span x-text="b.user?.credits"></span> Credit(s)
                </span>
                <span x-show="b.attendance === 'attended'" class="text-[9px] font-black uppercase bg-success-500 text-white px-2 py-0.5 rounded-lg shadow-sm shadow-success-500/20">Checked In</span>
                <span x-show="b.attendance === 'missed'" class="text-[9px] font-black uppercase bg-danger-500 text-white px-2 py-0.5 rounded-lg shadow-sm shadow-danger-500/20">Missed</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 shrink-0 ml-4">
        <button @click="toggleAttendance(b, 'attended')" :disabled="b._pending"
            :class="b.attendance === 'attended' 
                ? 'bg-success-600 text-white ring-4 ring-success-500/20 shadow-lg shadow-success-500/30' 
                : 'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-success-50 dark:hover:bg-success-900/30 hover:text-success-600 dark:hover:text-success-400 border border-transparent hover:border-success-200 dark:hover:border-success-800'"
            class="w-10 h-10 flex items-center justify-center rounded-xl transition-all disabled:opacity-30">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button @click="toggleAttendance(b, 'missed')" :disabled="b._pending"
            :class="b.attendance === 'missed' 
                ? 'bg-danger-600 text-white ring-4 ring-danger-500/20 shadow-lg shadow-danger-500/30' 
                : 'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-danger-50 dark:hover:bg-danger-900/30 hover:text-danger-600 dark:hover:text-danger-400 border border-transparent hover:border-danger-200 dark:hover:border-danger-800'"
            class="w-10 h-10 flex items-center justify-center rounded-xl transition-all disabled:opacity-30">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
