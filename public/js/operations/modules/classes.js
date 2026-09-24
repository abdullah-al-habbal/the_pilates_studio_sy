import { localDateValue } from './pickers.js';

const state = {
    options: null,
    page: 1,
    perPage: 20,
    search: '',
    status: '',
    scope: 'active',
    wizard: null,
    listRequestId: 0,
    popstateBound: false,
};

const perPageOptions = [10, 20, 50];

let searchTimer = null;

const t = (key, fallback) => {
    const value = window.__(`operations_ui.classes.${key}`);
    return value === `operations_ui.classes.${key}` ? fallback : value;
};

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[char]));

const display = (translated) => translated?.[document.documentElement.lang?.startsWith('ar') ? 'ar' : 'en']
    || translated?.en || translated?.ar || '';

const formatTime = (value) => {
    const [hours = 0, minutes = 0] = String(value ?? '').split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    return `${hours % 12 || 12}:${String(minutes).padStart(2, '0')} ${period}`;
};

const defaultForm = () => ({
    title: { en: '', ar: '' }, about: { en: '', ar: '' }, instructor_id: '', class_category_id: '',
    status: 'active', schedule_mode: 'weekdays', weekdays: [], recurrence_pattern_id: '',
    start_date: localDateValue(), end_date: localDateValue(), start_time: '09:00:00', end_time: '10:00:00', total_spots: 20,
});

async function ensureOptions() {
    if (state.options) return state.options;
    const response = await OperationsAPI.getClassOptions();
    state.options = response.data;
    return state.options;
}

export async function initClassesTab() {
    const search = document.getElementById('classes-search');
    const status = document.getElementById('classes-status-filter');
    const scope = document.getElementById('classes-scope-filter');
    const perPage = document.getElementById('classes-per-page');
    const create = document.getElementById('classes-create-button');
    if (!search || !status || !scope || !perPage || !create) return;

    hydrateListStateFromUrl();

    try {
        const options = await ensureOptions();
        status.innerHTML = `<option value="">${escapeHtml(t('all_statuses', 'All statuses'))}</option>${options.statuses.map((item) => `<option value="${item.value}">${escapeHtml(item.label)}</option>`).join('')}`;
    } catch (error) {
        OperationsUI.toast(error.message, 'error');
        return;
    }

    normalizeListState();
    syncListControls();
    syncListUrl({ replace: true });
    bindPopstateListener();

    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        state.search = search.value;
        state.page = 1;
        syncListUrl({ replace: true });
        searchTimer = setTimeout(loadClasses, 250);
    });
    status.addEventListener('change', () => { state.status = status.value; state.page = 1; syncListUrl(); loadClasses(); });
    scope.addEventListener('change', () => { state.scope = scope.value; state.page = 1; syncListUrl(); loadClasses(); });
    perPage.addEventListener('change', () => {
        state.perPage = Number(perPage.value);
        state.page = 1;
        syncListUrl();
        loadClasses();
    });
    create.addEventListener('click', () => openWizard());
    loadClasses();
}

function hydrateListStateFromUrl() {
    const params = new URL(window.location.href).searchParams;
    const page = Number.parseInt(params.get('page') ?? '', 10);
    const perPage = Number.parseInt(params.get('per_page') ?? '', 10);

    state.search = params.get('search') ?? '';
    state.status = params.get('status') ?? '';
    state.scope = params.get('scope') ?? 'active';
    state.page = Number.isInteger(page) && page > 0 ? page : 1;
    state.perPage = perPageOptions.includes(perPage) ? perPage : 20;
}

function normalizeListState() {
    const statuses = state.options?.statuses?.map((item) => item.value) ?? [];
    if (!statuses.includes(state.status)) state.status = '';
    if (!['active', 'all', 'trashed'].includes(state.scope)) state.scope = 'active';
}

function syncListControls() {
    const search = document.getElementById('classes-search');
    const status = document.getElementById('classes-status-filter');
    const scope = document.getElementById('classes-scope-filter');
    const perPage = document.getElementById('classes-per-page');

    if (search) search.value = state.search;
    if (status) status.value = state.status;
    if (scope) scope.value = state.scope;
    if (perPage) perPage.value = String(state.perPage);
}

function syncListUrl({ replace = false } = {}) {
    const url = new URL(window.location.href);
    const params = [
        ['search', state.search, ''],
        ['status', state.status, ''],
        ['scope', state.scope, 'active'],
        ['per_page', String(state.perPage), '20'],
        ['page', String(state.page), '1'],
    ];
    for (const [key, value, fallback] of params) {
        if (value === '' || value === fallback) url.searchParams.delete(key);
        else url.searchParams.set(key, value);
    }
    url.hash = 'classes';

    history[replace ? 'replaceState' : 'pushState']({}, '', url);
}

function bindPopstateListener() {
    if (state.popstateBound) return;
    state.popstateBound = true;

    window.addEventListener('popstate', () => {
        if (window.location.hash !== '#classes') return;
        // Cross-tab back/forward: hash flips before tabs.js re-renders the classes
        // DOM. Let hashchange/initClassesTab own that load to avoid a duplicate GET.
        if (!document.getElementById('classes-search')) return;

        clearTimeout(searchTimer);
        hydrateListStateFromUrl();
        normalizeListState();
        syncListControls();
        loadClasses();
    });
}

async function loadClasses() {
    const body = document.getElementById('classes-table-body');
    if (!body) return;
    const requestId = ++state.listRequestId;
    body.innerHTML = `<tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">${escapeHtml(t('loading', 'Loading classes…'))}</td></tr>`;
    try {
        const response = await OperationsAPI.getClasses({
            page: state.page,
            per_page: state.perPage,
            search: state.search,
            status: state.status,
            scope: state.scope,
        });
        if (requestId !== state.listRequestId) return;
        renderList(response.data ?? [], response.meta ?? {});
    } catch (error) {
        if (requestId !== state.listRequestId) return;
        body.innerHTML = `<tr><td colspan="5" class="px-5 py-10 text-center text-rose-500">${escapeHtml(error.message)}</td></tr>`;
    }
}

function renderList(classes, meta) {
    const body = document.getElementById('classes-table-body');
    const pagination = document.getElementById('classes-pagination');
    if (!body || !pagination) return;
    if (!classes.length) {
        if (meta.last_page > 0 && meta.current_page > meta.last_page) {
            state.page = meta.last_page;
            syncListUrl({ replace: true });
            loadClasses();
            return;
        }
        body.innerHTML = `<tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">${escapeHtml(t('empty', 'No classes found.'))}</td></tr>`;
        pagination.innerHTML = paginationSummary(meta);
        return;
    }
    body.innerHTML = classes.map((item) => {
        const schedule = item.schedule;
        return `<tr class="border-b border-slate-100 dark:border-slate-800 last:border-0 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
            <td class="px-5 py-4"><div class="font-bold">${escapeHtml(item.display_title)}</div><div class="text-xs text-slate-500">${escapeHtml(item.instructor?.display_name || t('no_instructor', 'No instructor'))}</div></td>
            <td class="px-5 py-4 text-sm"><div>${escapeHtml(schedule.start_date)} – ${escapeHtml(schedule.end_date)}</div><div class="text-xs text-slate-500">${escapeHtml(formatTime(schedule.start_time))} – ${escapeHtml(formatTime(schedule.end_time))}</div></td>
            <td class="px-5 py-4 text-sm">${item.total_spots} <span class="text-slate-400">· ${item.upcoming_sessions_count} ${escapeHtml(t('sessions', 'sessions'))}</span></td>
            <td class="px-5 py-4"><span class="px-2 py-1 rounded-full text-xs font-bold ${item.trashed ? 'bg-rose-100 text-rose-700' : item.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${escapeHtml(item.trashed ? t('deleted', 'Deleted') : item.status)}</span></td>
            <td class="px-5 py-4 text-right"><details class="relative inline-block text-left"><summary class="cursor-pointer list-none rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800" aria-label="${escapeHtml(t('actions', 'Actions'))}">•••</summary><div class="absolute right-0 z-10 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg dark:border-slate-700 dark:bg-slate-900"><a href="${classPageUrl(item.id, 'view')}" class="block px-4 py-2 font-medium hover:bg-slate-50 dark:hover:bg-slate-800">${escapeHtml(t('view', 'View'))}</a>${item.trashed ? `<button data-class-restore="${item.id}" class="block w-full px-4 py-2 text-left font-medium text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/30">${escapeHtml(t('restore', 'Restore'))}</button><button data-class-force="${item.id}" class="block w-full px-4 py-2 text-left font-medium text-rose-700 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30">${escapeHtml(t('force_delete', 'Delete permanently'))}</button>` : `<a href="${classPageUrl(item.id, 'edit')}" class="block px-4 py-2 font-medium hover:bg-slate-50 dark:hover:bg-slate-800">${escapeHtml(t('edit', 'Edit'))}</a><button data-class-status="${item.id}" data-status="${item.status === 'active' ? 'inactive' : 'active'}" class="block w-full px-4 py-2 text-left font-medium text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/30">${escapeHtml(item.status === 'active' ? t('deactivate', 'Mark inactive') : t('activate', 'Reactivate'))}</button><button data-class-delete="${item.id}" class="block w-full px-4 py-2 text-left font-medium text-rose-700 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30">${escapeHtml(t('delete', 'Delete'))}</button>`}</div></details></td>
        </tr>`;
    }).join('');
    bindListActions(body);
    pagination.innerHTML = `${paginationSummary(meta)}<div class="flex gap-2"><button ${meta.current_page <= 1 ? 'disabled' : ''} id="classes-prev" class="px-3 py-1 rounded-lg border disabled:opacity-40">${escapeHtml(t('previous', 'Previous'))}</button><button ${meta.current_page >= meta.last_page ? 'disabled' : ''} id="classes-next" class="px-3 py-1 rounded-lg border disabled:opacity-40">${escapeHtml(t('next', 'Next'))}</button></div>`;
    document.getElementById('classes-prev')?.addEventListener('click', () => { state.page = meta.current_page - 1; syncListUrl(); loadClasses(); });
    document.getElementById('classes-next')?.addEventListener('click', () => { state.page = meta.current_page + 1; syncListUrl(); loadClasses(); });
}

const classPageUrl = (classId, page) => `/admin/operations/classes/${classId}/${page}`;

function bindListActions(container) {
    container.querySelectorAll('[data-class-status]').forEach((button) => button.addEventListener('click', () => mutate(null, () => OperationsAPI.setClassStatus(button.dataset.classStatus, button.dataset.status))));
    container.querySelectorAll('[data-class-delete]').forEach((button) => button.addEventListener('click', () => confirmMutation(t('delete_confirm', 'Delete this class?'), () => OperationsAPI.deleteClass(button.dataset.classDelete))));
    container.querySelectorAll('[data-class-restore]').forEach((button) => button.addEventListener('click', () => mutate(null, () => OperationsAPI.restoreClass(button.dataset.classRestore))));
    container.querySelectorAll('[data-class-force]').forEach((button) => button.addEventListener('click', () => confirmMutation(t('force_confirm', 'Permanently delete this class and its images?'), () => OperationsAPI.forceDeleteClass(button.dataset.classForce))));
}

function paginationSummary(meta) {
    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    return `<span class="text-sm text-slate-500">${escapeHtml(
        t('pagination_summary', 'Showing :from to :to of :total entries.')
            .replace(':from', from)
            .replace(':to', to)
            .replace(':total', total),
    )}</span>`;
}

async function showClass(classId) {
    try {
        const response = await OperationsAPI.getClass(classId);
        const item = response.data;
        const sessions = (item.sessions ?? []).map((session) => `<li class="flex justify-between gap-3 py-2 border-b border-slate-100 dark:border-slate-800"><span>${escapeHtml(session.date)} · ${escapeHtml(formatTime(session.start_time))}</span><span>${session.available_spots}/${session.total_spots} ${session.booking_sessions_count ? '' : `<button data-session-edit="${session.id}" class="ml-2 text-primary-600">${escapeHtml(t('edit', 'Edit'))}</button><button data-session-delete="${session.id}" class="ml-2 text-rose-600">×</button>`}</span></li>`).join('') || `<li class="text-slate-400">${escapeHtml(t('no_sessions', 'No sessions'))}</li>`;
        const images = (item.images ?? []).map((image) => `<div class="relative"><img src="${escapeHtml(image.image_url)}" alt="" class="h-20 w-20 rounded-lg object-cover"><div class="absolute inset-x-0 bottom-0 flex gap-1 bg-slate-950/60 p-1 text-[10px]"><button data-image-primary="${image.id}" class="text-white">${image.is_primary ? '★' : '☆'}</button><button data-image-delete="${image.id}" class="text-rose-200">×</button></div></div>`).join('');
        OperationsUI.openModal(escapeHtml(item.display_title), `<div class="space-y-5"><div class="grid md:grid-cols-3 gap-3 text-sm"><div><b>${escapeHtml(t('category', 'Category'))}</b><br>${escapeHtml(item.category?.display_name || '—')}</div><div><b>${escapeHtml(t('capacity', 'Capacity'))}</b><br>${item.total_spots}</div><div><b>${escapeHtml(t('bookings', 'Bookings'))}</b><br>${item.booking_sessions_count}</div></div><div><b>${escapeHtml(t('schedule', 'Schedule'))}</b><p class="text-slate-500">${escapeHtml(item.schedule.start_date)} – ${escapeHtml(item.schedule.end_date)} · ${escapeHtml(formatTime(item.schedule.start_time))} – ${escapeHtml(formatTime(item.schedule.end_time))}</p></div><div><div class="flex justify-between"><b>${escapeHtml(t('sessions', 'Sessions'))}</b><button id="session-add" class="text-primary-600 font-bold text-sm">+ ${escapeHtml(t('add_session', 'Add session'))}</button></div><ul class="text-sm">${sessions}</ul></div><div><b>${escapeHtml(t('images', 'Images'))}</b><div class="mt-2 flex flex-wrap gap-2">${images}<label class="h-20 w-20 rounded-lg border border-dashed flex items-center justify-center cursor-pointer text-xl">+<input id="class-image-upload" type="file" accept="image/jpeg,image/png,image/webp" class="hidden"></label></div></div><div class="flex flex-wrap gap-2"><button id="class-edit" class="px-4 py-2 rounded-xl bg-primary-600 text-white font-bold">${escapeHtml(t('edit', 'Edit'))}</button>${detailActions(item)}</div></div>`);
        document.getElementById('class-edit')?.addEventListener('click', () => openWizard(item));
        bindDetailActions(item);
        document.getElementById('class-image-upload')?.addEventListener('change', async (event) => {
            const file = event.currentTarget.files?.[0]; if (!file) return;
            try { await OperationsAPI.uploadClassImage(item.id, file, !(item.images ?? []).length); showClass(item.id); } catch (error) { OperationsUI.toast(error.message, 'error'); }
        });
        document.querySelectorAll('[data-image-primary]').forEach((button) => button.addEventListener('click', async () => { try { await OperationsAPI.setPrimaryClassImage(item.id, button.dataset.imagePrimary); showClass(item.id); } catch (error) { OperationsUI.toast(error.message, 'error'); } }));
        document.querySelectorAll('[data-image-delete]').forEach((button) => button.addEventListener('click', async () => { try { await OperationsAPI.deleteClassImage(item.id, button.dataset.imageDelete); showClass(item.id); } catch (error) { OperationsUI.toast(error.message, 'error'); } }));
        document.getElementById('session-add')?.addEventListener('click', () => openSessionForm(item));
        document.querySelectorAll('[data-session-edit]').forEach((button) => button.addEventListener('click', () => {
            openSessionForm(item, item.sessions.find((session) => Number(session.id) === Number(button.dataset.sessionEdit)));
        }));
        document.querySelectorAll('[data-session-delete]').forEach((button) => button.addEventListener('click', () => {
            confirmMutation(t('delete_confirm', 'Delete this session?'), () => OperationsAPI.deleteClassSession(item.id, button.dataset.sessionDelete));
        }));
    } catch (error) { OperationsUI.toast(error.message, 'error'); }
}

function detailActions(item) {
    if (item.trashed) return `<button data-class-restore="${item.id}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold">${escapeHtml(t('restore', 'Restore'))}</button><button data-class-force="${item.id}" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold">${escapeHtml(t('force_delete', 'Delete permanently'))}</button>`;
    return `<button data-class-status="${item.id}" data-status="${item.status === 'active' ? 'inactive' : 'active'}" class="px-4 py-2 rounded-xl bg-amber-500 text-white font-bold">${escapeHtml(item.status === 'active' ? t('deactivate', 'Mark inactive') : t('activate', 'Reactivate'))}</button><button data-class-delete="${item.id}" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold">${escapeHtml(t('delete', 'Delete'))}</button>`;
}

function bindDetailActions(item) {
    document.querySelector('[data-class-status]')?.addEventListener('click', async (event) => mutate(item.id, () => OperationsAPI.setClassStatus(item.id, event.currentTarget.dataset.status)));
    document.querySelector('[data-class-delete]')?.addEventListener('click', async () => confirmMutation(t('delete_confirm', 'Delete this class?'), () => OperationsAPI.deleteClass(item.id)));
    document.querySelector('[data-class-restore]')?.addEventListener('click', async () => mutate(item.id, () => OperationsAPI.restoreClass(item.id)));
    document.querySelector('[data-class-force]')?.addEventListener('click', async () => confirmMutation(t('force_confirm', 'Permanently delete this class and its images?'), () => OperationsAPI.forceDeleteClass(item.id)));
}

async function mutate(id, action) {
    try { await action(); OperationsUI.closeModal(); OperationsUI.toast(t('saved', 'Saved.'), 'success'); loadClasses(); } catch (error) { OperationsUI.toast(error.message, 'error'); }
}

async function confirmMutation(message, action) {
    const result = await Swal.fire({ title: message, icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48' });
    if (result.isConfirmed) mutate(null, action);
}

async function openWizard(record = null) {
    try {
        await ensureOptions();
        state.wizard = createWizardState(record);
        renderWizard();
    } catch (error) { OperationsUI.toast(error.message, 'error'); }
}

function createWizardState(record, pageMode = false) {
    return { record, pageMode, step: 1, preview: null, submitting: false, form: record ? {
        title: record.title || {}, about: record.about || {}, instructor_id: record.instructor?.id || '', class_category_id: record.category?.id || '', status: record.status,
        schedule_mode: record.schedule.mode, weekdays: record.schedule.weekdays || [], recurrence_pattern_id: record.schedule.recurrence_pattern_id || '', start_date: record.schedule.start_date, end_date: record.schedule.end_date, start_time: record.schedule.start_time, end_time: record.schedule.end_time, total_spots: record.total_spots,
    } : defaultForm() };
}

function captureWizard() {
    const wizard = state.wizard; if (!wizard) return;
    const form = wizard.form;
    document.querySelectorAll('[data-class-field]').forEach((input) => {
        const key = input.dataset.classField;
        if (key.startsWith('title.') || key.startsWith('about.')) { const [group, locale] = key.split('.'); form[group][locale] = input.value; }
        else if (input.type === 'checkbox') { if (key === 'weekdays') form.weekdays = [...document.querySelectorAll('[data-class-field="weekdays"]:checked')].map((node) => node.value); }
        else if (input.type === 'radio' && input.checked) form[key] = input.value;
        else if (input.type !== 'radio') form[key] = input.value;
    });
}

function schedulePayload(form) {
    const payload = { ...form };

    if (payload.schedule_mode === 'weekdays') {
        delete payload.recurrence_pattern_id;
    } else {
        delete payload.weekdays;
    }

    return payload;
}

function validateWizardStepsThrough(lastStep) {
    const form = state.wizard?.form;
    if (!form) return false;

    const errors = [];
    const required = (value, message) => {
        if (String(value ?? '').trim() === '') errors.push(message);
    };

    if (lastStep >= 1) {
        required(form.title?.en, t('title_en_required', 'English title is required.'));
        required(form.class_category_id, t('category_required', 'Category is required.'));
        required(form.status, t('status_required', 'Status is required.'));
    }

    if (lastStep >= 2) {
        required(form.schedule_mode, t('schedule_mode_required', 'Choose a schedule type.'));
        if (form.schedule_mode === 'weekdays' && (!Array.isArray(form.weekdays) || form.weekdays.length === 0)) {
            errors.push(t('weekdays_required', 'Choose at least one weekday.'));
        }
        if (form.schedule_mode === 'interval') {
            required(form.recurrence_pattern_id, t('recurrence_required', 'Choose a recurrence pattern.'));
        }
    }

    if (lastStep >= 3) {
        required(form.start_date, t('start_date_required', 'Start date is required.'));
        required(form.end_date, t('end_date_required', 'End date is required.'));
        required(form.start_time, t('start_time_required', 'Start time is required.'));
        required(form.end_time, t('end_time_required', 'End time is required.'));
    }

    if (lastStep >= 4) {
        const totalSpots = Number(form.total_spots);
        if (!Number.isInteger(totalSpots) || totalSpots < 1 || totalSpots > 999) {
            errors.push(t('capacity_required', 'Capacity must be between 1 and 999.'));
        }
    }

    if (errors.length > 0) {
        showWizardErrors(errors);
        return false;
    }

    return true;
}

async function previewWizardSchedule(wizard) {
    wizard.preview = (await OperationsAPI.previewClass(schedulePayload(wizard.form))).data;
}

async function goToWizardStep(requestedStep) {
    const wizard = state.wizard;
    if (!wizard || requestedStep < 1 || requestedStep > 5 || requestedStep === wizard.step) return;

    captureWizard();

    if (requestedStep < wizard.step) {
        wizard.step = requestedStep;
        renderWizard();
        return;
    }

    if (!validateWizardStepsThrough(requestedStep - 1)) return;

    try {
        while (wizard.step < requestedStep) {
            if (wizard.step === 3) await previewWizardSchedule(wizard);
            wizard.step++;
        }
        renderWizard();
    } catch (error) {
        showWizardError(error);
    }
}

function renderWizard() {
    const wizard = state.wizard; const form = wizard.form; const options = state.options;
    const labels = [t('step_information', 'Information'), t('step_schedule', 'Schedule'), t('step_datetime', 'Date & time'), t('step_capacity', 'Capacity'), t('step_review', 'Review')];
    const stepper = `<div class="flex gap-2 mb-6 overflow-x-auto">${labels.map((label, index) => `<button data-wizard-step="${index + 1}" class="shrink-0 px-3 py-1.5 rounded-full text-xs font-bold ${wizard.step === index + 1 ? 'bg-primary-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'}">${index + 1}. ${escapeHtml(label)}</button>`).join('')}</div>`;
    let content = wizard.step === 1 ? stepInformation(form, options) : wizard.step === 2 ? stepSchedule(form, options) : wizard.step === 3 ? stepDateTime(form) : wizard.step === 4 ? stepCapacity(form) : stepReview(form, wizard.preview, options);
    const wizardMarkup = `${stepper}<div id="class-wizard-errors" class="hidden mb-4 rounded-xl bg-rose-50 text-rose-700 p-3 text-sm"></div>${content}<div class="flex justify-between gap-3 mt-7"><button id="wizard-back" ${wizard.step === 1 ? 'disabled' : ''} class="px-4 py-2 rounded-xl border disabled:opacity-40">${escapeHtml(t('back', 'Back'))}</button><button id="wizard-next" class="px-4 py-2 rounded-xl bg-primary-600 text-white font-bold">${escapeHtml(wizard.step === 5 ? (wizard.record ? t('save', 'Save changes') : t('create_sessions', 'Create class and sessions')) : t('next', 'Next'))}</button></div>`;
    const pageContainer = document.getElementById('class-wizard-page');
    if (wizard.pageMode && pageContainer) pageContainer.innerHTML = wizardMarkup;
    else OperationsUI.openModal(escapeHtml(wizard.record ? t('edit', 'Edit class') : t('create', 'Create class')), wizardMarkup);
    document.querySelectorAll('[data-wizard-step]').forEach((button) => button.addEventListener('click', () => goToWizardStep(Number(button.dataset.wizardStep))));
    document.querySelectorAll('[data-class-field="schedule_mode"]').forEach((input) => input.addEventListener('change', (event) => {
        wizard.form.schedule_mode = event.currentTarget.value;
        renderWizard();
    }));
    document.getElementById('wizard-back')?.addEventListener('click', () => { captureWizard(); wizard.step--; renderWizard(); });
    document.getElementById('wizard-next')?.addEventListener('click', () => nextWizard());
}

function comboField(type, field, label, placeholder, value, initialLabel) {
    return `<label>${escapeHtml(label)}
        <div data-combo-select data-combo-type="${type}" class="relative"${initialLabel ? ` data-combo-label="${escapeHtml(initialLabel)}"` : ''}>
            <input type="hidden" data-class-field="${field}" value="${escapeHtml(String(value ?? ''))}">
            <input type="text" data-combo-search autocomplete="off" class="field" placeholder="${escapeHtml(placeholder)}">
            <button type="button" data-combo-clear class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 text-lg leading-none">&times;</button>
            <div data-combo-results role="listbox" class="hidden absolute z-20 w-full mt-1 max-h-56 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm divide-y divide-slate-100 dark:divide-slate-800"></div>
        </div></label>`;
}

function stepInformation(form, options) {
    const record = state.wizard?.record;
    return `<div class="space-y-4"><div class="grid md:grid-cols-2 gap-4"><label>${escapeHtml(t('title_en', 'English title'))}<input data-class-field="title.en" value="${escapeHtml(form.title.en)}" class="field"></label><label>${escapeHtml(t('title_ar', 'Arabic title'))}<input data-class-field="title.ar" value="${escapeHtml(form.title.ar)}" class="field"></label></div><div class="grid md:grid-cols-2 gap-4">${comboField('instructor', 'instructor_id', t('instructor', 'Instructor'), t('instructor_placeholder', 'Search instructors…'), form.instructor_id, record?.instructor?.display_name)}${comboField('category', 'class_category_id', t('category', 'Category'), t('category_placeholder', 'Search categories…'), form.class_category_id, record?.category?.display_name)}</div><label>${escapeHtml(t('description_en', 'English description'))}<textarea data-class-field="about.en" class="field" rows="3">${escapeHtml(form.about.en)}</textarea></label><label>${escapeHtml(t('description_ar', 'Arabic description'))}<textarea data-class-field="about.ar" class="field" rows="3">${escapeHtml(form.about.ar)}</textarea></label><label>${escapeHtml(t('status', 'Status'))}<select data-class-field="status" class="field">${options.statuses.map((item) => `<option value="${item.value}" ${form.status === item.value ? 'selected' : ''}>${escapeHtml(item.label)}</option>`).join('')}</select></label></div>`;
}
function stepSchedule(form, options) { const locked = Boolean(state.wizard?.record?.is_schedule_locked); const disabled = locked ? 'disabled' : ''; return `<div class="space-y-5">${locked ? `<p class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">${escapeHtml(t('schedule_locked', 'This schedule is locked because it has customer bookings.'))}</p>` : ''}<div class="flex gap-3"><label><input type="radio" name="schedule_mode" data-class-field="schedule_mode" value="weekdays" ${form.schedule_mode === 'weekdays' ? 'checked' : ''} ${disabled}> ${escapeHtml(t('specific_days', 'Specific weekdays'))}</label><label><input type="radio" name="schedule_mode" data-class-field="schedule_mode" value="interval" ${form.schedule_mode === 'interval' ? 'checked' : ''} ${disabled}> ${escapeHtml(t('automated', 'Automated recurrence'))}</label></div>${form.schedule_mode === 'weekdays' ? `<div><p class="font-bold mb-2">${escapeHtml(t('choose_days', 'Choose days'))}</p><div class="grid grid-cols-2 md:grid-cols-4 gap-2">${options.weekdays.map((day) => `<label class="rounded-xl border p-3"><input type="checkbox" data-class-field="weekdays" value="${day.value}" ${form.weekdays.includes(day.value) ? 'checked' : ''} ${disabled}> ${escapeHtml(day.label)}</label>`).join('')}</div></div>` : `<label>${escapeHtml(t('recurrence', 'Recurrence'))}<select data-class-field="recurrence_pattern_id" class="field" ${disabled}>${options.recurrence_patterns.map((item) => `<option value="${item.id}" ${Number(form.recurrence_pattern_id) === item.id ? 'selected' : ''}>${escapeHtml(display(item.label))}</option>`).join('')}</select></label>`}</div>`; }
function stepDateTime(form) { const disabled = state.wizard?.record?.is_schedule_locked ? 'disabled' : ''; return `<div class="grid md:grid-cols-2 gap-4"><label>${escapeHtml(t('start_date', 'Start date'))}<input type="text" data-operations-picker="date" data-class-field="start_date" value="${escapeHtml(form.start_date)}" class="field" ${disabled}></label><label>${escapeHtml(t('end_date', 'End date'))}<input type="text" data-operations-picker="date" data-class-field="end_date" value="${escapeHtml(form.end_date)}" class="field" ${disabled}></label><label>${escapeHtml(t('start_time', 'Start time'))}<input type="text" data-operations-picker="time" data-class-field="start_time" value="${escapeHtml(form.start_time)}" class="field" ${disabled}></label><label>${escapeHtml(t('end_time', 'End time'))}<input type="text" data-operations-picker="time" data-class-field="end_time" value="${escapeHtml(form.end_time)}" class="field" ${disabled}></label></div>`; }
function stepCapacity(form) { return `<div class="space-y-4"><label>${escapeHtml(t('capacity', 'Capacity'))}<input min="1" max="999" type="number" data-class-field="total_spots" value="${escapeHtml(form.total_spots)}" class="field"></label><p class="text-sm text-slate-500">${escapeHtml(t('capacity_hint', 'The default capacity applies to newly generated sessions.'))}</p></div>`; }
function stepReview(form, preview, options) { const recurrence = form.schedule_mode === 'weekdays' ? form.weekdays.map((day) => options.weekdays.find((item) => item.value === day)?.label || day).join(', ') : display(options.recurrence_patterns.find((item) => Number(item.id) === Number(form.recurrence_pattern_id))?.label); return `<div class="space-y-4"><div class="rounded-xl bg-slate-50 dark:bg-slate-800 p-4"><b>${escapeHtml(form.title.en)}</b><p>${escapeHtml(recurrence)}</p><p>${escapeHtml(form.start_date)} – ${escapeHtml(form.end_date)} · ${escapeHtml(formatTime(form.start_time))} – ${escapeHtml(formatTime(form.end_time))}</p><p>${form.total_spots} ${escapeHtml(t('spots', 'spots'))}</p></div>${preview ? `<div class="rounded-xl ${preview.conflict_count ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'} p-4"><b>${preview.count} ${escapeHtml(t('sessions_generated', 'sessions will be generated'))}</b><p>${escapeHtml(preview.dates.join(', '))}${preview.has_more_dates ? '…' : ''}</p>${preview.conflict_count ? `<p>${preview.conflict_count} ${escapeHtml(t('conflicts', 'conflicts'))}: ${escapeHtml(preview.conflicts.map((item) => item.description).join('; '))}</p>` : ''}</div>` : `<p class="text-slate-500">${escapeHtml(t('preview_pending', 'The exact preview will load before saving.'))}</p>`}</div>`; }

async function nextWizard() {
    captureWizard(); const wizard = state.wizard;
    if (wizard.step < 5) return goToWizardStep(wizard.step + 1);
    if (!validateWizardStepsThrough(4)) return;
    if (wizard.submitting) return; wizard.submitting = true;
    const payload = schedulePayload(wizard.form);
    try {
        await (wizard.record ? OperationsAPI.updateClass(wizard.record.id, payload) : OperationsAPI.createClass(payload));
        if (wizard.pageMode) window.location.assign(document.getElementById('class-wizard-page')?.dataset.classDetailUrl);
        else { OperationsUI.closeModal(); OperationsUI.toast(t('saved', 'Class saved.'), 'success'); loadClasses(); }
    } catch (error) { wizard.submitting = false; showWizardError(error); }
}

function showWizardErrors(messages) { const container = document.getElementById('class-wizard-errors'); if (!container) return; container.textContent = messages.join(' '); container.classList.remove('hidden'); }
function showWizardError(error) { showWizardErrors(error.errors ? Object.values(error.errors).flat() : [error.message]); }

function openSessionForm(item, session = null) {
    const value = session || { date: localDateValue(), start_time: item.schedule.start_time, end_time: item.schedule.end_time, total_spots: item.total_spots, status: 'scheduled' };
    OperationsUI.openModal(escapeHtml(session ? t('edit_session', 'Edit session') : t('add_session', 'Add session')), `<form id="class-session-form" class="grid md:grid-cols-2 gap-4"><label>${escapeHtml(t('start_date', 'Date'))}<input name="date" data-operations-picker="date" class="field" value="${escapeHtml(value.date)}"></label><label>${escapeHtml(t('capacity', 'Capacity'))}<input name="total_spots" type="number" min="1" class="field" value="${value.total_spots}"></label><label>${escapeHtml(t('start_time', 'Start time'))}<input name="start_time" data-operations-picker="time" class="field" value="${escapeHtml(value.start_time)}"></label><label>${escapeHtml(t('end_time', 'End time'))}<input name="end_time" data-operations-picker="time" class="field" value="${escapeHtml(value.end_time)}"></label><label>${escapeHtml(t('status', 'Status'))}<select name="status" class="field"><option value="scheduled" ${value.status === 'scheduled' ? 'selected' : ''}>scheduled</option><option value="cancelled" ${value.status === 'cancelled' ? 'selected' : ''}>cancelled</option></select></label><button class="self-end px-4 py-2 rounded-xl bg-primary-600 text-white font-bold">${escapeHtml(t('save', 'Save'))}</button></form>`);
    document.getElementById('class-session-form')?.addEventListener('submit', async (event) => {
        event.preventDefault(); const payload = Object.fromEntries(new FormData(event.currentTarget));
        try { await (session ? OperationsAPI.updateClassSession(item.id, session.id, payload) : OperationsAPI.createClassSession(item.id, payload)); showClass(item.id); } catch (error) { OperationsUI.toast(error.message, 'error'); }
    });
}

window.openOperationsClassWizard = openWizard;

export async function initClassEditPage() {
    const container = document.getElementById('class-wizard-page');
    if (!container) return;

    try {
        await ensureOptions();
        const response = await OperationsAPI.getClass(container.dataset.classId);
        state.wizard = createWizardState(response.data, true);
        renderWizard();
    } catch (error) {
        container.innerHTML = `<p class="rounded-xl bg-rose-50 p-4 text-rose-700">${escapeHtml(error.message)}</p>`;
    }
}

export function initClassDetailPage() {
    document.querySelectorAll('[data-detail-class-action]').forEach((button) => button.addEventListener('click', async () => {
        const classId = button.dataset.classId;
        try {
            if (button.dataset.detailClassAction === 'delete') {
                const result = await Swal.fire({ title: t('delete_confirm', 'Delete this class?'), icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48' });
                if (!result.isConfirmed) return;
                await OperationsAPI.deleteClass(classId);
                window.location.assign('/admin/operations#classes');
                return;
            }
            await OperationsAPI.setClassStatus(classId, button.dataset.status);
            window.location.reload();
        } catch (error) { OperationsUI.toast(error.message, 'error'); }
    }));
}
