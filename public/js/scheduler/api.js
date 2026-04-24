// public/js/scheduler/api.js
(function(S) {
    const csrf = () => {
        const c = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='));
        if (c) return decodeURIComponent(c.split('=')[1]);
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    };

    const request = async (url, options = {}) => {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers
        };

        if (options.method && options.method !== 'GET') {
            headers['X-XSRF-TOKEN'] = csrf();
            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }
        }

        const response = await fetch(url, {
            ...options,
            headers,
            credentials: 'same-origin'
        });

        if (response.status === 422) {
            const error = await response.json();
            throw { status: 422, errors: error.errors, message: error.message };
        }

        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw { status: response.status, message: error.message || 'Network response was not ok' };
        }

        return response.json();
    };

    S.api = {
        getSessions: (date, page, perPage) => {
            const params = new URLSearchParams({ date, page, per_page: perPage });
            return request(`/admin/scheduler/sessions?${params}`);
        },
        getSession: (sessionId) => {
            return request(`/admin/scheduler/sessions/${sessionId}`);
        },
        getUsers: () => {
            return request('/admin/scheduler/users');
        },
        postAttendance: (sessionId, bookingId, status) => {
            return request(`/admin/scheduler/sessions/${sessionId}/attendance/${bookingId}`, {
                method: 'POST',
                body: JSON.stringify({ status })
            });
        },
        postExistingWalkIn: (sessionId, userIds) => {
            return request(`/admin/scheduler/sessions/${sessionId}/walkin/existing`, {
                method: 'POST',
                body: JSON.stringify({ user_ids: userIds })
            });
        },
        postNewWalkIn: (sessionId, userData) => {
            return request(`/admin/scheduler/sessions/${sessionId}/walkin/new`, {
                method: 'POST',
                body: JSON.stringify(userData)
            });
        }
    };
})(window.Scheduler);
