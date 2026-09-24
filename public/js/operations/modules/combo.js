const COMBO_SELECTOR = '[data-combo-select]';
const PER_PAGE = 10;
const labelCache = new Map();

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[char]));

const tr = (key, fallback) => {
    const value = window.__(`operations_ui.classes.${key}`);
    return value === `operations_ui.classes.${key}` ? fallback : value;
};

function cacheKey(type, id) {
    return `${type}|${id}`;
}

function config(root) {
    return {
        type: root.dataset.comboType,
        search: root.querySelector('[data-combo-search]'),
        hidden: root.querySelector('[data-class-field]'),
        results: root.querySelector('[data-combo-results]'),
        clear: root.querySelector('[data-combo-clear]'),
    };
}

function initCombo(root) {
    const { type, search, hidden, results, clear } = config(root);
    if (!type || !search || !hidden || !results || !clear) return;

    const state = {
        query: '', page: 1, hasMore: true, loading: false, requestId: 0,
        timer: null, blurTimer: null, highlighted: -1,
    };

    const items = () => [...results.querySelectorAll('[data-combo-item]')];

    function setExpanded(expanded) {
        search.setAttribute('aria-expanded', String(expanded));
    }

    function lock(label) {
        search.value = label || '';
        search.readOnly = true;
        clear.classList.remove('hidden');
        results.classList.add('hidden');
        setExpanded(false);
    }

    function showResults() {
        results.classList.remove('hidden');
        setExpanded(true);
    }

    function hideResults() {
        results.classList.add('hidden');
        setExpanded(false);
        state.highlighted = -1;
    }

    function highlight(index) {
        state.highlighted = index;
        items().forEach((element, i) => {
            element.classList.toggle('bg-primary-50 dark:bg-primary-900/20', i === index);
            element.setAttribute('aria-selected', i === index ? 'true' : 'false');
        });
    }

    async function fetchPage(reset) {
        if (state.loading) return;
        state.loading = true;
        const requestId = ++state.requestId;
        if (reset) {
            state.page = 1;
            state.hasMore = true;
            state.highlighted = -1;
            results.innerHTML = '';
        }
        try {
            const response = await OperationsAPI.searchClassOptions(type, {
                search: state.query,
                page: state.page,
                perPage: PER_PAGE,
            });
            if (requestId !== state.requestId) return;
            const items = response.data ?? [];
            const meta = response.meta ?? {};
            state.hasMore = meta.current_page < meta.last_page;

            if (reset) results.innerHTML = '';
            if (!items.length) {
                results.innerHTML = `<p class="px-4 py-3 text-xs text-slate-400 italic">${escapeHtml(tr('no_matches', 'No matches found.'))}</p>`;
                state.highlighted = -1;
                return;
            }
            results.insertAdjacentHTML('beforeend', items.map((item) => `
                <button type="button" data-combo-item="${item.id}" role="option"
                    class="w-full text-left px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                    ${escapeHtml(item.display_name)}
                </button>`).join(''));
            results.querySelectorAll('[data-combo-item]').forEach((button) => {
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    select(button.dataset.comboItem);
                });
            });
        } catch (error) {
            if (requestId !== state.requestId) return;
            if (reset) results.innerHTML = `<p class="px-4 py-3 text-xs text-rose-500">${escapeHtml(error.message)}</p>`;
        } finally {
            if (requestId === state.requestId) state.loading = false;
        }
    }

    function select(idValue) {
        const button = results.querySelector(`[data-combo-item="${idValue}"]`);
        const label = button ? button.textContent.trim() : idValue;
        hidden.value = idValue;
        labelCache.set(cacheKey(type, idValue), label);
        lock(label);
    }

    const cachedLabel = hidden.value ? labelCache.get(cacheKey(type, hidden.value)) : null;
    search.setAttribute('role', 'combobox');
    setExpanded(false);
    if (cachedLabel || root.dataset.comboLabel) {
        lock(root.dataset.comboLabel || cachedLabel);
    }

    search.addEventListener('focus', () => {
        clearTimeout(state.blurTimer);
        if (search.readOnly) return;
        showResults();
        fetchPage(true);
    });

    search.addEventListener('input', () => {
        clearTimeout(state.timer);
        state.timer = setTimeout(() => {
            const query = search.value.trim();
            if (query === state.query) return;
            state.query = query;
            showResults();
            fetchPage(true);
        }, 300);
    });

    search.addEventListener('blur', () => {
        state.blurTimer = setTimeout(hideResults, 150);
    });

    search.addEventListener('keydown', (event) => {
        if (search.readOnly) return;
        const list = items();

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!list.length) return;
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            const next = state.highlighted + direction;
            highlight(Math.min(Math.max(next, 0), list.length - 1));
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            const selected = list[state.highlighted];
            if (selected) select(selected.dataset.comboItem);
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            hideResults();
            search.blur();
        }
    });

    results.addEventListener('mousedown', (event) => event.preventDefault());
    results.addEventListener('mouseup', () => clearTimeout(state.blurTimer));

    results.addEventListener('scroll', () => {
        if (state.loading || !state.hasMore) return;
        const { scrollTop, scrollHeight, clientHeight } = results;
        if (scrollTop + clientHeight >= scrollHeight - 40) {
            state.page += 1;
            fetchPage(false);
        }
    });

    clear.addEventListener('click', () => {
        const key = cacheKey(type, hidden.value);
        hidden.value = '';
        labelCache.delete(key);
        search.readOnly = false;
        search.value = '';
        clear.classList.add('hidden');
        state.query = '';
        state.page = 1;
        state.hasMore = true;
        state.highlighted = -1;
        search.focus();
        showResults();
        fetchPage(true);
    });
}

export function initializeComboSelects(root = document) {
    if (root instanceof Element && root.matches(COMBO_SELECTOR)) initCombo(root);
    if (typeof root.querySelectorAll === 'function') {
        root.querySelectorAll(COMBO_SELECTOR).forEach(initCombo);
    }
}

let observer = null;

export function observeComboSelects() {
    initializeComboSelects(document);

    if (observer !== null) return;

    observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) initializeComboSelects(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}