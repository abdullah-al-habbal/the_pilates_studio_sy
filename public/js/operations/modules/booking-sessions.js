// public/js/operations/modules/booking-sessions.js
// "View Classes" breakdown for one package, batch status changes, and direct date editing.

const L = (key) => window.__(`operations_ui.clients.${key}`);

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
}[char]));

let current = { bookingId: null, userId: null, rows: [], adds: [], options: null, found: [] };

function statusLabel(row) {
    if (row.status === 'cancelled') return L('sessions_cancelled');
    if (row.attendance_status === 'attended') return L('sessions_attended');
    return L('sessions_missed');
}

function choices(row) {
    const options = [`<option value="">${escapeHtml(L('sessions_keep'))}</option>`];
    if (row.status !== 'reserved') return options.join('');
    if (row.can_mark_attended && row.attendance_status !== 'attended') {
        options.push(`<option value="attended">${escapeHtml(L('sessions_attended'))}</option>`);
    }
    if (row.can_mark_missed && row.attendance_status !== 'missed') {
        options.push(`<option value="missed">${escapeHtml(L('sessions_missed'))}</option>`);
    }
    if (row.can_cancel) {
        const label = row.inside_cancel_window ? L('sessions_cancelled_waiver') : L('sessions_cancelled');
        options.push(`<option value="cancelled">${escapeHtml(label)}</option>`);
    }
    if (row.can_cancel && row.inside_cancel_window) {
        options.push(`<option value="late_cancelled">${escapeHtml(L('sessions_late_cancel'))}</option>`);
    }
    return options.join('');
}

function rowHtml(row) {
    const editable = row.status === 'reserved' && (row.can_cancel || row.can_mark_attended || row.can_mark_missed);
    const badge = row.inside_cancel_window
        ? `<span class="ml-2 text-[10px] font-bold uppercase text-amber-600">${escapeHtml(L('sessions_inside_window'))}</span>`
        : '';

    return `
        <tr data-bs-row="${row.id}" class="border-t border-slate-100 dark:border-slate-800">
            <td class="px-3 py-3 text-sm">
                <div class="font-bold">${escapeHtml(row.class_title ?? '—')}</div>
                <div class="text-xs text-slate-400">${escapeHtml(row.instructor_name ?? '')}</div>
                ${row.category_name ? `<span class="mt-1 inline-block rounded-full bg-primary-50 dark:bg-primary-900/30 px-2 py-0.5 text-[10px] font-bold text-primary-700 dark:text-primary-300">${escapeHtml(row.category_name)}</span>` : ''}
            </td>
            <td class="px-3 py-3 text-sm text-slate-500">${escapeHtml(row.date ?? '')} ${escapeHtml(row.starts_at ?? '')}${badge}</td>
            <td class="px-3 py-3 text-sm font-medium">${escapeHtml(statusLabel(row))}</td>
            <td class="px-3 py-3">
                ${editable ? `
                <select data-bs-select="${row.id}" onchange="window.bookingSessionsRowChanged(${row.id})"
                        class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 text-sm">
                    ${choices(row)}
                </select>
                ${row.inside_cancel_window ? `
                <label data-bs-override="${row.id}" class="hidden mt-1 text-xs text-amber-700 items-center gap-1">
                    <input type="checkbox" data-bs-override-input="${row.id}"> ${escapeHtml(L('sessions_override'))}
                </label>` : ''}` : ''}
            </td>
        </tr>`;
}

const today = () => new Date().toLocaleDateString('en-CA');

function selectHtml(id, label, items, handler) {
    return `<select id="${id}" aria-label="${escapeHtml(label)}" onchange="${handler}()"
                class="min-w-0 flex-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs">
            <option value="">${escapeHtml(label)}</option>
            ${(items ?? []).map((o) => `<option value="${o.id}">${escapeHtml(o.name)}</option>`).join('')}
        </select>`;
}

function queuedHtml() {
    if (current.adds.length === 0) return '';
    return `<ul class="mt-2 space-y-1 text-xs">${current.adds.map((a, i) => `
        <li class="flex items-center justify-between rounded-lg bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1.5">
            <span>${escapeHtml(a.label)} &middot; <strong>${escapeHtml(L('add_as_' + a.status))}</strong></span>
            <button type="button" onclick="window.bookingSessionsUnqueue(${i})" class="font-bold text-rose-600">&times;</button>
        </li>`).join('')}</ul>`;
}

function foundHtml() {
    if (current.found.length === 0) return `<p class="py-3 text-center text-xs text-slate-400">${escapeHtml(L('add_none'))}</p>`;
    return current.found.map((c) => `
        <div class="flex items-center justify-between gap-2 px-3 py-2">
            <div class="min-w-0 text-xs"><div class="font-bold truncate">${escapeHtml(c.title)}</div>
                <div class="text-slate-400">${escapeHtml(c.start_time)} &middot; ${escapeHtml(c.instructor ?? '')}</div></div>
            <div class="flex shrink-0 gap-1">
                <select data-bs-add-status="${c.id}" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-1 py-1 text-xs">
                    <option value="reserved">${escapeHtml(L('add_as_reserved'))}</option>
                    <option value="attended">${escapeHtml(L('add_as_attended'))}</option>
                    <option value="missed">${escapeHtml(L('add_as_missed'))}</option>
                </select>
                <button type="button" onclick="window.bookingSessionsQueue(${c.id})" class="rounded-lg bg-primary-600 px-2 py-1 text-xs font-bold text-white">${escapeHtml(L('add_button'))}</button>
            </div>
        </div>`).join('');
}

function addSectionHtml() {
    return `
        <div class="mt-5 rounded-2xl border-2 border-slate-100 dark:border-slate-800 p-3">
            <h5 class="mb-2 text-xs font-bold uppercase text-slate-500">${escapeHtml(L('add_title'))}</h5>
            <div class="flex flex-wrap gap-2">
                <input type="date" id="bs-add-date" value="${today()}" onchange="window.bookingSessionsSearch()"
                       class="min-w-0 flex-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs">
                ${selectHtml('bs-add-instructor', L('add_all_instructors'), current.options?.instructors, 'window.bookingSessionsSearch')}
                ${selectHtml('bs-add-category', L('add_all_categories'), current.options?.categories, 'window.bookingSessionsSearch')}
            </div>
            <div id="bs-add-results" class="mt-2 max-h-44 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">${foundHtml()}</div>
            <div id="bs-add-queued">${queuedHtml()}</div>
        </div>`;
}

function render() {
    const table = current.rows.length === 0
        ? `<p class="py-6 text-center text-slate-400 italic">${escapeHtml(L('sessions_empty'))}</p>`
        : `<div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr class="text-xs uppercase text-slate-400">
                    <th class="px-3 py-2">${escapeHtml(L('sessions_col_class'))}</th>
                    <th class="px-3 py-2">${escapeHtml(L('sessions_col_when'))}</th>
                    <th class="px-3 py-2">${escapeHtml(L('sessions_col_status'))}</th>
                    <th class="px-3 py-2"></th>
                </tr></thead>
                <tbody>${current.rows.map(rowHtml).join('')}</tbody>
            </table>
        </div>`;

    OperationsUI.openModal(L('sessions_modal_title'), `
        ${table}
        ${addSectionHtml()}
        <div class="flex justify-end pt-4">
            <button id="bs-save" onclick="window.saveBookingSessions()"
                    class="px-5 py-2.5 rounded-xl bg-primary-600 text-white font-bold text-sm hover:bg-primary-700">
                ${escapeHtml(L('sessions_save'))}
            </button>
        </div>`);
}

export async function bookingSessionsSearch() {
    const date = document.getElementById('bs-add-date')?.value || today();
    const instructor = document.getElementById('bs-add-instructor')?.value || null;
    const category = document.getElementById('bs-add-category')?.value || null;
    const token = (current.searchToken = (current.searchToken ?? 0) + 1);

    try {
        const response = await OperationsAPI.getSchedulerSessions(date, instructor, category);
        if (token !== current.searchToken) return;
        current.found = (response.data ?? []).map((c) => ({ ...c, date }));
    } catch (error) {
        if (token !== current.searchToken) return;
        current.found = [];
    }
    const box = document.getElementById('bs-add-results');
    if (box) box.innerHTML = foundHtml();
}

export function bookingSessionsQueue(classSessionId) {
    const found = current.found.find((c) => Number(c.id) === Number(classSessionId));
    if (!found || current.adds.some((a) => a.class_session_id === found.id)) return;
    const status = document.querySelector(`[data-bs-add-status="${found.id}"]`)?.value || 'reserved';
    current.adds.push({
        class_session_id: found.id,
        status,
        label: `${found.title} ${found.date} ${found.start_time}`,
    });
    const box = document.getElementById('bs-add-queued');
    if (box) box.innerHTML = queuedHtml();
}

export function bookingSessionsUnqueue(index) {
    current.adds.splice(index, 1);
    const box = document.getElementById('bs-add-queued');
    if (box) box.innerHTML = queuedHtml();
}

export async function showBookingSessions(bookingId, userId) {
    current = { bookingId, userId, rows: [], adds: [], options: null, found: [] };
    try {
        const response = await OperationsAPI.getBookingSessions(bookingId);
        current.rows = response.data ?? [];
        try {
            current.options = (await OperationsAPI.getClassOptions()).data ?? null;
        } catch (e) {
            current.options = null;
        }
        render();
        bookingSessionsSearch();
    } catch (error) {
        OperationsUI.toast(error.message || L('sessions_load_failed'), 'error');
    }
}

export function bookingSessionsRowChanged(id) {
    const select = document.querySelector(`[data-bs-select="${id}"]`);
    const override = document.querySelector(`[data-bs-override="${id}"]`);
    if (!select || !override) return;
    override.classList.toggle('hidden', select.value !== 'cancelled');
    override.classList.toggle('flex', select.value === 'cancelled');
}

export async function saveBookingSessions() {
    const items = [];
    document.querySelectorAll('[data-bs-select]').forEach((select) => {
        if (!select.value) return;
        const id = Number(select.dataset.bsSelect);
        const item = { booking_session_id: id, status: select.value };
        if (select.value === 'cancelled') {
            item.override = Boolean(document.querySelector(`[data-bs-override-input="${id}"]`)?.checked);
        }
        items.push(item);
    });

    current.adds.forEach((a) => items.push({ class_session_id: a.class_session_id, status: a.status }));

    if (items.length === 0) {
        OperationsUI.toast(L('sessions_nothing_changed'), 'error');
        return;
    }

    const button = document.getElementById('bs-save');
    if (button) button.disabled = true;

    try {
        await OperationsAPI.batchUpdateBookingSessions(current.bookingId, items);
        OperationsUI.toast(L('sessions_saved'), 'success');
        await showBookingSessions(current.bookingId, current.userId);
    } catch (error) {
        OperationsUI.toast(error.message, 'error');
        if (button) button.disabled = false;
    }
}

export function showBookingEdit(bookingId, userId, purchasedAt, expiresAt) {
    current = { bookingId, userId, rows: [] };
    OperationsUI.openModal(L('edit_modal_title'), `
        <div class="space-y-4">
            <p class="text-sm text-slate-500">${escapeHtml(L('edit_hint'))}</p>
            <label class="block text-xs font-bold text-slate-500 uppercase">${escapeHtml(L('edit_purchased_at'))}
                <input type="date" id="be-purchased-at" value="${escapeHtml(purchasedAt)}"
                       class="mt-1 block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm normal-case">
            </label>
            <label class="block text-xs font-bold text-slate-500 uppercase">${escapeHtml(L('edit_expires_at'))}
                <input type="date" id="be-expires-at" value="${escapeHtml(expiresAt)}"
                       class="mt-1 block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm normal-case">
            </label>
            <div class="flex justify-end">
                <button id="be-save" onclick="window.saveBookingEdit()"
                        class="px-5 py-2.5 rounded-xl bg-primary-600 text-white font-bold text-sm hover:bg-primary-700">
                    ${escapeHtml(L('sessions_save'))}
                </button>
            </div>
        </div>`);
}

export async function saveBookingEdit() {
    const purchasedAt = document.getElementById('be-purchased-at')?.value;
    const expiresAt = document.getElementById('be-expires-at')?.value;
    if (!purchasedAt) return;

    const button = document.getElementById('be-save');
    if (button) button.disabled = true;

    try {
        await OperationsAPI.updateBooking(current.bookingId, { purchasedAt, expiresAt });
        OperationsUI.toast(L('edit_saved'), 'success');
        if (current.userId) await window.showClientDetails(current.userId);
        else OperationsUI.closeModal();
    } catch (error) {
        OperationsUI.toast(error.message, 'error');
        if (button) button.disabled = false;
    }
}

window.showBookingSessions = showBookingSessions;
window.bookingSessionsRowChanged = bookingSessionsRowChanged;
window.saveBookingSessions = saveBookingSessions;
window.showBookingEdit = showBookingEdit;
window.saveBookingEdit = saveBookingEdit;

window.bookingSessionsSearch = bookingSessionsSearch;
window.bookingSessionsQueue = bookingSessionsQueue;
window.bookingSessionsUnqueue = bookingSessionsUnqueue;
