// public/js/operations/modules/approvals.js

export function initApprovalsTab() {
    const container = document.getElementById('approvals-container');
    if (!container) return;

    renderApprovalsShimmer(container);

    loadPendingExpenses(container);
}

async function loadPendingExpenses(container) {
    try {
        const result = await OperationsAPI.getPendingExpenses();
        renderApprovals(container, result.data);
    } catch (e) {
        console.error('Failed to load pending expenses:', e);
        container.innerHTML = `
            <div class="glass-card rounded-2xl p-8 text-center space-y-3">
                <p class="text-rose-500 font-bold">Failed to load pending expenses</p>
                <p class="text-sm text-slate-400">${e.message}</p>
                <button onclick="renderApprovalsShimmer(document.getElementById('approvals-container'));loadPendingExpenses(document.getElementById('approvals-container'))"
                    class="text-xs text-primary-600 underline">Retry</button>
            </div>`;
        OperationsUI.toast('Failed to load pending expenses', 'error');
    }
}

function renderApprovalsShimmer(container) {
    container.innerHTML = `
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <div class="shimmer-cell w-30" style="height:14px;border-radius:4px;"></div>
            <div class="space-y-3">
                ${Array(4).fill('').map(() => `
                    <div class="flex items-center gap-4 p-4">
                        <div class="flex-1 space-y-2">
                            <div class="shimmer-cell w-50" style="height:12px;"></div>
                            <div class="shimmer-cell w-30" style="height:10px;"></div>
                        </div>
                        <div class="shimmer-cell w-15" style="height:32px;border-radius:8px;"></div>
                        <div class="shimmer-cell w-15" style="height:32px;border-radius:8px;"></div>
                    </div>
                `).join('')}
            </div>
        </div>`;
}

function renderApprovals(container, data) {
    if (!Array.isArray(data) || data.length === 0) {
        container.innerHTML = `
            <div class="glass-card rounded-2xl p-12 text-center space-y-3">
                <svg class="w-12 h-12 mx-auto text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-lg font-bold text-slate-600 dark:text-slate-300">All Clear!</p>
                <p class="text-sm text-slate-400">All expenses have been reviewed.</p>
            </div>`;
        return;
    }

    const fmt = (amount, decimals, code) =>
        OperationsUI.formatCurrencyBlock(amount, decimals, code);

    let html = `<div class="space-y-4">`;

    data.forEach(exp => {
        const formattedAmount = fmt(exp.amount, exp.currency_decimals, exp.currency_code);
        const date = exp.expense_date || '—';

        html += `
            <div class="glass-card rounded-2xl p-5 border border-slate-200 dark:border-slate-800 hover:shadow-md transition-shadow" data-expense-id="${exp.id}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex-1 min-w-0 space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">${date}</span>
                            <span class="font-bold text-lg text-slate-900 dark:text-white">${formattedAmount}</span>
                            <span class="text-xs font-medium text-slate-400">${exp.currency_code}</span>
                        </div>
                        <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">${exp.category_name}</p>
                        ${exp.notes ? `<p class="text-xs text-slate-500 italic">${exp.notes}</p>` : ''}
                        <p class="text-xs text-slate-400">Recorded by <span class="font-medium text-slate-600 dark:text-slate-300">${exp.recorded_by_name}</span></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button onclick="window.approveExpense(${exp.id})"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98] btn-single-action">
                            Approve
                        </button>
                        <button onclick="window.showRejectModal(${exp.id})"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]">
                            Reject
                        </button>
                    </div>
                </div>
                <div id="reject-form-${exp.id}" class="hidden mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 space-y-3">
                    <textarea id="reject-reason-${exp.id}" rows="2" placeholder="Reason for rejection..."
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg border-transparent focus:ring-2 focus:ring-rose-500 outline-none text-sm"></textarea>
                    <div class="flex gap-2">
                        <button onclick="window.confirmReject(${exp.id})"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all btn-single-action">
                            Confirm Rejection
                        </button>
                        <button onclick="window.hideRejectModal(${exp.id})"
                            class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>`;
    });

    html += '</div>';
    container.innerHTML = html;
}

window.approveExpense = async function(expenseId) {
    const card = document.querySelector(`[data-expense-id="${expenseId}"]`);
    if (card) card.style.opacity = '0.5';

    try {
        await OperationsAPI.approveExpense(expenseId);
        OperationsUI.toast('Expense approved!', 'success');
        const container = document.getElementById('approvals-container');
        renderApprovalsShimmer(container);
        loadPendingExpenses(container);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
        if (card) card.style.opacity = '1';
    }
};

window.showRejectModal = function(expenseId) {
    const form = document.getElementById(`reject-form-${expenseId}`);
    if (form) form.classList.remove('hidden');
};

window.hideRejectModal = function(expenseId) {
    const form = document.getElementById(`reject-form-${expenseId}`);
    if (form) form.classList.add('hidden');
};

window.confirmReject = async function(expenseId) {
    const reason = document.getElementById(`reject-reason-${expenseId}`)?.value?.trim();
    if (!reason) {
        OperationsUI.toast('Please enter a rejection reason.', 'warning');
        return;
    }

    const card = document.querySelector(`[data-expense-id="${expenseId}"]`);
    if (card) card.style.opacity = '0.5';

    try {
        await OperationsAPI.rejectExpense(expenseId, reason);
        OperationsUI.toast('Expense rejected.', 'success');
        const container = document.getElementById('approvals-container');
        renderApprovalsShimmer(container);
        loadPendingExpenses(container);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
        if (card) card.style.opacity = '1';
    }
};
