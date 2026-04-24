// public/js/scheduler/templates.js
(function(S) {
    S.templates = {
        sessionCard: (session) => {
            const statusClass = session.is_full 
                ? 'bg-danger-50 dark:bg-danger-900/20 border-danger-100 dark:border-danger-800 text-danger-600 dark:text-danger-400' 
                : (session.fill_pct >= 75 
                    ? 'bg-warning-50 dark:bg-warning-900/20 border-warning-100 dark:border-warning-800 text-warning-600 dark:text-warning-400' 
                    : 'bg-primary-50 dark:bg-primary-900/20 border-primary-100 dark:border-primary-800 text-primary-600 dark:text-primary-400');
            
            const fillBarClass = session.is_full 
                ? 'bg-danger-500 shadow-[0_0_8px_rgba(239,68,68,0.4)]' 
                : (session.fill_pct >= 75 ? 'bg-warning-500' : 'bg-primary-500');

            return `
                <div class="group bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-xl hover:border-primary-200 dark:hover:border-primary-800 transition-all duration-300 flex items-center gap-4 px-6 py-5">
                    <div class="w-16 shrink-0 text-center">
                        <p class="text-sm font-extrabold text-primary-600 dark:text-primary-400 tabular-nums tracking-tight">${session.start_time}</p>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mt-1">${session.end_time}</p>
                        <div class="inline-flex items-center mt-2 px-1.5 py-0.5 rounded-md bg-gray-50 dark:bg-gray-800 text-[10px] font-bold text-gray-500">
                            <span>${session.duration_minutes}</span>m
                        </div>
                    </div>
                    <div class="w-px self-stretch bg-gray-100 dark:bg-gray-800 rounded-full shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-base text-gray-900 dark:text-white truncate group-hover:text-primary-600 transition-colors">${session.title}</h4>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="w-5 h-5 rounded-full bg-primary-50 dark:bg-primary-900/50 flex items-center justify-center text-[10px] font-bold text-primary-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">${session.instructor}</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-center w-24 hidden sm:block px-2">
                        <div class="flex items-end justify-center gap-1">
                            <span class="text-lg font-black text-gray-900 dark:text-white leading-none">${session.reserved}</span>
                            ${session.capacity > 0 ? `<span class="text-xs font-bold text-gray-400 mb-0.5">/ ${session.capacity}</span>` : ''}
                        </div>
                        ${session.capacity > 0 ? `
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 mt-2 relative overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 ease-out ${fillBarClass}" style="width:${session.fill_pct}%"></div>
                            </div>
                        ` : ''}
                        <p class="text-[10px] font-bold text-gray-400 mt-1.5 uppercase tracking-wider">${session.attended} Checked</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="hidden lg:inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest border px-3 py-1.5 rounded-xl shrink-0 transition-all ${statusClass}">
                            ${session.is_full ? 'Full' : (session.fill_pct >= 75 ? 'Limited' : 'Open')}
                        </span>
                        <button onclick="Scheduler.modal.open(${session.id})" type="button" class="group/btn relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary-500/20 transition-all hover:bg-primary-500 active:scale-95">
                            <svg class="h-4 w-4 transition-transform group-hover/btn:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <span class="hidden sm:inline">Manage</span>
                        </button>
                    </div>
                </div>
            `;
        },

        bookingCard: (b) => {
            const initial = b.user?.initial ?? '?';
            const name = b.user?.name ?? 'Anonymous User';
            const phone = b.user?.phone ?? '';
            const credits = b.user?.credits ?? 0;
            const attendance = b.attendance;
            const isPending = b._pending;

            const attendedBtnClass = attendance === 'attended' 
                ? 'bg-success-600 text-white ring-4 ring-success-500/20 shadow-lg shadow-success-500/30' 
                : 'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-success-50 dark:hover:bg-success-900/30 hover:text-success-600 dark:hover:text-success-400 border border-transparent hover:border-success-200 dark:hover:border-success-800';

            const missedBtnClass = attendance === 'missed' 
                ? 'bg-danger-600 text-white ring-4 ring-danger-500/20 shadow-lg shadow-danger-500/30' 
                : 'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-danger-50 dark:hover:bg-danger-900/30 hover:text-danger-600 dark:hover:text-danger-400 border border-transparent hover:border-danger-200 dark:hover:border-danger-800';

            return `
                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl shadow-sm hover:shadow-md hover:border-primary-100 dark:hover:border-primary-900/50 transition-all group">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/40 flex items-center justify-center text-primary-700 dark:text-primary-300 font-black text-sm shrink-0 border border-primary-100 dark:border-primary-800">${initial}</div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white truncate group-hover:text-primary-600 transition-colors">${name}</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <p class="text-[10px] font-bold text-gray-400 tabular-nums">${phone}</p>
                                ${credits > 0 ? `<span class="text-[9px] font-black uppercase tracking-tighter bg-success-50 dark:bg-success-900/30 text-success-600 dark:text-success-400 px-2 py-0.5 rounded-lg border border-success-100 dark:border-success-800/50">${credits} Credit(s)</span>` : ''}
                                ${attendance === 'attended' ? `<span class="text-[9px] font-black uppercase bg-success-500 text-white px-2 py-0.5 rounded-lg shadow-sm shadow-success-500/20">Checked In</span>` : ''}
                                ${attendance === 'missed' ? `<span class="text-[9px] font-black uppercase bg-danger-500 text-white px-2 py-0.5 rounded-lg shadow-sm shadow-danger-500/20">Missed</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-4">
                        <button onclick="Scheduler.events.toggleAttendance(${b.id}, 'attended')" ${isPending ? 'disabled' : ''} class="w-10 h-10 flex items-center justify-center rounded-xl transition-all disabled:opacity-30 ${attendedBtnClass}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button onclick="Scheduler.events.toggleAttendance(${b.id}, 'missed')" ${isPending ? 'disabled' : ''} class="w-10 h-10 flex items-center justify-center rounded-xl transition-all disabled:opacity-30 ${missedBtnClass}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            `;
        }
    };
})(window.Scheduler);
