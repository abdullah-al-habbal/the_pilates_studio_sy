const OperationsNotifications = (() => {
    // ─── State ────────────────────────────────────────────────────────────────
    const state = {
        selectedUsers: new Map(), // id → { id, fullname, phone_number }
        searchTimeout: null,
    };

    // ─── Init ─────────────────────────────────────────────────────────────────
    function init() {
        _bindCharCounters();
        _bindTargetToggle();
        _bindUserSearch();
    }

    // ─── Character counters ───────────────────────────────────────────────────
    function _bindCharCounters() {
        document.getElementById('notif-title')?.addEventListener('input', (e) => {
            document.getElementById('notif-title-count').textContent = e.target.value.length;
        });
        document.getElementById('notif-body')?.addEventListener('input', (e) => {
            document.getElementById('notif-body-count').textContent = e.target.value.length;
        });
    }

    // ─── Target toggle ────────────────────────────────────────────────────────
    function _bindTargetToggle() {
        document.querySelectorAll('input[name="notif-target"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                const picker = document.getElementById('notif-user-picker');
                picker?.classList.toggle('hidden', radio.value !== 'specific');

                if (radio.value !== 'specific') {
                    state.selectedUsers.clear();
                    _renderSelectedUsers();
                }
            });
        });
    }

    // ─── User search ──────────────────────────────────────────────────────────
    function _bindUserSearch() {
        const input = document.getElementById('notif-user-search');
        if (!input) return;

        input.addEventListener('input', (e) => {
            clearTimeout(state.searchTimeout);
            const q = e.target.value.trim();

            if (q.length < 2) {
                document.getElementById('notif-user-results')?.classList.add('hidden');
                return;
            }

            state.searchTimeout = setTimeout(() => _searchUsers(q), 300);
        });
    }

    async function _searchUsers(query) {
        const resultsEl = document.getElementById('notif-user-results');
        if (!resultsEl) return;

        resultsEl.classList.remove('hidden');
        resultsEl.innerHTML = '<p class="text-xs text-slate-400 px-3 py-2">Searching…</p>';

        try {
            const result = await OperationsAPI.getClients(query, 1, '');
            const users  = result.data ?? [];

            if (users.length === 0) {
                resultsEl.innerHTML = '<p class="text-xs text-slate-400 px-3 py-2 italic">No users found.</p>';
                return;
            }

            resultsEl.innerHTML = users.map((u) => `
                <button
                    onclick="OperationsNotifications.selectUser(${u.id}, '${_esc(u.fullname)}', '${_esc(u.phone_number)}')"
                    class="w-full text-left px-3 py-2 text-sm hover:bg-primary-50 dark:hover:bg-primary-900/20
                           flex items-center justify-between transition-colors
                           ${state.selectedUsers.has(u.id) ? 'opacity-40 pointer-events-none' : ''}">
                    <span class="font-medium">${_esc(u.fullname)}</span>
                    <span class="text-xs text-slate-400">${_esc(u.phone_number)}</span>
                </button>
            `).join('');

        } catch (e) {
            resultsEl.innerHTML = `<p class="text-xs text-rose-500 px-3 py-2">${e.message}</p>`;
        }
    }

    // ─── User selection ───────────────────────────────────────────────────────
    function selectUser(id, fullname, phone) {
        if (state.selectedUsers.has(id)) return;

        state.selectedUsers.set(id, { id, fullname, phone_number: phone });
        _renderSelectedUsers();

        // Clear search
        const input = document.getElementById('notif-user-search');
        if (input) input.value = '';
        document.getElementById('notif-user-results')?.classList.add('hidden');
    }

    function deselectUser(id) {
        state.selectedUsers.delete(id);
        _renderSelectedUsers();
    }

    function _renderSelectedUsers() {
        const container = document.getElementById('notif-selected-users');
        const hint      = document.getElementById('notif-no-users-hint');
        if (!container) return;

        if (state.selectedUsers.size === 0) {
            container.innerHTML = '<p class="text-xs text-slate-400 italic" id="notif-no-users-hint">No users selected yet.</p>';
            return;
        }

        container.innerHTML = Array.from(state.selectedUsers.values()).map((u) => `
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-100 dark:bg-primary-900/30
                         text-primary-700 dark:text-primary-300 text-xs font-medium rounded-full">
                ${_esc(u.fullname)}
                <button onclick="OperationsNotifications.deselectUser(${u.id})"
                    class="hover:text-rose-500 transition-colors font-bold">&times;</button>
            </span>
        `).join('');
    }

    // ─── Send ─────────────────────────────────────────────────────────────────
    async function send() {
        const title  = document.getElementById('notif-title')?.value.trim();
        const body   = document.getElementById('notif-body')?.value.trim();
        const target = document.querySelector('input[name="notif-target"]:checked')?.value ?? 'all';

        if (!title || !body) {
            OperationsUI.toast('Title and body are required.', 'error');
            return;
        }

        if (target === 'specific' && state.selectedUsers.size === 0) {
            OperationsUI.toast('Select at least one user for targeted delivery.', 'error');
            return;
        }

        const payload = {
            title,
            body,
            target,
            ...(target === 'specific' && {
                user_ids: Array.from(state.selectedUsers.keys()),
            }),
        };

        _setResultsPanel('loading');

        try {
            const result = await OperationsAPI.sendNotification(payload);
            _setResultsPanel('success', result.data);
            _appendHistory(payload, result.data);
            OperationsUI.toast(`Dispatched to ${result.data?.dispatched ?? 0} user(s).`, 'success');
        } catch (e) {
            _setResultsPanel('error', e.message);
            OperationsUI.toast(e.message, 'error');
        }
    }

    // ─── Results panel ────────────────────────────────────────────────────────
    function _setResultsPanel(state, data = null) {
        const panel = document.getElementById('notif-results-panel');
        if (!panel) return;

        if (state === 'loading') {
            panel.innerHTML = `
                <div class="flex flex-col items-center gap-3 text-primary-500">
                    <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm font-medium">Dispatching…</p>
                </div>`;
            return;
        }

        if (state === 'error') {
            panel.innerHTML = `
                <div class="flex flex-col items-center gap-2 text-rose-500">
                    <span class="text-3xl">❌</span>
                    <p class="text-sm font-bold">Dispatch Failed</p>
                    <p class="text-xs text-slate-400">${_esc(data)}</p>
                </div>`;
            return;
        }

        // success
        const metrics = [
            { label: 'Dispatched',  value: data?.dispatched  ?? 0, color: 'text-emerald-600' },
            { label: 'Users Found', value: data?.total_users ?? 0, color: 'text-primary-600' },
            { label: 'Failed',      value: data?.failed      ?? 0, color: 'text-rose-500' },
        ];

        panel.innerHTML = `
            <div class="space-y-4 w-full px-4">
                <div class="flex items-center gap-2 text-emerald-600">
                    <span class="text-2xl">✅</span>
                    <p class="font-bold">Dispatch Complete</p>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    ${metrics.map((m) => `
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-3 text-center">
                            <p class="text-2xl font-black ${m.color}">${m.value}</p>
                            <p class="text-xs text-slate-400 font-medium">${m.label}</p>
                        </div>
                    `).join('')}
                </div>
                ${data?.reason ? `<p class="text-xs text-amber-600 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg">${_esc(data.reason)}</p>` : ''}
            </div>`;
    }

    function _appendHistory(payload, result) {
        const container = document.getElementById('notif-history');
        if (!container) return;

        const entry = document.createElement('div');
        entry.className = 'flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-xs';
        entry.innerHTML = `
            <div class="space-y-0.5 min-w-0">
                <p class="font-bold truncate">${_esc(payload.title)}</p>
                <p class="text-slate-400 truncate">${_esc(payload.body)}</p>
            </div>
            <div class="text-right shrink-0 ml-3 space-y-0.5">
                <p class="font-bold text-emerald-600">${result?.dispatched ?? 0} sent</p>
                <p class="text-slate-400">${new Date().toLocaleTimeString()}</p>
            </div>`;

        container.prepend(entry);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function _esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Public API
    return { init, send, selectUser, deselectUser };
})();

window.OperationsNotifications = OperationsNotifications;