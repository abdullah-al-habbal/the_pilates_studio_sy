// /home/lenovo/work/projects/the_pilates_studio_sy/public/js/operations/modules/finance.js
let allCategories = [];

function initCategoryDropdown() {
    const input = document.getElementById('category-input');
    const dropdown = document.getElementById('category-dropdown');
    if (!input || !dropdown) return;
    const parent = input.parentNode;
    const wrapper = document.createElement('div');
    wrapper.className = 'relative flex-1';
    parent.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.innerHTML = '&times;';
    clearBtn.className = 'hidden absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 text-lg leading-none';
    clearBtn.addEventListener('click', () => {
        input.value = '';
        input.readOnly = false;
        clearBtn.classList.add('hidden');
        input.focus();
    });
    wrapper.appendChild(clearBtn);

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
        input.readOnly = true;
        clearBtn.classList.remove('hidden');
        hideDropdown();
    };

    input.addEventListener('input', () => {
        if (input.readOnly) return;
        showDropdown(input.value);
    });

    input.addEventListener('focus', () => {
        if (input.readOnly) return;
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
    // render expense breakdown for the selected date
    renderExpenseBreakdown(dateInput.value);

    document.getElementById('expense-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const btn = e.target.querySelector('button[type="submit"]');

        const currencyId = parseInt(formData.get('currency_id'), 10) || null;
        const amount = parseInt(formData.get('amount'), 10) || 0;

        const payload = {
            category_name: formData.get('category_name'),
            currency_id: currencyId,
            amount: amount,
            notes: formData.get('notes'),
            date: formData.get('date'),
        };

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving…';
        }

        try {
            await OperationsAPI.recordExpense(payload);
            OperationsUI.toast('Expense recorded!', 'success');
            e.target.reset();
            // re-enable category input and hide clear button if present
            const catInput = document.getElementById('category-input');
            if (catInput) {
                catInput.readOnly = false;
                const clearBtn = catInput.parentNode.querySelector('button');
                if (clearBtn) clearBtn.classList.add('hidden');
            }
            renderBalance(dateInput.value);
            renderExpenseBreakdown(dateInput.value);
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

// ─── Expense breakdown ───────────────────────────────────────────────────────
async function renderExpenseBreakdown(date = '') {
    const container = document.getElementById('expense-breakdown');
    if (!container) return;
    container.innerHTML = '<p class="text-sm text-slate-400">Loading breakdown...</p>';
    try {
        const result = await OperationsAPI.request(`/admin/operations/finance/expenses/breakdown?date=${encodeURIComponent(date)}`);
        const expenses = result.data ?? [];
        if (!expenses.length) {
            container.innerHTML = '<p class="text-sm text-slate-400 italic text-center py-8">No expenses for this date.</p>';
            return;
        }
        const maxAmount = Math.max(...expenses.map(e => e.total_amount));
        const currency = window.OperationsCurrencies?.[0];
        const code = currency?.code ?? 'USD';
        const fmt = (amount) => new Intl.NumberFormat('en-US', { style: 'currency', currency: code, minimumFractionDigits: 0 }).format(amount);

        let html = '<ul class="space-y-3">';
        expenses.forEach(e => {
            const percent = maxAmount > 0 ? (e.total_amount / maxAmount) * 100 : 0;
            html += `
                <li class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">${e.category_name}</p>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mt-1">
                            <div class="bg-primary-500 h-2 rounded-full" style="width: ${percent}%"></div>
                        </div>
                    </div>
                    <span class="ml-3 text-sm font-bold text-slate-800 dark:text-white">${fmt(e.total_amount)}</span>
                </li>`;
        });
        html += '</ul>';
        container.innerHTML = html;
    } catch (e) {
        console.error('Failed to load expense breakdown', e);
        container.innerHTML = `<p class="text-sm text-rose-500">Failed to load breakdown. <button onclick="renderExpenseBreakdown('${date}')" class="underline">Retry</button></p>`;
    }
}

window.renderBalance = renderBalance;
window.OperationsFinance = window.OperationsFinance || {};
window.OperationsFinance.applyCurrencyFilter = applyCurrencyFilter;
window.OperationsFinance.renderBalance = renderBalance;
