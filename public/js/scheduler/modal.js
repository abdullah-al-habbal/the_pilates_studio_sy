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

            state.capacity = { show: false, saving: false, error: '' };

            render.modal();
            await S.modal.fetchDetails(sessionId);
        },

        close: () => {
            state.modal.show = false;
            state.capacity = { show: false, saving: false, error: '' };
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

        openCapacityModal: () => {
            const s = state.modal.session;
            if (!s) return;

            state.capacity = {
                show: true,
                saving: false,
                error: '',
                currentCapacity: s.capacity,
                reserved: s.reserved,
                minAllowed: s.reserved,
                newCapacity: s.capacity,
                reason: '',
            };
            render.capacityModal();
        },

        closeCapacityModal: () => {
            state.capacity.show = false;
            render.capacityModal();
        },

        saveCapacity: async () => {
            const c = state.capacity;
            const val = parseInt(c.newCapacity, 10);

            if (!val || val < 1) {
                c.error = 'Capacity must be at least 1.';
                render.capacityModal();
                return;
            }
            if (val < c.minAllowed) {
                c.error = `Capacity cannot be less than ${c.minAllowed} already reserved booking(s).`;
                render.capacityModal();
                return;
            }
            if (!c.reason.trim()) {
                c.error = 'Please provide a reason for this change.';
                render.capacityModal();
                return;
            }

            c.saving = true;
            c.error = '';
            render.capacityModal();

            try {
                const json = await api.postCapacity(state.modal.sessionId, val, c.reason.trim());
                if (json.success) {
                    state.capacity.show = false;
                    S.modal.showToast('Capacity updated successfully.');
                    await S.modal.fetchDetails(state.modal.sessionId);
                    await S.events.loadSessions();
                } else {
                    c.error = json.message || 'Failed to update capacity.';
                }
            } catch (err) {
                console.error('Capacity Update Error:', err);
                if (err.status === 422 && err.errors) {
                    c.error = Object.values(err.errors).flat().join(' ');
                } else {
                    c.error = err.message || 'Failed to update capacity. Please try again.';
                }
            } finally {
                c.saving = false;
                render.capacityModal();
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
