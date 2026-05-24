// filePath: /home/lenovo/work/projects/the_pilates_studio_sy/public/js/operations/modules/notifications.js
// public/js/operations/modules/notifications.js
const OperationsNotifications = (() => {
    // ─── State ────────────────────────────────────────────────────────────────
    const state = {
        selectedUsers: new Map(),          // id → { id, fullname, phone_number }
        searchTimeout: null,
        // Pagination
        currentPage: 1,
        lastPage: 1,
        isLoading: false,
        searchQuery: '',
        // Cached user data for the current search (to avoid re‑fetching)
        users: [],
        // Dropdown scroll element reference
        dropdownEl: null,
        isFocused: false,
        blurTimer: null,
    };
    const PER_PAGE = 15;   // matches backend default

    // ─── Init ─────────────────────────────────────────────────────────────────
    function init() {
        _bindCharCounters();
        _bindTargetToggle();
        _bindUserSearch();
        _bindDropdownScroll();
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
                    _clearSearch();
                }
            });
        });
    }

    // ─── Search input ─────────────────────────────────────────────────────────
    function _bindUserSearch() {
        const input = document.getElementById('notif-user-search');
        if (!input) return;

        input.addEventListener('focus', () => {
            state.isFocused = true;
            clearTimeout(state.blurTimer);
            if (state.users.length > 0 || state.isLoading) {
                _renderDropdown();
                return;
            }
            _resetAndSearch('');
        });

        input.addEventListener('blur', () => {
            state.blurTimer = setTimeout(() => {
                if (state.isFocused) return;
                _hideDropdown();
            }, 200);
        });

        const dropdown = document.getElementById('notif-user-results');
        if (dropdown) {
            dropdown.addEventListener('mouseenter', () => {
                clearTimeout(state.blurTimer);
                state.isFocused = true;
            });
            dropdown.addEventListener('mouseleave', () => {
                state.isFocused = false;
                if (document.activeElement !== input) {
                    state.blurTimer = setTimeout(() => {
                        if (!state.isFocused) _hideDropdown();
                    }, 200);
                }
            });
        }

        input.addEventListener('input', (e) => {
            clearTimeout(state.searchTimeout);
            state.searchTimeout = setTimeout(() => {
                const query = e.target.value.trim();
                if (query === state.searchQuery) return;
                _resetAndSearch(query);
            }, 300);
        });
    }

    // ─── Hide dropdown helper ─────────────────────────────────────────────────
    function _hideDropdown() {
        const dropdown = state.dropdownEl;
        if (!dropdown) return;
        dropdown.classList.add('hidden');
    }

    // ─── Dropdown scroll listener ─────────────────────────────────────────────
    function _bindDropdownScroll() {
        const dropdown = document.getElementById('notif-user-results');
        if (!dropdown) return;
        state.dropdownEl = dropdown;
        dropdown.addEventListener('scroll', () => {
            if (state.isLoading) return;
            const { scrollTop, scrollHeight, clientHeight } = dropdown;
            // Fire when within 40px of the bottom
            if (scrollTop + clientHeight >= scrollHeight - 40) {
                _loadNextPage();
            }
        });
    }

    // ─── Reset search & load page 1 ───────────────────────────────────────────
    function _resetAndSearch(query) {
        state.searchQuery = query;
        state.currentPage = 1;
        state.lastPage = 1;
        state.users = [];
        state.isLoading = true;
        _renderDropdown();          // show loading
        _fetchPage(1);
    }

    // ─── Load next page (if available) ────────────────────────────────────────
    function _loadNextPage() {
        if (state.isLoading) return;
        if (state.currentPage >= state.lastPage) return;
        state.isLoading = true;
        _renderDropdown();
        _fetchPage(state.currentPage + 1);
    }

    // ─── Fetch a specific page from the API ───────────────────────────────────
    async function _fetchPage(page) {
        const dropdown = state.dropdownEl;
        if (!dropdown) return;
        try {
            const result = await OperationsAPI.getClients(
                state.searchQuery,
                page,
                '',           // filter
                PER_PAGE
            );
            const users = result.data ?? [];
            const meta = result.meta?.pagination ?? {};
            state.lastPage = meta.total_pages ?? 1;
            state.currentPage = page;
            if (page === 1) {
                state.users = users;
            } else {
                state.users = state.users.concat(users);
            }
            state.isLoading = false;
            _renderDropdown();
        } catch (e) {
            console.error('User search failed', e);
            state.isLoading = false;
            _renderDropdown();
        }
    }

    // ─── Render the dropdown list (users + loading indicator) ─────────────────
    function _renderDropdown() {
        const dropdown = state.dropdownEl;
        if (!dropdown) return;

        const shouldShow = (state.isFocused && (state.users.length > 0 || state.isLoading)) ||
                           (state.searchQuery.length >= 2);
        if (!shouldShow) {
            dropdown.classList.add('hidden');
            return;
        }

        dropdown.classList.remove('hidden');

        let html = '';

        // Items
        if (state.users.length > 0) {
            html += state.users.map((u) => {
                const checked = state.selectedUsers.has(u.id) ? 'checked' : '';
                return `
                <label class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-primary-50 dark:hover:bg-primary-900/20 
                              cursor-pointer transition-colors rounded-lg">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                           ${checked}
                           onchange="OperationsNotifications.toggleUser(${u.id}, '${_esc(u.fullname)}', '${_esc(u.phone_number)}', this.checked)">
                    <span class="flex-1 text-sm font-medium text-gray-900 dark:text-white">${_esc(u.fullname)}</span>
                    <span class="text-xs text-slate-400">${_esc(u.phone_number)}</span>
                </label>`;
            }).join('');
        } else if (!state.isLoading) {
            html += '<p class="px-4 py-3 text-xs text-slate-400 italic">No users found.</p>';
        }

        // Loading indicator at the bottom
        if (state.isLoading) {
            html += `
            <div class="flex items-center justify-center px-4 py-3 text-primary-500 text-xs gap-2">
                <div class="w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                Loading…
            </div>`;
        }

        dropdown.innerHTML = html;
    }

    // ─── Toggle a user in/out of selection ────────────────────────────────────
    function toggleUser(id, fullname, phone, checked) {
        if (checked) {
            state.selectedUsers.set(id, { id, fullname, phone_number: phone });
        } else {
            state.selectedUsers.delete(id);
        }
        _renderSelectedUsers();
        // Re‑render the dropdown to reflect checkbox state
        _renderDropdown();
    }

    function deselectUser(id) {
        state.selectedUsers.delete(id);
        _renderSelectedUsers();
        _renderDropdown();
    }

    // ─── Render the selected‑user tags ────────────────────────────────────────
    function _renderSelectedUsers() {
        const container = document.getElementById('notif-selected-users');
        if (!container) return;

        if (state.selectedUsers.size === 0) {
            container.innerHTML = '<p class="text-xs text-slate-400 italic">No users selected yet.</p>';
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

    // ─── Clear search input & dropdown ─────────────────────────────────────────
    function _clearSearch() {
        const input = document.getElementById('notif-user-search');
        if (input) input.value = '';
        state.searchQuery = '';
        state.users = [];
        state.currentPage = 1;
        state.lastPage = 1;
        state.isLoading = false;
        _renderDropdown();
    }

    // ─── Send notification (unchanged) ────────────────────────────────────────
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

    // ─── Results panel (unchanged) ────────────────────────────────────────────
    function _setResultsPanel(status, data = null) {
        const panel = document.getElementById('notif-results-panel');
        if (!panel) return;

        if (status === 'loading') {
            panel.innerHTML = `
                <div class="flex flex-col items-center gap-3 text-primary-500">
                    <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm font-medium">Dispatching…</p>
                </div>`;
            return;
        }

        if (status === 'error') {
            panel.innerHTML = `
                <div class="flex flex-col items-center gap-2 text-rose-500">
                    <span class="text-3xl">❌</span>
                    <p class="text-sm font-bold">Dispatch Failed</p>
                    <p class="text-xs text-slate-400">${_esc(data)}</p>
                </div>`;
            return;
        }

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

    function _esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Public API
    return { init, send, toggleUser, deselectUser };
})();

window.OperationsNotifications = OperationsNotifications;

export function initNotificationsTab() {
    OperationsNotifications.init();
}