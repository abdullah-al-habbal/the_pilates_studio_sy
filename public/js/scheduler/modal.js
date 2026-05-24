// public/js/scheduler/modal.js
(function(S) {
    const { state, api, render } = S;

    S.modal = {
        open: async (sessionId) => {
            state.modal = {
                show: true,
                loading: false,
                tab: 'attendees',
                session: null,
                bookings: [],
                successMsg: '',
                error: '',
                sessionId: sessionId
            };
            
            state.walkin = {
                ...state.walkin,
                mode: 'existing',
                submitting: false,
                error: '',
                search: '',
                selected: [],
                dropdownOpen: false,
                newUser: { fullname: '', phone_number: '', email: '', password: '' },
                newErrors: {}
            };

            render.modal();
            await S.modal.fetchDetails(sessionId);
        },

        close: () => {
            state.modal.show = false;
            render.modal();
        },

        switchTab: (tab) => {
            state.modal.tab = tab;
            render.modalTabs();
            if (tab === 'walkin') {
                S.walkin.loadUsers(state.modal.sessionId);
            }
        },

        fetchDetails: async (sessionId) => {
            state.modal.loading = true;
            state.modal.error = '';
            render.modalHeader();
            render.attendeesTab();
            
            try {
                const json = await api.getSession(sessionId);
                if (json.success) {
                    state.modal.session = json.data;
                    state.modal.bookings = json.data.bookings.map(b => ({ ...b, _pending: false }));
                }
            } catch (err) {
                console.error('Session Details Error:', err);
                state.modal.error = err.message || 'Unable to load session attendees.';
                state.modal.bookings = [];
            } finally {
                state.modal.loading = false;
                render.modalHeader();
                render.attendeesTab();
                render.walkinTab();
            }
        },

        showToast: (msg, duration = 4000) => {
            state.modal.successMsg = msg;
            render.modalToast();
            setTimeout(() => {
                state.modal.successMsg = '';
                render.modalToast();
            }, duration);
        }
    };
})(window.Scheduler);
