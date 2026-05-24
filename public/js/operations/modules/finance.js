// /home/lenovo/work/projects/the_pilates_studio_sy/public/js/operations/modules/finance.js
let allCategories = [];

function initCategoryDropdown() {
    const input = document.getElementById('category-input');
    const dropdown = document.getElementById('category-dropdown');
    if (!input || !dropdown) return;

    async function fetchCategories() {
        try {
            const result = await OperationsAPI.request('/admin/operations/finance/categories');
            allCategories = Array.isArray(result.data) ? result.data : [];
        } catch (e) {
            console.error('Failed to load expense categories', e);
        }
    }

    function hideDropdown() {
        dropdown.classList.add('hidden');
    }

    function showDropdown(filter = '') {
        const term = filter.trim().toLowerCase();
        const matches = term.length === 0
            ? allCategories
            : allCategories.filter((category) => category.toLowerCase().includes(term));

        dropdown.innerHTML = '';
        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-4 py-2 text-xs text-slate-400 italic';
            empty.textContent = 'No matches – press Enter to create';
            dropdown.appendChild(empty);
        } else {
            matches.forEach((category) => {
                const item = document.createElement('div');
                item.className = 'px-4 py-2 text-sm cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-900/20 text-slate-700 dark:text-slate-200';
                item.textContent = category;
                item.addEventListener('click', () => selectCategory(category));
                dropdown.appendChild(item);
            });
        }

        dropdown.classList.remove('hidden');
    }

    window.selectCategory = function (categoryName) {
        input.value = categoryName;
        hideDropdown();
    };

    input.addEventListener('input', () => {
        showDropdown(input.value);
    });

    input.addEventListener('focus', () => {
        showDropdown(input.value);
    });

    let blurTimeout;
    input.addEventListener('blur', () => {
        blurTimeout = window.setTimeout(hideDropdown, 150);
    });

    dropdown.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });

    dropdown.addEventListener('mouseup', () => {
        if (blurTimeout) {
            window.clearTimeout(blurTimeout);
        }
    });

    fetchCategories();
}

export async function renderBalance(date = '') {
    OperationsUI.renderBalanceShimmer();

    const checkboxes = document.querySelectorAll('.currency-filter-cb:checked');
    const selectedCurrencies = Array.from(checkboxes).map((cb) => cb.value);
    const convertToBase = document.getElementById('convert-to-base')?.checked ?? false;

    try {
        const result = await OperationsAPI.getDailyBalance(date, selectedCurrencies, convertToBase);
        OperationsUI.renderBalance(result.data);
    } catch (e) {
        console.error('Failed to load balance:', e);
        const container = document.getElementById('balance-container');
        if (container) {
            container.innerHTML = `
                <div class="glass-card rounded-2xl p-8 text-center space-y-3">
                    <p class="text-rose-500 font-bold">Failed to load financial data</p>
                    <p class="text-sm text-slate-400">${e.message}</p>
                    <button onclick="window.renderBalance(document.getElementById('balance-date').value)"
                        class="text-xs text-primary-600 underline">Try again</button>
                </div>`;
        }
        OperationsUI.toast('Failed to load balance', 'error');
    }
}

export function applyCurrencyFilter() {
    const dateInput = document.getElementById('balance-date');
    if (dateInput) {
        renderBalance(dateInput.value);
    }
}

export function initFinanceTab() {
    const dateInput = document.getElementById('balance-date');
    if (!dateInput) return;

    dateInput.addEventListener('change', (e) => {
        renderBalance(e.target.value);
        if (window.updateGlobalStats) window.updateGlobalStats(e.target.value);
    });

    renderBalance(dateInput.value);
    initCategoryDropdown();

    document.getElementById('expense-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const btn = e.target.querySelector('button[type="submit"]');

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving…';
        }

        try {
            await OperationsAPI.recordExpense(Object.fromEntries(formData));
            OperationsUI.toast('Expense recorded!', 'success');
            e.target.reset();
            renderBalance(dateInput.value);
            if (window.updateGlobalStats) window.updateGlobalStats(dateInput.value);
        } catch (err) {
            OperationsUI.toast(err.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save Expense';
            }
        }
    });
}

window.renderBalance = renderBalance;
window.OperationsFinance = window.OperationsFinance || {};
window.OperationsFinance.applyCurrencyFilter = applyCurrencyFilter;
window.OperationsFinance.renderBalance = renderBalance;
