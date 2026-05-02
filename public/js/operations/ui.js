// public/js/operations/ui.js
const OperationsUI = {

    // ── Toast ─────────────────────────────────────────────────────────────
    toast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const colors = {
            success: 'bg-emerald-500',
            error:   'bg-rose-500',
            info:    'bg-primary-600',
            warning: 'bg-amber-500',
        };
        const toast = document.createElement('div');
        toast.className = `${colors[type]} text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-3 animate-in slide-in-from-right-10 duration-300`;
        toast.innerHTML = `
            <span class="font-medium">${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto opacity-70 hover:opacity-100">&times;</button>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('animate-in');
            toast.classList.add('opacity-0', 'translate-x-full', 'transition-all', 'duration-300');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    },

    // ── Modal ─────────────────────────────────────────────────────────────
    openModal(title, content) {
        const overlay   = document.getElementById('modal-overlay');
        const container = document.getElementById('modal-container');

        container.innerHTML = `
            <div class="sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center z-10">
                <h3 class="text-xl font-bold">${title}</h3>
                <button onclick="OperationsUI.closeModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors text-xl leading-none">&times;</button>
            </div>
            <div class="p-6">${content}</div>
        `;

        overlay.classList.remove('hidden');
        requestAnimationFrame(() => {
            container.classList.remove('scale-95', 'opacity-0');
            container.classList.add('scale-100', 'opacity-100');
        });
    },

    closeModal() {
        const overlay   = document.getElementById('modal-overlay');
        const container = document.getElementById('modal-container');

        // Fix: classList.replace only accepts exactly 2 args — use remove/add instead
        container.classList.remove('scale-100', 'opacity-100');
        container.classList.add('scale-95', 'opacity-0');

        setTimeout(() => overlay.classList.add('hidden'), 300);
    },

    // ── Currency ──────────────────────────────────────────────────────────
    formatCurrency(amount) {
        const decimals = parseInt(document.body.dataset.currencyDecimals || '2');
        const code     = document.body.dataset.currencyCode || 'USD';
        return new Intl.NumberFormat('en-US', {
            style:    'currency',
            currency: code,
        }).format(amount / (10 ** decimals));
    },

    // ── Balance shimmer ───────────────────────────────────────────────────
    renderBalanceShimmer() {
        const container = document.getElementById('balance-container');
        if (!container) return;

        const card = (border) => `
            <div class="glass-card rounded-2xl p-6 border-b-4 ${border} shadow-sm space-y-3">
                <div class="shimmer-cell w-30" style="height:10px;"></div>
                <div class="shimmer-cell w-50" style="height:28px;border-radius:6px;"></div>
            </div>`;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                ${card('border-slate-200 dark:border-slate-700')}
                ${card('border-slate-200 dark:border-slate-700')}
                ${card('border-slate-200 dark:border-slate-700')}
                ${card('border-slate-200 dark:border-slate-700')}
            </div>`;
    },

    // ── Store grid shimmer ────────────────────────────────────────────────
    renderStoreShimmer() {
        const grid = document.getElementById('store-grid');
        if (!grid) return;

        const card = () => `
            <div class="glass-card rounded-2xl p-6 space-y-4 border-b-4 border-slate-200 dark:border-slate-700">
                <div class="flex justify-between items-start gap-4">
                    <div class="space-y-2 flex-1">
                        <div class="shimmer-cell w-20" style="height:10px;"></div>
                        <div class="shimmer-cell w-50" style="height:20px;border-radius:4px;"></div>
                    </div>
                    <div class="shimmer-cell w-15" style="height:20px;border-radius:4px;"></div>
                </div>
                <div class="shimmer-cell w-30" style="height:12px;"></div>
                <div class="shimmer-cell" style="width:100%;height:44px;border-radius:12px;"></div>
            </div>`;

        grid.innerHTML = Array(6).fill('').map(card).join('');
    },

    // ── Balance render ────────────────────────────────────────────────────
    renderBalance(data) {
        const container = document.getElementById('balance-container');
        if (!container) return;

        const fmt = (val) => this.formatCurrency(val);

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="glass-card rounded-2xl p-6 border-b-4 border-primary-500 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-1">Total Revenue</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">${fmt(data.total_revenue)}</p>
                </div>
                <div class="glass-card rounded-2xl p-6 border-b-4 border-rose-500 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-1">Expenses</p>
                    <p class="text-2xl font-black text-rose-500">${fmt(data.total_expenses)}</p>
                </div>
                <div class="glass-card rounded-2xl p-6 border-b-4 border-amber-500 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-1">Refunds</p>
                    <p class="text-2xl font-black text-amber-500">${fmt(data.total_refunds)}</p>
                </div>
                <div class="glass-card rounded-2xl p-6 border-b-4 border-emerald-500 shadow-sm bg-emerald-50/10">
                    <p class="text-xs font-bold text-emerald-600 uppercase mb-1">True Balance</p>
                    <p class="text-2xl font-black text-emerald-600">${fmt(data.true_balance)}</p>
                </div>
            </div>`;

        const statBalance = document.getElementById('stat-balance');
        if (statBalance) statBalance.textContent = fmt(data.true_balance);

        const progress = document.getElementById('balance-progress');
        const pct      = document.getElementById('stat-percentage');
        if (progress && pct) {
            const target     = 5000 * (10 ** parseInt(document.body.dataset.currencyDecimals || '2'));
            const percentage = Math.min(100, Math.round((data.true_balance / target) * 100));
            progress.style.width = `${percentage}%`;
            pct.textContent      = `${percentage}%`;
        }
    },
};
