// filePath: resources\js\scheduler.js
document.addEventListener('alpine:init', () => {
    Alpine.data('schedulerPage', () => ({
        selectedDate: new Date().toISOString().slice(0, 10),
        resolvedDate: '',
        sessions: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        loading: false,
        error: null,

        modal: {
            show: false,
            loading: false,
            tab: 'attendees',
            session: null,
            bookings: [],
            successMsg: '',
            sessionId: null,
        },

        walkin: {
            mode: 'existing',
            submitting: false,
            error: '',
            allUsers: [],
            usersLoaded: false,
            usersLoading: false,
            search: '',
            selected: [],
            dropdownOpen: false,
            newUser: { fullname: '', phone_number: '', email: '', password: '' },
            newErrors: {},
        },

        init() {
            console.log('Scheduler Initializing via Alpine.data()...');
            this.loadSessions();
        },

        goToToday() {
            this.selectedDate = new Date().toISOString().slice(0, 10);
            this.meta.current_page = 1;
            this.loadSessions();
        },

        changePage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.meta.current_page = page;
            this.loadSessions();
        },

        async loadSessions() {
            console.log('loadSessions() called');
            this.loading = true;
            this.error = null;
            try {
                const params = new URLSearchParams({
                    date: this.selectedDate,
                    page: this.meta.current_page,
                    per_page: this.meta.per_page,
                });
                console.log('Fetching sessions with params:', params.toString());
                const res = await fetch(`/admin/scheduler/sessions?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                });

                console.log('Fetch response status:', res.status);
                const json = await res.json();
                console.log('Fetch response JSON:', json);

                if (json.success) {
                    this.sessions = json.data;
                    this.meta = { ...this.meta, ...json.meta };
                    this.resolvedDate = new Date(this.selectedDate + 'T00:00:00')
                        .toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                } else {
                    console.warn('JSON response success=false:', json);
                    this.error = {
                        title: 'Load Failed',
                        message: json.message || 'We could not retrieve the sessions.'
                    };
                }
            } catch (err) {
                console.error('Scheduler Fetch Error:', err);
                this.error = {
                    title: 'Connection Lost',
                    message: 'Please check your internet connection and try again.'
                };
            } finally {
                this.loading = false;
            }
        },

        openModal(sessionId) {
            this.modal = {
                show: true,
                loading: false,
                tab: 'attendees',
                session: null,
                bookings: [],
                successMsg: '',
                sessionId,
            };
            this.walkin = {
                mode: 'existing',
                submitting: false,
                error: '',
                allUsers: this.walkin.allUsers,
                usersLoaded: this.walkin.usersLoaded,
                usersLoading: false,
                search: '',
                selected: [],
                dropdownOpen: false,
                newUser: { fullname: '', phone_number: '', email: '', password: '' },
                newErrors: {},
            };
            this.fetchSessionDetails(sessionId);
        },

        closeModal() {
            this.modal.show = false;
        },

        async fetchSessionDetails(sessionId) {
            this.modal.loading = true;
            try {
                const res = await fetch(`/admin/scheduler/sessions/${sessionId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                });
                const json = await res.json();
                if (json.success) {
                    this.modal.session = json.data;
                    this.modal.bookings = json.data.bookings.map(b => ({ ...b, _pending: false }));
                }
            } catch (err) {
                console.error('Session Details Error:', err);
            } finally {
                this.modal.loading = false;
            }
        },

        async loadUsers() {
            if (this.walkin.usersLoaded) return;
            this.walkin.usersLoading = true;
            try {
                const res = await fetch('/admin/scheduler/users', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                });
                const json = await res.json();
                if (json.success) {
                    this.walkin.allUsers = json.data;
                    this.walkin.usersLoaded = true;
                }
            } finally {
                this.walkin.usersLoading = false;
            }
        },

        filteredUsers() {
            const q = this.walkin.search.toLowerCase().trim();
            const selected = new Set(this.walkin.selected.map(u => u.id));
            const existingAttendeeIds = new Set(this.modal.bookings.map(b => b.user?.id).filter(Boolean));

            return this.walkin.allUsers
                .filter(u => !selected.has(u.id) && !existingAttendeeIds.has(u.id) && (!q || u.label.toLowerCase().includes(q)))
                .slice(0, 25);
        },

        selectUser(user) {
            const spots = this.modal.session?.available_spots ?? null;
            if (spots !== null && this.walkin.selected.length >= spots) {
                this.walkin.error = 'No more spots available in this session.';
                return;
            }
            if (!this.walkin.selected.find(u => u.id === user.id)) {
                this.walkin.selected.push(user);
            }
            this.walkin.search = '';
            this.walkin.dropdownOpen = false;
            this.walkin.error = '';
        },

        removeSelectedUser(id) {
            this.walkin.selected = this.walkin.selected.filter(u => u.id !== id);
        },

        async toggleAttendance(booking, status) {
            if (booking._pending) return;
            booking._pending = true;
            try {
                const res = await fetch(
                    `/admin/scheduler/sessions/${this.modal.sessionId}/attendance/${booking.id}`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-XSRF-TOKEN': this._csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ status }),
                    }
                );

                const json = await res.json();
                if (json.success) {
                    await this.fetchSessionDetails(this.modal.sessionId);
                    this.loadSessions();
                }
            } catch (err) {
                console.error('Attendance Toggle Error:', err);
            } finally {
                booking._pending = false;
            }
        },

        async submitExistingWalkIn() {
            if (!this.walkin.selected.length || this.walkin.submitting) return;
            this.walkin.submitting = true;
            this.walkin.error = '';
            try {
                const res = await fetch(`/admin/scheduler/sessions/${this.modal.sessionId}/walkin/existing`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': this._csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ user_ids: this.walkin.selected.map(u => u.id) }),
                });
                const json = await res.json();
                if (json.success) {
                    this.walkin.selected = [];
                    this.modal.successMsg = json.message || 'Walk-ins added successfully.';
                    this.modal.tab = 'attendees';
                    await this.fetchSessionDetails(this.modal.sessionId);
                    this.loadSessions();
                    setTimeout(() => this.modal.successMsg = '', 4000);
                } else {
                    this.walkin.error = json.message || 'Failed to add walk-ins.';
                }
            } catch (err) {
                this.walkin.error = 'Connection failed. Please try again.';
            } finally {
                this.walkin.submitting = false;
            }
        },

        async submitNewWalkIn() {
            if (!this.walkin.newUser.fullname || !this.walkin.newUser.phone_number || this.walkin.submitting) return;
            this.walkin.submitting = true;
            this.walkin.newErrors = {};
            try {
                const payload = { ...this.walkin.newUser };
                if (!payload.password) payload.password = 'pilates';
                const res = await fetch(`/admin/scheduler/sessions/${this.modal.sessionId}/walkin/new`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': this._csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (res.ok && json.success) {
                    this.walkin.newUser = { fullname: '', phone_number: '', email: '', password: '' };
                    this.modal.successMsg = json.message || 'New member created successfully.';
                    this.modal.tab = 'attendees';
                    await this.fetchSessionDetails(this.modal.sessionId);
                    this.loadSessions();
                    setTimeout(() => this.modal.successMsg = '', 4000);
                } else if (res.status === 422) {
                    this.walkin.newErrors = json.errors || {};
                } else {
                    this.walkin.newErrors.general = json.message || 'Something went wrong.';
                }
            } catch (err) {
                this.walkin.newErrors.general = 'Network error. Please check your connection.';
            } finally {
                this.walkin.submitting = false;
            }
        },

        _csrfToken() {
            const c = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='));
            return c ? decodeURIComponent(c.split('=')[1]) : '';
        },
    }));
});
