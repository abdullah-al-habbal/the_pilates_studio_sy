// public/js/operations/modules/booking-sessions.js
// "View Classes" breakdown for one package, batch status changes, and direct date editing.

const L = (key) => window.__(`operations_ui.clients.${key}`);

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
}[char]));

let current = { bookingId: null, userId: null, rows: [] };

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
        options.push(`<option value="cancelled">${escapeHtml(L('sessions_cancelled'))}</option>`);
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

function render() {
    const body = current.rows.length === 0
        ? `<p class="py-10 text-center text-slate-400 italic">${escapeHtml(L('sessions_empty'))}</p>`
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
        </div>
        <div class="flex justify-end pt-4">
            <button id="bs-save" onclick="window.saveBookingSessions()"
                    class="px-5 py-2.5 rounded-xl bg-primary-600 text-white font-bold text-sm hover:bg-primary-700">
                ${escapeHtml(L('sessions_save'))}
            </button>
        </div>`;

    OperationsUI.openModal(L('sessions_modal_title'), body);
}

export async function showBookingSessions(bookingId, userId) {
    current = { bookingId, userId, rows: [] };
    try {
        const response = await OperationsAPI.getBookingSessions(bookingId);
        current.rows = response.data ?? [];
        render();
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
