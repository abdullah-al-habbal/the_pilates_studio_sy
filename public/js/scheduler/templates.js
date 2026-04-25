// public/js/scheduler/templates.js
(function (S) {
    'use strict';

    function fillBarColor(fillPct, isFull) {
        if (isFull)         return 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.35)]';
        if (fillPct >= 75)  return 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.3)]';
        if (fillPct >= 50)  return 'bg-amber-500';
        return 'bg-red-400'; // low fill
    }

    S.templates = {
        sessionCard: (session) => {
            const statusClass = session.is_full
                ? 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800 text-red-600 dark:text-red-400'
                : session.fill_pct >= 75
                    ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-800 text-amber-600 dark:text-amber-400'
                    : 'bg-primary-50 dark:bg-primary-900/20 border-primary-100 dark:border-primary-800 text-primary-600 dark:text-primary-400';

            const barColor = fillBarColor(session.fill_pct, session.is_full);

            return `
<div class="group bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700
            shadow-sm hover:shadow-xl hover:border-primary-200 dark:hover:border-primary-800
            transition-all duration-300 flex items-center gap-4 px-6 py-5">

    <div class="w-16 shrink-0 text-center">
        <p class="text-sm font-extrabold text-primary-600 dark:text-primary-400 tabular-nums tracking-tight">${session.start_time}</p>
        <p class="text-[10px] uppercase font-bold text-gray-400 mt-1">${session.end_time}</p>
        <div class="inline-flex items-center mt-2 px-1.5 py-0.5 rounded-md bg-gray-50 dark:bg-gray-800 text-[10px] font-bold text-gray-500">
            ${session.duration_minutes}m
        </div>
    </div>

    <div class="w-px self-stretch bg-gray-100 dark:bg-gray-800 rounded-full shrink-0"></div>

    <div class="flex-1 min-w-0">
        <h4 class="font-bold text-base text-gray-900 dark:text-white truncate group-hover:text-primary-600 transition-colors">
            ${session.title}
        </h4>
        <div class="flex items-center gap-2 mt-1">
            <div class="w-5 h-5 rounded-full bg-primary-50 dark:bg-primary-900/50 flex items-center justify-center">
                <svg class="w-3 h-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
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
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 mt-2 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-700 ease-out ${barColor}"
                 style="width:${session.fill_pct}%"></div>
        </div>` : ''}
        <p class="text-[10px] font-bold text-gray-400 mt-1.5 uppercase tracking-wider">${session.attended} Checked</p>
    </div>

    <div class="flex items-center gap-4">
        <span class="hidden lg:inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest
                     border px-3 py-1.5 rounded-xl shrink-0 transition-all ${statusClass}">
            ${session.is_full ? 'Full' : (session.fill_pct >= 75 ? 'Limited' : 'Open')}
        </span>
        <button onclick="Scheduler.modal.open(${session.id})" type="button"
                class="group/btn relative inline-flex items-center justify-center gap-2 overflow-hidden
                       rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white
                       shadow-lg shadow-primary-500/20 transition-all hover:bg-primary-500 active:scale-95">
            <svg class="h-4 w-4 transition-transform group-hover/btn:rotate-12"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="hidden sm:inline">Manage</span>
        </button>
    </div>
</div>`;
        },

        bookingCard: (b) => {
            const attendance = b.attendance;
            const isPending  = b._pending;
            const isLocked   = attendance === 'attended' || attendance === 'missed';

            const cardClass = attendance === 'attended'
                ? 'border-green-200 dark:border-green-800/60 bg-green-50/40 dark:bg-green-900/10'
                : attendance === 'missed'
                    ? 'border-red-200 dark:border-red-800/60 bg-red-50/40 dark:bg-red-900/10'
                    : 'border-gray-100 dark:border-gray-800';

            const avatarClass = attendance === 'attended'
                ? 'bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300 border-green-100 dark:border-green-800'
                : attendance === 'missed'
                    ? 'bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-red-100 dark:border-red-800'
                    : 'bg-primary-50 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 border-primary-100 dark:border-primary-800';

            const initial = b.user?.initial ?? '?';
            const name    = b.user?.name    ?? '[MISSING:user.fullname]';
            const phone   = b.user?.phone   ?? '';
            const credits = b.user?.credits ?? 0;

            const creditsBadge = credits > 0
                ? `<span class="text-[9px] font-black uppercase tracking-tighter
                               bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400
                               px-2 py-0.5 rounded-lg border border-green-100 dark:border-green-800/50
                               shadow-sm">
                       ${credits} Credit${credits !== 1 ? 's' : ''}
                   </span>`
                : '';

            const attendedBadge = attendance === 'attended'
                ? `<span class="text-[9px] font-black uppercase tracking-tight
                               bg-green-500 text-white px-2 py-0.5 rounded-lg
                               shadow-sm shadow-green-500/30 animate-pulse-once">
                       ✓ Checked In
                   </span>`
                : '';

            const missedBadge = attendance === 'missed'
                ? `<span class="text-[9px] font-black uppercase tracking-tight
                               bg-red-500 text-white px-2 py-0.5 rounded-lg
                               shadow-sm shadow-red-500/30">
                       Missed
                   </span>`
                : '';

            const actionButtons = isLocked ? '' : `
                <button onclick="Scheduler.events.toggleAttendance(${b.id}, 'attended')"
                        ${isPending ? 'disabled' : ''}
                        title="Mark as attended"
                        class="w-10 h-10 flex items-center justify-center rounded-xl transition-all
                               disabled:opacity-30 disabled:cursor-not-allowed
                               bg-gray-50 dark:bg-gray-800 text-gray-400
                               hover:bg-green-50 dark:hover:bg-green-900/30
                               hover:text-green-600 dark:hover:text-green-400
                               border border-transparent hover:border-green-200 dark:hover:border-green-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
                <button onclick="Scheduler.events.toggleAttendance(${b.id}, 'missed')"
                        ${isPending ? 'disabled' : ''}
                        title="Mark as missed"
                        class="w-10 h-10 flex items-center justify-center rounded-xl transition-all
                               disabled:opacity-30 disabled:cursor-not-allowed
                               bg-gray-50 dark:bg-gray-800 text-gray-400
                               hover:bg-red-50 dark:hover:bg-red-900/30
                               hover:text-red-600 dark:hover:text-red-400
                               border border-transparent hover:border-red-200 dark:hover:border-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;

            return `
<div class="flex items-center justify-between p-4 bg-white dark:bg-gray-900
            border ${cardClass} rounded-2xl shadow-sm
            hover:shadow-md transition-all group">
    <div class="flex items-center gap-4 min-w-0">
        <div class="w-12 h-12 rounded-xl ${avatarClass}
                    flex items-center justify-center font-black text-sm shrink-0 border">
            ${initial}
        </div>
        <div class="min-w-0">
            <p class="font-bold text-gray-900 dark:text-white truncate group-hover:text-primary-600 transition-colors">
                ${name}
            </p>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                ${phone ? `<p class="text-[10px] font-bold text-gray-400 tabular-nums">${phone}</p>` : ''}
                ${creditsBadge}
                ${attendedBadge}
                ${missedBadge}
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2 shrink-0 ml-4">
        ${actionButtons}
    </div>
</div>`;
        },
    };

})(window.Scheduler);
