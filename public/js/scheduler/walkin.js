// public/js/scheduler/walkin.js
(function(S) {
    const { state, api, render, modal, ui } = S;

    S.walkin = {
        loadUsers: async () => {
            if (state.walkin.usersLoaded || state.walkin.usersLoading) return;
            state.walkin.usersLoading = true;
            try {
                const json = await api.getUsers();
                if (json.success) {
                    state.walkin.allUsers = json.data;
                    state.walkin.usersLoaded = true;
                }
            } catch (err) {
                console.error('Load Users Error:', err);
            } finally {
                state.walkin.usersLoading = false;
            }
        },

        filter: () => {
            const q = state.walkin.search.toLowerCase().trim();
            const selected = new Set(state.walkin.selected.map(u => u.id));
            const existingAttendeeIds = new Set(state.modal.bookings.map(b => b.user?.id).filter(Boolean));

            return state.walkin.allUsers
                .filter(u => !selected.has(u.id) && !existingAttendeeIds.has(u.id) && (!q || u.label.toLowerCase().includes(q)))
                .slice(0, 25);
        },

        select: (user) => {
            const spots = state.modal.session?.available_spots ?? null;
            if (spots !== null && state.walkin.selected.length >= spots) {
                state.walkin.error = 'No more spots available in this session.';
                render.walkinExisting();
                return;
            }
            if (!state.walkin.selected.find(u => u.id === user.id)) {
                state.walkin.selected.push(user);
            }
            state.walkin.search = '';
            state.walkin.dropdownOpen = false;
            state.walkin.error = '';
            ui.val('walkin-search', '');
            render.walkinExisting();
        },

        remove: (userId) => {
            state.walkin.selected = state.walkin.selected.filter(u => u.id !== userId);
            render.walkinExisting();
        },

        switchMode: (mode) => {
            state.walkin.mode = mode;
            render.walkinExisting();
        },

        submitExisting: async () => {
            if (!state.walkin.selected.length || state.walkin.submitting) return;
            state.walkin.submitting = true;
            state.walkin.error = '';
            render.walkinExisting();

            try {
                const userIds = state.walkin.selected.map(u => u.id);
                const json = await api.postExistingWalkIn(state.modal.sessionId, userIds);
                if (json.success) {
                    state.walkin.selected = [];
                    modal.showToast(json.message || 'Walk-ins added successfully.');
                    modal.switchTab('attendees');
                    await modal.fetchDetails(state.modal.sessionId);
                    await S.events.loadSessions();
                } else {
                    state.walkin.error = json.message || 'Failed to add walk-ins.';
                }
            } catch (err) {
                state.walkin.error = err.message || 'Connection failed. Please try again.';
            } finally {
                state.walkin.submitting = false;
                render.walkinExisting();
            }
        },

        submitNew: async () => {
            const newUser = state.walkin.newUser;
            if (!newUser.fullname || !newUser.phone_number || state.walkin.submitting) return;
            
            state.walkin.submitting = true;
            state.walkin.newErrors = {};
            render.walkinNew();

            try {
                const payload = { ...newUser };
                if (!payload.password) payload.password = 'pilates';
                const json = await api.postNewWalkIn(state.modal.sessionId, payload);
                
                if (json.success) {
                    state.walkin.newUser = { fullname: '', phone_number: '', email: '', password: '' };
                    // Clear inputs
                    ui.val('input-fullname', '');
                    ui.val('input-phone', '');
                    ui.val('input-email', '');
                    
                    modal.showToast(json.message || 'New member created successfully.');
                    modal.switchTab('attendees');
                    await modal.fetchDetails(state.modal.sessionId);
                    await S.events.loadSessions();
                } else {
                    state.walkin.newErrors.general = json.message || 'Failed to create member.';
                }
            } catch (err) {
                if (err.status === 422) {
                    state.walkin.newErrors = err.errors || {};
                } else {
                    state.walkin.newErrors.general = err.message || 'Something went wrong.';
                }
            } finally {
                state.walkin.submitting = false;
                render.walkinNew();
            }
        }
    };
})(window.Scheduler);
