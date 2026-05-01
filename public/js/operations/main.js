document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initTheme();
    loadTab('clients');
    updateGlobalStats();
});

function initTheme() {
    const toggle = document.getElementById('theme-toggle');
    if (localStorage.getItem('operations-theme') === 'dark' || 
        (!localStorage.getItem('operations-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }

    toggle.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('operations-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    });
}

function initTabs() {
    const buttons = document.querySelectorAll('[data-tab]');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            loadTab(tab);

            buttons.forEach(b => {
                b.classList.remove('bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20', 'active-tab');
                b.classList.add('hover:bg-slate-100', 'dark:hover:bg-slate-800');
            });
            btn.classList.add('bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20', 'active-tab');
            btn.classList.remove('hover:bg-slate-100', 'dark:hover:bg-slate-800');
        });
    });
}

async function loadTab(tab) {
    const container = document.getElementById('tab-content-container');
    const template = document.getElementById(`tpl-${tab}`);
    
    if (!template) return;

    container.innerHTML = template.innerHTML;

    if (tab === 'clients') initClientsTab();
    if (tab === 'store') initStoreTab();
    if (tab === 'finance') initFinanceTab();
}

async function updateGlobalStats() {
    try {
        const result = await OperationsAPI.getDailyBalance();
        OperationsUI.renderBalance(result.data);
    } catch (e) {
        console.error('Failed to load global stats');
    }
}

let clientSearchTimeout = null;

async function initClientsTab() {
    const searchInput = document.getElementById('client-search');
    searchInput.addEventListener('input', (e) => {
        clearTimeout(clientSearchTimeout);
        clientSearchTimeout = setTimeout(() => {
            renderClients(e.target.value);
        }, 300);
    });

    renderClients();
}

async function renderClients(search = '', page = 1) {
    const tbody = document.getElementById('client-table-body');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12 text-slate-400">Searching...</td></tr>';

    try {
        const result = await OperationsAPI.getClients(search, page);
        
        if (result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12 text-slate-400">No clients found.</td></tr>';
            return;
        }

        tbody.innerHTML = result.data.map(user => `
            <tr class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                <td class="px-6 py-4 font-medium">${user.fullname}</td>
                <td class="px-6 py-4 text-slate-500">${user.phone_number}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded-full text-xs font-bold ${user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                        ${user.is_active ? 'ACTIVE' : 'INACTIVE'}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <button onclick="showClientDetails(${user.id})" class="text-primary-600 hover:text-primary-700 font-bold text-sm">Details</button>
                </td>
            </tr>
        `).join('');

        renderPagination(result.meta);
    } catch (e) {
        console.error('Failed to load clients:', e);
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12"><div class="flex flex-col items-center gap-2"><span class="text-rose-500 font-bold">Error loading clients</span><button onclick="renderClients()" class="text-xs text-primary-600 underline">Try again</button></div></td></tr>';
        OperationsUI.toast('Failed to load clients', 'error');
    }
}

function renderPagination(meta) {
    const container = document.getElementById('client-pagination');
    if (!meta || !meta.pagination) return;

    const p = meta.pagination;
    container.innerHTML = `
        <span class="text-xs text-slate-500 font-medium">Page ${p.current_page} of ${p.total_pages} (${p.total} clients)</span>
        <div class="flex gap-1">
            <button onclick="renderClients('', ${p.current_page - 1})" ${p.current_page === 1 ? 'disabled' : ''} class="px-3 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg disabled:opacity-50">&larr;</button>
            <button onclick="renderClients('', ${p.current_page + 1})" ${p.current_page === p.total_pages ? 'disabled' : ''} class="px-3 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg disabled:opacity-50">&rarr;</button>
        </div>
    `;
}

async function showClientDetails(userId) {
    try {
        const result = await OperationsAPI.getClientDetails(userId);
        const user = result.data;

        const content = `
            <div class="space-y-8">
                <div class="flex items-start justify-between">
                    <div class="flex gap-4 items-center">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-2xl font-bold text-slate-400">
                            ${user.fullname.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-2xl font-bold">${user.fullname}</h4>
                            <p class="text-slate-500">${user.phone_number} &bull; Member since ${user.member_since}</p>
                        </div>
                    </div>
                    <button onclick="showPackageAssignment(${user.id})" class="bg-primary-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:scale-105 transition-all">+ Assign Package</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="glass-card rounded-2xl p-6 border-l-4 border-gold-500">
                        <h5 class="text-xs font-bold text-slate-400 uppercase mb-4">Current Package</h5>
                        ${user.active_package ? `
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-xl font-bold">${user.active_package.name}</p>
                                        <p class="text-sm text-slate-500">Source: ${user.active_package.source_type}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-primary-600">${user.active_package.remaining_credits} / ${user.active_package.total_credits}</p>
                                        <p class="text-xs font-bold text-slate-400">CREDITS</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-2">
                                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Expires: ${user.active_package.expires_at || 'Never'}</span>
                                    ${user.active_package.remaining_days !== null ? `
                                        <span class="px-2 py-0.5 rounded-lg bg-gold-100 text-gold-700 text-xs font-bold">${user.active_package.remaining_days} days left</span>
                                    ` : ''}
                                </div>
                                <div class="flex gap-2 pt-2">
                                    ${user.active_package.status === 'frozen' ? `
                                        <button onclick="handleUnfreeze(${user.active_package.id}, ${user.id})" class="flex-1 bg-emerald-100 text-emerald-700 py-2 rounded-lg font-bold text-xs uppercase tracking-wider">Unfreeze Now</button>
                                    ` : `
                                        <button onclick="handleFreeze(${user.active_package.id}, ${user.id})" class="flex-1 bg-amber-100 text-amber-700 py-2 rounded-lg font-bold text-xs uppercase tracking-wider">Freeze Package</button>
                                    `}
                                </div>
                            </div>
                        ` : `
                            <div class="flex flex-col items-center py-6 text-center">
                                <p class="text-slate-400 font-medium">No active package found.</p>
                                <p class="text-xs text-slate-400 mt-1">Client needs a new subscription.</p>
                            </div>
                        `}
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <h5 class="text-xs font-bold text-slate-400 uppercase mb-4">Activity Snapshot</h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                <p class="text-2xl font-bold">${user.activity_snapshot.total_sessions_attended}</p>
                                <p class="text-xs text-slate-500 font-medium">Attended</p>
                            </div>
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                <p class="text-2xl font-bold text-rose-500">${user.activity_snapshot.total_sessions_cancelled}</p>
                                <p class="text-xs text-slate-500 font-medium">Cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h5 class="text-xs font-bold text-slate-400 uppercase">Recent Store Purchases</h5>
                    <div class="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden">
                        ${user.store_purchases.length > 0 ? `
                            <table class="w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    ${user.store_purchases.slice(0, 5).map(o => `
                                        <tr>
                                            <td class="px-4 py-3">${o.item_name}</td>
                                            <td class="px-4 py-3 text-slate-500">${o.quantity} unit(s)</td>
                                            <td class="px-4 py-3 font-bold">${OperationsUI.formatCurrency(o.total_price)}</td>
                                            <td class="px-4 py-3 text-right text-xs text-slate-400">${o.ordered_at}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : `
                            <p class="p-6 text-center text-slate-400 italic">No purchase history.</p>
                        `}
                    </div>
                </div>
            </div>
        `;

        OperationsUI.openModal('Client Workspace', content);
    } catch (e) {
        console.error('Failed to load client details:', e);
        OperationsUI.toast('Failed to load client details', 'error');
    }
}

async function showPackageAssignment(userId) {
    try {
        const result = await OperationsAPI.getPackages();
        const packages = result.data;

        const content = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${packages.map(p => `
                    <button onclick="handlePackageAssign(${userId}, ${p.id})" class="flex flex-col p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-800 hover:border-primary-500 transition-all text-left group">
                        <span class="text-lg font-bold group-hover:text-primary-600 transition-colors">${p.name}</span>
                        <span class="text-sm text-slate-500">${p.total_credits} Sessions &bull; ${p.validity_days} Days</span>
                                                                    // fix: use the current price approach

                        <span class="mt-4 text-2xl font-black text-slate-900 dark:text-white">${OperationsUI.formatCurrency(p.price)}</span>
                    </button>
                `).join('')}
            </div>
        `;

        OperationsUI.openModal('Assign New Package', content);
    } catch (e) {
        console.error('Failed to load packages:', e);
        OperationsUI.toast('Failed to load packages', 'error');
    }
}

async function handlePackageAssign(userId, packageId) {
    try {
        await OperationsAPI.assignPackage(userId, packageId);
        OperationsUI.toast('Package assigned successfully!', 'success');
        OperationsUI.closeModal();
        renderClients(); // Refresh list
    } catch (e) {
        console.error('Failed to assign package:', e);
        OperationsUI.toast(e.message, 'error');
    }
}

async function handleFreeze(bookingId, userId) {
    if (!confirm('Are you sure you want to freeze this package? It will pause validity calculations.')) return;
    try {
        await OperationsAPI.freezeBooking(bookingId);
        OperationsUI.toast('Package frozen.', 'success');
        showClientDetails(userId); // Refresh modal
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

async function handleUnfreeze(bookingId, userId) {
    if (!confirm('Unfreeze package? This will generate a new replacement package for the remaining time.')) return;
    try {
        await OperationsAPI.unfreezeBooking(bookingId);
        OperationsUI.toast('Package unfrozen and resumed.', 'success');
        showClientDetails(userId); // Refresh modal
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

// --- Tab: Store ---
async function initStoreTab() {
    renderStore();
}

async function renderStore() {
    const grid = document.getElementById('store-grid');
    grid.innerHTML = '<div class="col-span-full py-12 text-center text-slate-400 animate-pulse">Loading merchandise...</div>';

    try {
        const result = await OperationsAPI.getStoreItems();
        
        grid.innerHTML = result.data.map(item => `
            <div class="glass-card rounded-2xl p-6 space-y-4 hover:shadow-xl transition-all border-b-4 ${item.stock_quantity > 5 ? 'border-primary-500' : 'border-amber-500'}">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-primary-500">${item.category || 'Product'}</span>
                        <h4 class="text-xl font-bold">${item.name}</h4>
                    </div>
                    <div class="text-right">
                    //                                             // fix: use the current price approach

                        <span class="text-xl font-black">${OperationsUI.formatCurrency(item.price)}</span>

                    </div>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">In Stock:</span>
                    <span class="font-bold ${item.stock_quantity <= 5 ? 'text-amber-500' : ''}">${item.stock_quantity}</span>
                </div>

                <button onclick="showQuickSale(${item.id}, '${item.name.replace(/'/g, "\\'")}')" class="w-full bg-slate-900 dark:bg-white dark:text-slate-900 text-white py-3 rounded-xl font-bold text-sm hover:scale-[1.02] transition-all" ${item.stock_quantity <= 0 ? 'disabled' : ''}>
                    ${item.stock_quantity <= 0 ? 'Out of Stock' : 'Quick Sale'}
                </button>
            </div>
        `).join('');
    } catch (e) {
        console.error('Failed to load store:', e);
        grid.innerHTML = '<div class="col-span-full py-12 text-center text-rose-500 font-bold">Failed to load merchandise.</div>';
        OperationsUI.toast('Failed to load store', 'error');
    }
}

async function showQuickSale(itemId, itemName) {
    const content = `
        <form id="sale-form" class="space-y-6">
            <input type="hidden" name="merchandise_id" value="${itemId}">
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Select Customer</label>
                    <select name="customer_id" id="sale-customer-select" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none" required>
                        <option value="">Loading customers...</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Quantity</label>
                    <input type="number" name="quantity" value="1" min="1" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none" required>
                </div>
            </div>
            <button type="submit" class="w-full bg-primary-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all">Confirm Purchase</button>
        </form>
    `;

    OperationsUI.openModal(`Sale: ${itemName}`, content);

    // Load customers for select
    try {
        const result = await OperationsAPI.getClients();
        const select = document.getElementById('sale-customer-select');
        select.innerHTML = '<option value="">-- Choose Client --</option>' + 
            result.data.map(u => `<option value="${u.id}">${u.fullname} (${u.phone_number})</option>`).join('');
    } catch (e) {
        console.error('Failed to load customers for sale:', e);
        OperationsUI.toast('Failed to load customers', 'error');
    }

    document.getElementById('sale-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            await OperationsAPI.placeOrder(
                formData.get('customer_id'),
                formData.get('merchandise_id'),
                formData.get('quantity')
            );
            OperationsUI.toast('Sale recorded!', 'success');
            OperationsUI.closeModal();
            renderStore();
            updateGlobalStats();
        } catch (err) {
            OperationsUI.toast(err.message, 'error');
        }
    });
}

// --- Tab: Finance ---
async function initFinanceTab() {
    const dateInput = document.getElementById('balance-date');
    dateInput.addEventListener('change', (e) => {
        renderBalance(e.target.value);
    });

    renderBalance();

    document.getElementById('expense-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            await OperationsAPI.recordExpense(Object.fromEntries(formData));
            OperationsUI.toast('Expense recorded!', 'success');
            e.target.reset();
            renderBalance(dateInput.value);
        } catch (err) {
            OperationsUI.toast(err.message, 'error');
        }
    });
}

async function renderBalance(date = '') {
    try {
        const result = await OperationsAPI.getDailyBalance(date);
        OperationsUI.renderBalance(result.data);
    } catch (e) {
        console.error('Failed to load balance:', e);
        OperationsUI.toast('Failed to load balance', 'error');
    }
}
