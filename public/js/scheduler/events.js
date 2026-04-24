// public/js/scheduler/events.js
(function(S) {
    const { state, api, render, modal, ui, walkin } = S;

    S.events = {
        bind: () => {
            ui.$('btn-today')?.addEventListener('click', () => {
                state.selectedDate = new Date().toISOString().slice(0, 10);
                state.meta.current_page = 1;
                ui.val('input-date', state.selectedDate);
                S.events.loadSessions();
            });

            ui.$('btn-refresh')?.addEventListener('click', S.events.loadSessions);

            ui.$('input-date')?.addEventListener('change', (e) => {
                state.selectedDate = e.target.value;
                state.meta.current_page = 1;
                S.events.loadSessions();
            });

            ui.$('btn-prev')?.addEventListener('click', () => S.events.changePage(state.meta.current_page - 1));
            ui.$('btn-next')?.addEventListener('click', () => S.events.changePage(state.meta.current_page + 1));

            ui.$('btn-retry')?.addEventListener('click', S.events.loadSessions);

            ui.$('btn-close-modal')?.addEventListener('click', modal.close);
            ui.$('modal-backdrop')?.addEventListener('click', modal.close);
            ui.$('tab-btn-attendees')?.addEventListener('click', () => modal.switchTab('attendees'));
            ui.$('tab-btn-walkin')?.addEventListener('click', () => modal.switchTab('walkin'));

            ui.$('walkin-mode-existing')?.addEventListener('click', () => walkin.switchMode('existing'));
            ui.$('walkin-mode-new')?.addEventListener('click', () => walkin.switchMode('new'));

            const walkinSearch = ui.$('walkin-search');
            walkinSearch?.addEventListener('input', (e) => {
                state.walkin.search = e.target.value;
                state.walkin.dropdownOpen = true;
                render.walkinExisting();
            });
            walkinSearch?.addEventListener('focus', () => {
                state.walkin.dropdownOpen = true;
                render.walkinExisting();
            });
            
            document.addEventListener('click', (e) => {
                if (!ui.$('walkin-existing-section')?.contains(e.target)) {
                    state.walkin.dropdownOpen = false;
                    render.walkinExisting();
                }
            });

            ui.$('btn-submit-existing')?.addEventListener('click', walkin.submitExisting);

            ui.$('input-fullname')?.addEventListener('input', (e) => { state.walkin.newUser.fullname = e.target.value; });
            ui.$('input-phone')?.addEventListener('input', (e) => { state.walkin.newUser.phone_number = e.target.value; });
            ui.$('input-email')?.addEventListener('input', (e) => { state.walkin.newUser.email = e.target.value; });
            ui.$('btn-submit-new')?.addEventListener('click', walkin.submitNew);
        },

        loadSessions: async () => {
            state.loading = true;
            state.error = null;
            render.all();

            try {
                const json = await api.getSessions(state.selectedDate, state.meta.current_page, state.meta.per_page);
                if (json.success) {
                    state.sessions = json.data;
                    state.meta = { ...state.meta, ...json.meta };
                    state.resolvedDate = new Date(state.selectedDate + 'T00:00:00')
                        .toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                } else {
                    state.error = {
                        title: 'Load Failed',
                        message: json.message || 'We could not retrieve the sessions.'
                    };
                }
            } catch (err) {
                console.error('Load Sessions Error:', err);
                state.error = {
                    title: 'Connection Lost',
                    message: 'Please check your internet connection and try again.'
                };
            } finally {
                state.loading = false;
                render.all();
            }
        },

        changePage: (page) => {
            if (page < 1 || page > state.meta.last_page) return;
            state.meta.current_page = page;
            S.events.loadSessions();
        },

        toggleAttendance: async (bookingId, status) => {
            const booking = state.modal.bookings.find(b => b.id === bookingId);
            if (!booking || booking._pending) return;

            booking._pending = true;
            render.attendeesTab();

            try {
                const json = await api.postAttendance(state.modal.sessionId, bookingId, status);
                if (json.success) {
                    await modal.fetchDetails(state.modal.sessionId);
                    await S.events.loadSessions();
                }
            } catch (err) {
                console.error('Attendance Toggle Error:', err);
            } finally {
                if (booking) booking._pending = false;
                render.attendeesTab();
            }
        },
    };
})(window.Scheduler);
