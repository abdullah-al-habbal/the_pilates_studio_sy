// public/js/scheduler/walkin.js
(function (S) {
    'use strict';

    const { state, api, render, modal, ui } = S;

    function debounce(fn, ms) {
        let timer;
        return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), ms); };
    }

    S.walkin = {
        loadUsers: async (sessionId) => {
            if (state.walkin.usersLoaded && state.walkin.loadedSessionId === sessionId) return;
            state.walkin.usersLoading = true;
            state.walkin.loadedSessionId = sessionId;
            render.walkinExisting();
            try {
                const json = await api.getUsers(sessionId);
                if (json.success) {
                    state.walkin.allUsers  = json.data;
                    state.walkin.usersLoaded = true;
                }
            } catch (err) {
                console.error('[Scheduler:walkin] Load users failed:', err);
            } finally {
                state.walkin.usersLoading = false;
                render.walkinExisting();
            }
        },

        filter: () => {
            const q                  = state.walkin.search.toLowerCase().trim();
            const selectedIds        = new Set(state.walkin.selected.map(u => u.id));
            const existingAttendeeIds = new Set(
                state.modal.bookings.map(b => b.user?.id).filter(Boolean)
            );
            return state.walkin.allUsers
                .filter(u =>
                    !selectedIds.has(u.id) &&
                    !existingAttendeeIds.has(u.id) &&
                    (!q || u.label.toLowerCase().includes(q))
                )
                .slice(0, 25);
        },

        select: (user) => {
            const spots = state.modal.session?.available_spots ?? null;
            if (spots !== null && state.walkin.selected.length >= spots) {
                state.walkin.error = window.__('scheduler_ui.walkin.no_spots');
                render.walkinExisting();
                return;
            }
            if (!state.walkin.selected.find(u => u.id === user.id)) {
                state.walkin.selected.push(user);
            }
            state.walkin.search      = '';
            state.walkin.dropdownOpen = false;
            state.walkin.error        = '';
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
            render.walkinNew();
        },

        validateField: debounce(async (field, value) => {
            if (!value || value.length < 3) {
                delete state.walkin.newErrors[field];
                render.walkinNew();
                return;
            }
            try {
                const json = await api.validateField(field, value);
                if (json.success && !json.data.available) {
                    const label = field === 'phone_number' ? window.__('scheduler_ui.walkin.phone_label') : window.__('scheduler_ui.walkin.email_label');
                    state.walkin.newErrors[field] = [window.__('scheduler_ui.walkin.already_registered').replace(':label', label)];
                } else {
                    delete state.walkin.newErrors[field];
                }
                render.walkinNew();
            } catch (_) {
            }
        }, 500),

        submitExisting: async () => {
            if (!state.walkin.selected.length || state.walkin.submitting) return;
            state.walkin.submitting = true;
            state.walkin.error      = '';
            render.walkinExisting();
            try {
                const userIds = state.walkin.selected.map(u => u.id);
                const json    = await api.postExistingWalkIn(state.modal.sessionId, userIds);
                if (json.success) {
                    state.walkin.selected = [];
                    modal.showToast(json.message || window.__('scheduler_ui.walkin.walkins_added'));
                    S.toaster.success(json.message || window.__('scheduler_ui.walkin.walkins_added'));
                    modal.switchTab('attendees');
                    await modal.fetchDetails(state.modal.sessionId);
                    await S.events.loadSessions();
                } else {
                    state.walkin.error = json.message || window.__('scheduler_ui.walkin.walkins_failed');
                    S.toaster.error(state.walkin.error);
                }
            } catch (err) {
                state.walkin.error = err.message || window.__('scheduler_ui.walkin.connection_failed');
                S.toaster.error(state.walkin.error);
            } finally {
                state.walkin.submitting = false;
                render.walkinExisting();
            }
        },

        submitNew: async () => {
            const newUser = state.walkin.newUser;
            if (!newUser.fullname || !newUser.phone_number || state.walkin.submitting) return;

            const hasValidationErrors = Object.keys(state.walkin.newErrors).length > 0;
            if (hasValidationErrors) {
                S.toaster.warning(window.__('scheduler_ui.walkin.validation_errors'));
                return;
            }

            state.walkin.submitting = true;
            state.walkin.newErrors  = {};
            render.walkinNew();
            try {
                const payload = { ...newUser };
                if (!payload.password) payload.password = 'pilates';

                const json = await api.postNewWalkIn(state.modal.sessionId, payload);

                if (json.success) {
                    state.walkin.newUser = { fullname: '', phone_number: '', email: '', password: '' };
                    ui.val('input-fullname', '');
                    ui.val('input-phone',    '');
                    ui.val('input-email',    '');

                    modal.showToast(json.message || window.__('scheduler_ui.walkin.member_created'));
                    S.toaster.success(json.message || window.__('scheduler_ui.walkin.member_created_added'));
                    modal.switchTab('attendees');
                    await modal.fetchDetails(state.modal.sessionId);
                    await S.events.loadSessions();
                } else {
                    state.walkin.newErrors.general = json.message || window.__('scheduler_ui.walkin.member_create_failed');
                    S.toaster.error(state.walkin.newErrors.general);
                }
            } catch (err) {
                if (err.status === 422) {
                    state.walkin.newErrors = err.errors || {};
                    S.toaster.warning(window.__('scheduler_ui.walkin.check_form'));
                } else {
                    state.walkin.newErrors.general = err.message || window.__('scheduler_ui.walkin.something_wrong');
                    S.toaster.error(state.walkin.newErrors.general);
                }
            } finally {
                state.walkin.submitting = false;
                render.walkinNew();
            }
        },
    };

})(window.Scheduler);
