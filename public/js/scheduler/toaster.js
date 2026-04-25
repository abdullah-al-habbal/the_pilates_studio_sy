// public/js/scheduler/toaster.js
(function (S) {
    'use strict';

    let _counter = 0;

    const ICONS = {
        success: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>',
        error:   '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
        warning: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        info:    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };

    const VARIANTS = {
        success: { border: 'border-l-4 border-green-500',  icon: 'bg-green-500',  msg: 'text-green-800 dark:text-green-300' },
        error:   { border: 'border-l-4 border-red-500',    icon: 'bg-red-500',    msg: 'text-red-800 dark:text-red-300'   },
        warning: { border: 'border-l-4 border-amber-500',  icon: 'bg-amber-500',  msg: 'text-amber-800 dark:text-amber-300' },
        info:    { border: 'border-l-4 border-blue-500',   icon: 'bg-blue-500',   msg: 'text-blue-800 dark:text-blue-300'  },
    };

    function dismiss(el) {
        el.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => el.remove(), 300);
    }

    S.toaster = {
        show(message, variant = 'success', duration = 4000) {
            const stack = document.getElementById('toaster-stack');
            if (!stack) return;

            const v  = VARIANTS[variant] ?? VARIANTS.info;
            const id = `toast-${++_counter}`;

            const el = document.createElement('div');
            el.id        = id;
            el.className = [
                'pointer-events-auto flex items-start gap-3 w-80 max-w-sm px-4 py-3 rounded-2xl',
                'bg-white dark:bg-gray-900',
                v.border,
                'shadow-xl ring-1 ring-black/5 dark:ring-white/5',
                'translate-x-full opacity-0 transition-all duration-300 ease-out',
            ].join(' ');

            el.innerHTML = `
                <div class="w-6 h-6 rounded-full ${v.icon} text-white flex items-center justify-center shrink-0 mt-0.5">
                    ${ICONS[variant] ?? ICONS.info}
                </div>
                <p class="flex-1 text-sm font-bold ${v.msg} leading-snug mt-0.5">${message}</p>
                <button data-dismiss
                        aria-label="Dismiss"
                        class="shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;

            stack.appendChild(el);

            requestAnimationFrame(() => requestAnimationFrame(() => {
                el.classList.remove('translate-x-full', 'opacity-0');
            }));

            el.querySelector('[data-dismiss]').addEventListener('click', () => dismiss(el));
            setTimeout(() => dismiss(el), duration);
        },

        success: (msg, ms) => S.toaster.show(msg, 'success', ms),
        error:   (msg, ms) => S.toaster.show(msg, 'error',   ms),
        warning: (msg, ms) => S.toaster.show(msg, 'warning', ms),
        info:    (msg, ms) => S.toaster.show(msg, 'info',    ms),
    };

})(window.Scheduler);
