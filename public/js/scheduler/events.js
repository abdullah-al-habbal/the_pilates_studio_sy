// public/js/scheduler/events.js
(function (S) {
    'use strict';

    const { state, api, render, modal, ui, walkin } = S;

    S.events = {
        bind: () => {
            ui.$('btn-today')?.addEventListener('click', () => {
                state.selectedDate      = new Date().toISOString().slice(0, 10);
                state.meta.current_page = 1;
                ui.val('input-date', state.selectedDate);
                S.events.loadSessions();
            });

            ui.$('btn-refresh')?.addEventListener('click', S.events.loadSessions);

            ui.$('input-date')?.addEventListener('change', (e) => {
                state.selectedDate      = e.target.value;
                state.meta.current_page = 1;
                S.events.loadSessions();
            });

            ui.$('input-instructor')?.addEventListener('change', (e) => {
                state.selectedInstructorId = e.target.value || null;
                state.meta.current_page    = 1;
                S.events.loadSessions();
            });

            ui.$('btn-prev')?.addEventListener('click',
                () => S.events.changePage(state.meta.current_page - 1));
            ui.$('btn-next')?.addEventListener('click',
                () => S.events.changePage(state.meta.current_page + 1));

            ui.$('btn-retry')?.addEventListener('click', S.events.loadSessions);

            ui.$('btn-close-modal')?.addEventListener('click', modal.close);
            ui.$('modal-backdrop')?.addEventListener('click', modal.close);

            ui.$('tab-btn-attendees')?.addEventListener('click', () => modal.switchTab('attendees'));
            ui.$('tab-btn-walkin')?.addEventListener('click',    () => modal.switchTab('walkin'));

            ui.$('walkin-mode-existing')?.addEventListener('click', () => walkin.switchMode('existing'));
            ui.$('walkin-mode-new')?.addEventListener('click',      () => walkin.switchMode('new'));

            const walkinSearch = ui.$('walkin-search');
            walkinSearch?.addEventListener('input', (e) => {
                state.walkin.search       = e.target.value;
                state.walkin.dropdownOpen = true;
                render.walkinExisting();
            });
            walkinSearch?.addEventListener('focus', async () => {
                state.walkin.dropdownOpen = true;
                if (!state.walkin.usersLoaded && state.modal.sessionId) {
                    await S.walkin.loadUsers(state.modal.sessionId);
                }
                render.walkinExisting();
            });

            document.addEventListener('click', (e) => {
                if (!ui.$('walkin-existing-section')?.contains(e.target)) {
                    state.walkin.dropdownOpen = false;
                    render.walkinExisting();
                }
            });

            ui.$('btn-submit-existing')?.addEventListener('click', walkin.submitExisting);

            ui.$('input-fullname')?.addEventListener('input', (e) => {
                state.walkin.newUser.fullname = e.target.value;
            });

            ui.$('input-phone')?.addEventListener('input', (e) => {
                state.walkin.newUser.phone_number = e.target.value;
                walkin.validateField('phone_number', e.target.value);
            });

            ui.$('input-email')?.addEventListener('input', (e) => {
                state.walkin.newUser.email = e.target.value;
                if (e.target.value.trim()) {
                    walkin.validateField('email', e.target.value.trim());
                } else {
                    delete state.walkin.newErrors.email;
                    render.walkinNew();
                }
            });

            ui.$('btn-submit-new')?.addEventListener('click', walkin.submitNew);
        },

        loadSessions: async () => {
            state.loading = true;
            state.error   = null;
            render.all();
            try {
                const json = await api.getSessions(
                    state.selectedDate,
                    state.meta.current_page,
                    state.meta.per_page,
                    state.selectedInstructorId
                );
                if (json.success) {
                    state.sessions     = json.data;
                    state.meta         = { ...state.meta, ...json.meta };
                    state.resolvedDate = new Date(state.selectedDate + 'T00:00:00')
                        .toLocaleDateString(undefined, {
                            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                        });
                } else {
                    state.error = {
                        title:   'Load Failed',
                        message: json.message || 'Could not retrieve sessions.',
                    };
                }
            } catch (err) {
                console.error('[Scheduler:events] loadSessions error:', err);
                state.error = {
                    title:   'Connection Lost',
                    message: 'Please check your internet connection and try again.',
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

        initFlatpickr: () => {
            const input = ui.$('input-date');
            if (!input || typeof flatpickr === 'undefined') return;

            let enabledDates = [];

            const fetchDates = async (year, month) => {
                try {
                    const res = await api.getDaysInMonth(year, month);
                    if (res.success) {
                        enabledDates = res.data;
                        fp.set('enable', enabledDates);
                        fp.redraw();
                    }
                } catch (e) {
                    console.error('[Scheduler:events] Failed to fetch session dates', e);
                }
            };

            const fp = flatpickr(input, {
                dateFormat: 'Y-m-d',
                defaultDate: state.selectedDate,
                disableMobile: true,
                onMonthChange: (selectedDates, dateStr, instance) => {
                    fetchDates(instance.currentYear, instance.currentMonth + 1);
                },
                onYearChange: (selectedDates, dateStr, instance) => {
                    fetchDates(instance.currentYear, instance.currentMonth + 1);
                },
                onReady: (selectedDates, dateStr, instance) => {
                    fetchDates(instance.currentYear, instance.currentMonth + 1);

                    const style = document.createElement('style');
                    style.innerHTML = `
                        .flatpickr-day.has-session {
                            background: #fbbf24 !important;
                            color: #fff !important;
                            border-color: #f59e0b !important;
                        }
                        .flatpickr-day.has-session.selected {
                            background: #d97706 !important;
                            border-color: #b45309 !important;
                        }
                    `;
                    document.head.appendChild(style);
                },
                onDayCreate: (dObj, dStr, fp, dayElem) => {
                    const dateStr = dayElem.dateObj.toISOString().slice(0, 10);
                    if (enabledDates.includes(dateStr)) {
                        dayElem.classList.add('has-session');
                    }
                },
            });
        },

        loadInstructors: async () => {
            try {
                const json = await api.getInstructors();
                if (json.success) {
                    state.instructors = json.data;
                    render.instructorDropdown();
                }
            } catch (err) {
                console.error('[Scheduler:events] loadInstructors error:', err);
            }
        },

        toggleAttendance: async (bookingId, status) => {
            const booking = state.modal.bookings.find(b => b.id === bookingId);
            if (!booking || booking._pending) return;

            booking._pending = true;
            render.attendeesTab();

            try {
                const json = await api.postAttendance(state.modal.sessionId, bookingId, status);

                if (json.success) {
                    const label = status === 'attended' ? 'marked as attended ✓' : 'marked as missed';
                    S.toaster.success(`Attendance ${label} successfully.`);
                    await modal.fetchDetails(state.modal.sessionId);
                    await S.events.loadSessions();
                } else {
                    S.toaster.error(json.message || 'Failed to update attendance.');
                }
            } catch (err) {
                console.error('[Scheduler:events] toggleAttendance error:', err);
                S.toaster.error('Failed to update attendance. Please try again.');
            } finally {
                render.attendeesTab();
            }
        },
    };

})(window.Scheduler);
