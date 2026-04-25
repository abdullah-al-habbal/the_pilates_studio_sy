// public/js/scheduler/api.js
(function (S) {
    'use strict';

    const BASE = '/admin/scheduler';

    const ROUTES = Object.freeze({
        sessions:       `${BASE}/sessions`,
        session:        (id)                   => `${BASE}/sessions/${id}`,
        users:          (sessionId) => `${BASE}/users?session_id=${sessionId}`,
        attendance:     (sessionId, bookingId) => `${BASE}/sessions/${sessionId}/attendance/${bookingId}`,
        walkInExisting: (sessionId)            => `${BASE}/sessions/${sessionId}/walkin/existing`,
        walkInNew:      (sessionId)            => `${BASE}/sessions/${sessionId}/walkin/new`,
        validateField:  `${BASE}/walkin/validate`,
    });

    const csrf = () => {
        const c = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='));
        if (c) return decodeURIComponent(c.split('=')[1]);
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    };

    const request = async (url, options = {}) => {
        const headers = {
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers,
        };

        if (options.method && options.method !== 'GET') {
            headers['X-XSRF-TOKEN'] = csrf();
            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }
        }

        const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });

        if (response.status === 422) {
            const err = await response.json();
            throw { status: 422, errors: err.errors, message: err.message };
        }

        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw { status: response.status, message: err.message || 'Network response was not ok' };
        }

        return response.json();
    };

    S.api = {

        getSessions: (date, page, perPage) => {
            const params = new URLSearchParams({ date, page, per_page: perPage });
            return request(`${ROUTES.sessions}?${params}`);
        },

        getSession: (sessionId) =>
            request(ROUTES.session(sessionId)),

        getUsers: (sessionId) =>
            request(ROUTES.users(sessionId)),

        postAttendance: (sessionId, bookingId, status) =>
            request(ROUTES.attendance(sessionId, bookingId), {
                method: 'POST',
                body:   JSON.stringify({ status }),
            }),

        postExistingWalkIn: (sessionId, userIds) =>
            request(ROUTES.walkInExisting(sessionId), {
                method: 'POST',
                body:   JSON.stringify({ user_ids: userIds }),
            }),

        postNewWalkIn: (sessionId, userData) =>
            request(ROUTES.walkInNew(sessionId), {
                method: 'POST',
                body:   JSON.stringify(userData),
            }),

        validateField: (field, value) => {
            const params = new URLSearchParams({ field, value });
            return request(`${ROUTES.validateField}?${params}`);
        },
    };

})(window.Scheduler);
