// public/js/scheduler/render.js
(function(S) {
    const { ui, templates, state } = S;

    S.render = {
        all: () => {
            S.render.header();
            S.render.states();
            S.render.sessionList();
            S.render.pagination();
            S.render.modal();
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
                ui.text('error-title', state.error.title);
                ui.text('error-message', state.error.message);
            } else if (state.sessions.length === 0) {
                ui.show('empty-state');
            } else {
                ui.show('session-list');
            }
        },

        sessionList: () => {
            const html = state.sessions.map(templates.sessionCard).join('');
            ui.html('session-list', html);
        },

        pagination: () => {
            const { current_page, last_page } = state.meta;
            if (last_page <= 1 && state.sessions.length === 0) {
                ui.hide('pagination');
                return;
            }
            ui.show('pagination');
            ui.text('page-current', current_page);
            ui.text('page-last', last_page);

            const prevBtn = ui.$('btn-prev');
            const nextBtn = ui.$('btn-next');
            if (prevBtn) prevBtn.disabled = current_page <= 1;
            if (nextBtn) nextBtn.disabled = current_page >= last_page;
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
                    ui.text('modal-title', s.title);
                    ui.text('modal-date', s.date);
                    ui.text('modal-time', `${s.start_time} - ${s.end_time}`);
                    ui.text('modal-instructor', s.instructor);
                }
            }
        },

        modalTabs: () => {
            const tab = state.modal.tab;
            const attendeesBtn = ui.$('tab-btn-attendees');
            const walkinBtn = ui.$('tab-btn-walkin');

            const activeClass = 'bg-primary-600 text-white shadow-lg shadow-primary-500/30';
            const inactiveClass = 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800';

            if (tab === 'attendees') {
                attendeesBtn?.setAttribute('class', `flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all ${activeClass}`);
                walkinBtn?.setAttribute('class', `flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all ${inactiveClass}`);
                ui.show('tab-attendees');
                ui.hide('tab-walkin');
            } else {
                attendeesBtn?.setAttribute('class', `flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all ${inactiveClass}`);
                walkinBtn?.setAttribute('class', `flex-1 py-3 px-4 rounded-xl text-sm font-bold transition-all ${activeClass}`);
                ui.hide('tab-attendees');
                ui.show('tab-walkin');
            }
            
            ui.text('attendees-count', state.modal.bookings.length);
        },

        attendeesTab: () => {
            const s = state.modal.session;
            if (!s) return;

            if (s.capacity > 0) {
                ui.show('capacity-bar-wrap');
                ui.text('capacity-reserved', s.reserved);
                ui.text('capacity-total', s.capacity);
                const fillEl = ui.$('capacity-bar-fill');
                if (fillEl) fillEl.style.width = `${s.fill_pct}%`;
            } else {
                ui.hide('capacity-bar-wrap');
            }

            ui.hide('attendees-skeleton');
            ui.hide('attendees-empty');
            ui.hide('attendees-list');

            if (state.modal.loading && state.modal.bookings.length === 0) {
                ui.show('attendees-skeleton');
            } else if (state.modal.bookings.length === 0) {
                ui.show('attendees-empty');
            } else {
                ui.show('attendees-list');
                const html = state.modal.bookings.map(templates.bookingCard).join('');
                ui.html('attendees-list', html);
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
            const w = state.walkin;
            const existingBtn = ui.$('walkin-mode-existing');
            const newBtn = ui.$('walkin-mode-new');
            const activeClass = 'bg-primary-600 text-white shadow-md';
            const inactiveClass = 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800';

            if (w.mode === 'existing') {
                existingBtn?.setAttribute('class', `flex-1 py-2 px-4 rounded-lg text-xs font-bold transition-all ${activeClass}`);
                newBtn?.setAttribute('class', `flex-1 py-2 px-4 rounded-lg text-xs font-bold transition-all ${inactiveClass}`);
                ui.show('walkin-existing-section');
                ui.hide('walkin-new-section');
            } else {
                existingBtn?.setAttribute('class', `flex-1 py-2 px-4 rounded-lg text-xs font-bold transition-all ${inactiveClass}`);
                newBtn?.setAttribute('class', `flex-1 py-2 px-4 rounded-lg text-xs font-bold transition-all ${activeClass}`);
                ui.hide('walkin-existing-section');
                ui.show('walkin-new-section');
            }

            if (w.dropdownOpen && w.search.length >= 2) {
                ui.show('walkin-dropdown');
                const users = S.walkin.filter();
                if (users.length === 0) {
                    ui.html('walkin-dropdown', '<div class="px-4 py-3 text-sm text-gray-500 italic">No members found...</div>');
                } else {
                    const html = users.map(u => `
                        <button onclick="Scheduler.walkin.select(${JSON.stringify(u).replace(/"/g, '&quot;')})" class="w-full text-left px-4 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors flex items-center justify-between group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] font-black text-gray-500 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600">${u.initial}</div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary-600">${u.label}</p>
                                    <p class="text-[10px] text-gray-400 font-medium">${u.phone}</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-primary-500 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    `).join('');
                    ui.html('walkin-dropdown', html);
                }
            } else {
                ui.hide('walkin-dropdown');
            }

            const tagsHtml = w.selected.map(u => `
                <div class="inline-flex items-center gap-2 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-3 py-1.5 rounded-xl border border-primary-100 dark:border-primary-800/50 animate-in fade-in zoom-in duration-300">
                    <span class="text-xs font-bold">${u.label}</span>
                    <button onclick="Scheduler.walkin.remove(${u.id})" class="hover:text-primary-900 dark:hover:text-primary-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            `).join('');
            ui.html('walkin-selected-tags', tagsHtml);

            if (w.error) {
                ui.show('walkin-existing-error');
                ui.text('walkin-existing-error-msg', w.error);
            } else {
                ui.hide('walkin-existing-error');
            }

            const submitBtn = ui.$('btn-submit-existing');
            if (submitBtn) {
                submitBtn.disabled = w.selected.length === 0 || w.submitting;
                ui.text('btn-submit-existing-text', w.submitting ? 'Adding...' : `Add ${w.selected.length} Member(s)`);
            }
        },

        walkinNew: () => {
            const w = state.walkin;
            const submitBtn = ui.$('btn-submit-new');
            if (submitBtn) submitBtn.disabled = w.submitting;
            ui.text('btn-submit-new-text', w.submitting ? 'Creating...' : 'Create & Add to Session');

            ['fullname', 'phone_number', 'email', 'general'].forEach(field => {
                const errEl = ui.$(`err-${field}`);
                if (w.newErrors[field]) {
                    ui.show(`err-${field}`);
                    ui.text(`err-${field}`, Array.isArray(w.newErrors[field]) ? w.newErrors[field][0] : w.newErrors[field]);
                } else {
                    ui.hide(`err-${field}`);
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
        }
    };
})(window.Scheduler);
