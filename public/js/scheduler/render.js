// public/js/scheduler/render.js
(function (S) {
    'use strict';

    const { ui, templates, state } = S;

    S.render = {

        all: () => {
            S.render.header();
            S.render.states();
            S.render.sessionList();
            S.render.pagination();
            S.render.modal();
        },

        instructorDropdown: () => {
            const select = ui.$('input-instructor');
            if (!select) return;

            select.innerHTML = '<option value="">All Instructors</option>' +
                state.instructors.map(i => `
                    <option value="${i.id}" ${state.selectedInstructorId == i.id ? 'selected' : ''}>
                        ${i.name}
                    </option>
                `).join('');
        },

        header: () => {
            ui.text('resolved-date', state.resolvedDate);
            ui.text('sessions-count', state.sessions.length);
            if (state.loading) {
                ui.show('loading-badge');
                ui.cls('refresh-icon', 'animate-spin', 'add');
            } else {
                ui.hide('loading-badge');
                ui.cls('refresh-icon', 'animate-spin', 'remove');
            }
        },

        states: () => {
            ui.hide('error-state');
            ui.hide('empty-state');
            ui.hide('skeleton-loader');
            ui.hide('session-list');

            if (state.loading && state.sessions.length === 0) {
                ui.show('skeleton-loader');
            } else if (state.error) {
                ui.show('error-state');
                ui.text('error-title',   state.error.title);
                ui.text('error-message', state.error.message);
            } else if (state.sessions.length === 0) {
                ui.show('empty-state');
            } else {
                ui.show('session-list');
            }
        },

        sessionList: () => {
            ui.html('session-list', state.sessions.map(templates.sessionCard).join(''));
        },

        pagination: () => {
            const { current_page, last_page } = state.meta;
            if (last_page <= 1 && state.sessions.length === 0) {
                ui.hide('pagination');
                return;
            }
            ui.show('pagination');
            ui.text('page-current', current_page);
            ui.text('page-last',    last_page);
            const prev = ui.$('btn-prev');
            const next = ui.$('btn-next');
            if (prev) prev.disabled = current_page <= 1;
            if (next) next.disabled = current_page >= last_page;
        },

        modal: () => {
            if (!state.modal.show) {
                ui.hide('modal-backdrop');
                ui.hide('modal-panel');
                document.body.style.overflow = '';
                return;
            }
            ui.show('modal-backdrop');
            ui.show('modal-panel');
            document.body.style.overflow = 'hidden';
            S.render.modalHeader();
            S.render.modalTabs();
            S.render.attendeesTab();
            S.render.walkinTab();
            S.render.modalToast();
        },

        modalHeader: () => {
            if (state.modal.loading && !state.modal.session) {
                ui.show('modal-header-skeleton');
                ui.hide('modal-header-content');
            } else {
                ui.hide('modal-header-skeleton');
                ui.show('modal-header-content');
                const s = state.modal.session;
                if (s) {
                    ui.text('modal-title',      s.title);
                    ui.text('modal-date',        s.date);
                    ui.text('modal-time',        `${s.start_time} – ${s.end_time}`);
                    ui.text('modal-instructor',  s.instructor);
                }
            }
        },

        modalTabs: () => {
            const tab          = state.modal.tab;
            const attendeesBtn = ui.$('tab-btn-attendees');
            const walkinBtn    = ui.$('tab-btn-walkin');
            const BASE         = 'flex-1 flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl text-sm font-black transition-all duration-300';
            const ACTIVE       = `${BASE} bg-primary-600 text-white shadow-lg shadow-primary-500/30 ring-4 ring-primary-500/10`;
            const INACTIVE     = `${BASE} text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800`;

            if (tab === 'attendees') {
                attendeesBtn?.setAttribute('class', ACTIVE);
                walkinBtn?.setAttribute('class', INACTIVE);
                ui.show('tab-attendees');
                ui.hide('tab-walkin');
            } else {
                attendeesBtn?.setAttribute('class', INACTIVE);
                walkinBtn?.setAttribute('class', ACTIVE);
                ui.hide('tab-attendees');
                ui.show('tab-walkin');
            }

            ui.text('attendees-count', state.modal.bookings.length);
        },

        attendeesTab: () => {
            const s = state.modal.session;

            ui.text('attendees-count', state.modal.bookings.length);

            if (!s) return;

            if (s.capacity > 0) {
                ui.show('capacity-bar-wrap');
                ui.text('capacity-reserved', s.reserved);
                ui.text('capacity-total',    s.capacity);

                const fillEl = ui.$('capacity-bar-fill');
                if (fillEl) {
                    fillEl.style.width = `${s.fill_pct ?? 0}%`;

                    const colorClass = s.is_full || (s.fill_pct ?? 0) >= 100
                        ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.35)]'
                        : (s.fill_pct ?? 0) >= 75
                            ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.3)]'
                            : (s.fill_pct ?? 0) >= 50
                                ? 'bg-amber-500'
                                : 'bg-red-400';

                    fillEl.className = `h-full rounded-full transition-all duration-1000 ease-out ${colorClass}`;
                }
            } else {
                ui.hide('capacity-bar-wrap');
            }

            ui.hide('attendees-skeleton');
            ui.hide('attendees-error');
            ui.hide('attendees-empty');
            ui.hide('attendees-list');

            if (state.modal.loading && state.modal.bookings.length === 0) {
                ui.show('attendees-skeleton');
            } else if (state.modal.error) {
                ui.show('attendees-error');
                ui.text('attendees-error-message', state.modal.error);
            } else if (state.modal.bookings.length === 0) {
                ui.show('attendees-empty');
            } else {
                ui.show('attendees-list');
                ui.html('attendees-list', state.modal.bookings.map(templates.bookingCard).join(''));
            }
        },

        walkinTab: () => {
            const s = state.modal.session;
            if (!s) return;

            if (s.is_full) {
                ui.show('walkin-full-alert');
                ui.hide('walkin-form');
            } else {
                ui.hide('walkin-full-alert');
                ui.show('walkin-form');
                S.render.walkinExisting();
                S.render.walkinNew();
            }
        },

        walkinExisting: () => {
            const w          = state.walkin;
            const existingBtn = ui.$('walkin-mode-existing');
            const newBtn      = ui.$('walkin-mode-new');
            const BASE_BTN    = 'flex-1 py-2 px-4 rounded-lg text-xs font-bold transition-all';
            const ACTIVE_BTN  = `${BASE_BTN} bg-primary-600 text-white shadow-md`;
            const INACT_BTN   = `${BASE_BTN} text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800`;

            if (w.mode === 'existing') {
                existingBtn?.setAttribute('class', ACTIVE_BTN);
                newBtn?.setAttribute('class', INACT_BTN);
                ui.show('walkin-existing-section');
                ui.hide('walkin-new-section');
            } else {
                existingBtn?.setAttribute('class', INACT_BTN);
                newBtn?.setAttribute('class', ACTIVE_BTN);
                ui.hide('walkin-existing-section');
                ui.show('walkin-new-section');
            }

            w.usersLoading ? ui.show('walkin-users-loading') : ui.hide('walkin-users-loading');

            if (w.dropdownOpen) {
                ui.show('walkin-dropdown');
                const users = S.walkin.filter();
                if (users.length === 0) {
                    ui.html('walkin-dropdown',
                        '<div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 italic">No members found...</div>');
                } else {
                    ui.html('walkin-dropdown', users.map(u => `
                        <button onclick="Scheduler.walkin.select(${JSON.stringify(u).replace(/"/g, '&quot;')})"
                                class="w-full text-left px-4 py-3 rounded-xl
                                       hover:bg-primary-50 dark:hover:bg-primary-900/20
                                       transition-colors flex items-center justify-between group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800
                                            flex items-center justify-center text-[10px] font-black text-gray-500
                                            group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40
                                            group-hover:text-primary-600">${u.initial ?? u.label.charAt(0).toUpperCase()}</div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white
                                          group-hover:text-primary-600">${u.label}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-primary-500
                                        group-hover:translate-x-0.5 transition-all"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                      d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    `).join(''));
                }
            } else {
                ui.hide('walkin-dropdown');
            }

            ui.html('walkin-selected-tags', w.selected.map(u => `
                <div class="inline-flex items-center gap-2 bg-primary-50 dark:bg-primary-900/30
                            text-primary-700 dark:text-primary-300 px-3 py-1.5 rounded-xl
                            border border-primary-100 dark:border-primary-800/50">
                    <span class="text-xs font-bold">${u.label}</span>
                    <button onclick="Scheduler.walkin.remove(${u.id})"
                            class="hover:text-primary-900 dark:hover:text-primary-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            `).join(''));

            if (w.error) {
                ui.show('walkin-existing-error');
                ui.text('walkin-existing-error-msg', w.error);
            } else {
                ui.hide('walkin-existing-error');
            }

            const submitBtn = ui.$('btn-submit-existing');
            if (submitBtn) {
                submitBtn.disabled = w.selected.length === 0 || w.submitting;
                ui.text('btn-submit-existing-text',
                    w.submitting
                        ? 'Adding...'
                        : w.selected.length > 0
                            ? `Confirm ${w.selected.length} Walk-in${w.selected.length !== 1 ? 's' : ''}`
                            : 'Select Members'
                );
            }
        },

        walkinNew: () => {
            const w         = state.walkin;
            const submitBtn = ui.$('btn-submit-new');
            if (submitBtn) submitBtn.disabled = w.submitting;
            ui.text('btn-submit-new-text', w.submitting ? 'Creating...' : 'Register & Add Walk-in');

            const FIELD_MAP = {
                fullname:     'fullname',
                phone_number: 'phone',
                email:        'email',
                general:      'general',
            };

            Object.entries(FIELD_MAP).forEach(([backendKey, domSuffix]) => {
                const val = w.newErrors[backendKey];
                if (val) {
                    ui.show(`err-${domSuffix}`);
                    ui.text(`err-${domSuffix}`, Array.isArray(val) ? val[0] : val);
                } else {
                    ui.hide(`err-${domSuffix}`);
                }
            });
        },

        modalToast: () => {
            if (state.modal.successMsg) {
                ui.show('modal-toast');
                ui.text('modal-toast-msg', state.modal.successMsg);
            } else {
                ui.hide('modal-toast');
            }
        },
    };

})(window.Scheduler);
