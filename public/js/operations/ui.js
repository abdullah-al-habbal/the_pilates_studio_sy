/**
 * UI Rendering and Modal Logic
 */
const OperationsUI = {
    toast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const colors = {
            success: 'bg-emerald-500',
            error: 'bg-rose-500',
            info: 'bg-primary-600',
            warning: 'bg-amber-500'
        };

        const toast = document.createElement('div');
        toast.className = `${colors[type]} text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-3 animate-in slide-in-from-right-10 duration-300`;
        toast.innerHTML = `
            <span class="font-medium">${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto opacity-70 hover:opacity-100">&times;</button>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.replace('animate-in', 'animate-out');
            toast.classList.add('fade-out', 'translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    },

    openModal(title, content) {
        const overlay = document.getElementById('modal-overlay');
        const container = document.getElementById('modal-container');
        
        container.innerHTML = `
            <div class="sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center z-10">
                <h3 class="text-xl font-bold">${title}</h3>
                <button onclick="OperationsUI.closeModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">&times;</button>
            </div>
            <div class="p-6">
                ${content}
            </div>
        `;

        overlay.classList.remove('hidden');
        setTimeout(() => {
            container.classList.remove('scale-95', 'opacity-0');
            container.classList.add('scale-100', 'opacity-100');
        }, 10);
    },

    closeModal() {
        const overlay = document.getElementById('modal-overlay');
        const container = document.getElementById('modal-container');
        
        container.classList.replace('scale-100', 'opacity-100', 'scale-95');
        container.classList.add('opacity-0');
        
        setTimeout(() => {
            overlay.classList.add('hidden');
        }, 300);
    },

    renderBalance(data) {
        const container = document.getElementById('balance-container');
        if (!container) return;

        const format = (val) => new Intl.NumberFormat().format(val) + ' SYP';

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="glass-card rounded-2xl p-6 border-b-4 border-primary-500 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-1">Total Revenue</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">${format(data.total_revenue)}</p>
                </div>
                <div class="glass-card rounded-2xl p-6 border-b-4 border-rose-500 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-1">Expenses</p>
                    <p class="text-2xl font-black text-rose-500">${format(data.total_expenses)}</p>
                </div>
                <div class="glass-card rounded-2xl p-6 border-b-4 border-amber-500 shadow-sm">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-1">Refunds</p>
                    <p class="text-2xl font-black text-amber-500">${format(data.total_refunds)}</p>
                </div>
                <div class="glass-card rounded-2xl p-6 border-b-4 border-emerald-500 shadow-sm bg-emerald-50/10">
                    <p class="text-xs font-bold text-emerald-600 uppercase mb-1">True Balance</p>
                    <p class="text-2xl font-black text-emerald-600">${format(data.true_balance)}</p>
                </div>
            </div>
        `;

        // Update Quick Stats
        document.getElementById('stat-balance').textContent = format(data.true_balance);
        const percentage = Math.min(100, Math.round((data.true_balance / 5000) * 100));
        document.getElementById('balance-progress').style.width = `${percentage}%`;
        document.getElementById('stat-percentage').textContent = `${percentage}%`;
    }
};
