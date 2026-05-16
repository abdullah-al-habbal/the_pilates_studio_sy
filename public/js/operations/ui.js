// public/js/operations/ui.js
const OperationsUI = {
    toast(message, type = "info") {
        const container = document.getElementById("toast-container");
        const colors = {
            success: "bg-emerald-500",
            error: "bg-rose-500",
            info: "bg-primary-600",
            warning: "bg-amber-500",
        };
        const toast = document.createElement("div");
        toast.className = `${colors[type]} text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-3 animate-in slide-in-from-right-10 duration-300`;
        toast.innerHTML = `<span class="font-medium">${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto opacity-70 hover:opacity-100">&times;</button>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove("animate-in");
            toast.classList.add(
                "opacity-0",
                "translate-x-full",
                "transition-all",
                "duration-300",
            );
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    },

    openModal(title, content) {
        const overlay = document.getElementById("modal-overlay");
        const container = document.getElementById("modal-container");
        container.innerHTML = `
            <div class="sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center z-10">
                <h3 class="text-xl font-bold">${title}</h3>
                <button onclick="OperationsUI.closeModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors text-xl leading-none">&times;</button>
            </div>
            <div class="p-6">${content}</div>`;
        overlay.classList.remove("hidden");
        requestAnimationFrame(() => {
            container.classList.remove("scale-95", "opacity-0");
            container.classList.add("scale-100", "opacity-100");
        });
    },

    closeModal() {
        const overlay = document.getElementById("modal-overlay");
        const container = document.getElementById("modal-container");
        container.classList.remove("scale-100", "opacity-100");
        container.classList.add("scale-95", "opacity-0");
        setTimeout(() => overlay.classList.add("hidden"), 300);
    },

    formatCurrencyBlock(amount, decimals, code) {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: code,
            minimumFractionDigits: decimals,
        }).format(amount / 10 ** decimals);
    },

    getCurrencyById(currencyId) {
        return (
            (window.OperationsCurrencies || []).find(
                (currency) => currency.id === Number(currencyId),
            ) || null
        );
    },

    formatCurrency(amount, currencyId = null) {
        let decimals;
        let code;

        if (currencyId != null) {
            const currency = this.getCurrencyById(currencyId);
            if (currency) {
                decimals = Number(currency.decimal_places || 2);
                code = currency.code || "USD";
            }
        }

        if (decimals === undefined || code === undefined) {
            decimals = parseInt(document.body.dataset.currencyDecimals || "2");
            code = document.body.dataset.currencyCode || "USD";
        }

        return this.formatCurrencyBlock(amount, decimals, code);
    },

    computeAmountFromBase(baseAmount, targetCurrencyId) {
        const currency = this.getCurrencyById(targetCurrencyId);
        if (!currency || typeof baseAmount !== "number" || baseAmount <= 0) {
            return 0;
        }

        const baseCurrency = (window.OperationsCurrencies || []).find(
            (c) => Number(c.exchange_rate) === 1,
        );
        if (!baseCurrency) {
            return 0;
        }

        const baseDivisor = 10 ** Number(baseCurrency.decimal_places || 2);
        const targetDivisor = 10 ** Number(currency.decimal_places || 2);
        const exchangeRate = Number(currency.exchange_rate || 0);

        if (exchangeRate <= 0) {
            return 0;
        }

        const baseUnits = baseAmount / baseDivisor;
        const convertedInTargetUnits = baseUnits * exchangeRate;
        return Math.round(convertedInTargetUnits * targetDivisor);
    },

    renderBalanceShimmer() {
        const container = document.getElementById("balance-container");
        if (!container) return;
        const shimmerBlock = () => `
            <div class="glass-card rounded-2xl p-6 space-y-4 border border-slate-200 dark:border-slate-800">
                <div class="shimmer-cell w-20" style="height:10px;border-radius:4px;"></div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    ${Array(4)
                        .fill("")
                        .map(
                            () => `
                        <div class="space-y-2">
                            <div class="shimmer-cell w-30" style="height:10px;"></div>
                            <div class="shimmer-cell w-50" style="height:24px;border-radius:6px;"></div>
                        </div>`,
                        )
                        .join("")}
                </div>
            </div>`;
        container.innerHTML = shimmerBlock() + shimmerBlock();
    },

    renderStoreShimmer() {
        const grid = document.getElementById("store-grid");
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
        grid.innerHTML = Array(6).fill("").map(card).join("");
    },

    renderBalance(data) {
        const container = document.getElementById("balance-container");
        if (!container) return;

        if (!Array.isArray(data) || data.length === 0) {
            container.innerHTML = `
                <div class="glass-card rounded-2xl p-8 text-center text-slate-400">
                    No financial data for this period.
                </div>`;
            return;
        }

        const fmt = (amount, decimals, code) =>
            this.formatCurrencyBlock(amount, decimals, code);

        container.innerHTML = data
            .map(
                (c) => `
            <div class="glass-card rounded-2xl p-6 space-y-4 mb-4 border border-slate-200 dark:border-slate-800">
                <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                        class="flex items-center gap-2 w-full text-left">
                    <span class="text-xs font-black uppercase tracking-widest text-primary-500 bg-primary-50 dark:bg-primary-900/30 px-3 py-1 rounded-full">
                        ${c.currency_code}
                    </span>
                    <span class="text-xs text-slate-400">${c.currency_symbol}</span>
                    <svg class="w-4 h-4 ml-auto transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4">
                    <div class="glass-card rounded-xl p-4 border-b-2 border-primary-400">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Total Revenue</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white truncate">
                            ${fmt(c.total_revenue, c.currency_decimals, c.currency_code)}
                        </p>
                    </div>
                    <div class="glass-card rounded-xl p-4 border-b-2 border-sky-400">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Packages</p>
                        <p class="text-lg font-black text-sky-600 truncate">
                            ${fmt(c.package_revenue, c.currency_decimals, c.currency_code)}
                        </p>
                    </div>
                    <div class="glass-card rounded-xl p-4 border-b-2 border-gold-400">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Merchandise</p>
                        <p class="text-lg font-black text-gold-600 truncate">
                            ${fmt(c.merchandise_revenue, c.currency_decimals, c.currency_code)}
                        </p>
                    </div>
                    <div class="glass-card rounded-xl p-4 border-b-2 border-rose-400">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Expenses</p>
                        <p class="text-lg font-black text-rose-500 truncate">
                            ${fmt(c.total_expenses, c.currency_decimals, c.currency_code)}
                        </p>
                    </div>
                    <div class="glass-card rounded-xl p-4 border-b-2 border-amber-400">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Refunds</p>
                        <p class="text-lg font-black text-amber-500 truncate">
                            ${fmt(c.total_refunds, c.currency_decimals, c.currency_code)}
                        </p>
                    </div>
                    <div class="glass-card rounded-xl p-4 border-b-2 border-emerald-400 bg-emerald-50/10">
                        <p class="text-[10px] font-bold text-emerald-600 uppercase mb-1">True Balance</p>
                        <p class="text-lg font-black ${c.true_balance >= 0 ? "text-emerald-600" : "text-rose-600"} truncate">
                            ${fmt(c.true_balance, c.currency_decimals, c.currency_code)}
                        </p>
                    </div>
                </div>
            </div>`,
            )
            .join("");
    },

    renderDailySnapshot(data) {
        const container = document.getElementById("quick-stats-currency-list");
        if (!container) return;

        if (!Array.isArray(data) || data.length === 0) {
            container.innerHTML =
                '<p class="text-sm text-slate-500">No data for today.</p>';
            return;
        }

        const fmt = (amount, decimals, code) =>
            this.formatCurrencyBlock(amount, decimals, code);

        container.innerHTML = data
            .map(
                (c) => `
            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    ${c.currency_code} ${c.currency_symbol}
                </span>
                <span class="text-sm font-bold ${c.true_balance >= 0 ? "text-emerald-600" : "text-rose-600"}">
                    ${fmt(c.true_balance, c.currency_decimals, c.currency_code)}
                </span>
            </div>
        `,
            )
            .join("");
    },
};
