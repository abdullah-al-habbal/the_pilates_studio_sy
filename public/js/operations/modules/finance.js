export function initFinanceTab() {
    const dateInput = document.getElementById('balance-date');
    if (!dateInput) return;

    dateInput.addEventListener('change', (e) => renderBalance(e.target.value));
    renderBalance(dateInput.value);

    document.getElementById('expense-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const btn = e.target.querySelector('button[type="submit"]');

        try {
            await OperationsAPI.recordExpense(Object.fromEntries(formData));
            OperationsUI.toast('Expense recorded!', 'success');
            e.target.reset();
            renderBalance(dateInput.value);
        } catch (err) {
            OperationsUI.toast(err.message, 'error');
        } finally {
            if (btn) {
                btn.disabled    = false;
                btn.textContent = 'Save Expense';
            }
        }
    });
}

export async function renderBalance(date = '') {
    OperationsUI.renderBalanceShimmer();

    const checkboxes = document.querySelectorAll('.currency-filter-cb:checked');
    const selectedCurrencies = Array.from(checkboxes).map(cb => cb.value);

    try {
        const result = await OperationsAPI.getDailyBalance(date, selectedCurrencies);
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

window.renderBalance = renderBalance;
window.OperationsFinance = window.OperationsFinance || {};
window.OperationsFinance.applyCurrencyFilter = applyCurrencyFilter;
window.OperationsFinance.renderBalance = renderBalance;
