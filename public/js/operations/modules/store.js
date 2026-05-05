import { updateGlobalStats } from './tabs.js';

export function initStoreTab() {
    renderStore();
}

export async function renderStore() {
    OperationsUI.renderStoreShimmer();

    try {
        const result = await OperationsAPI.getStoreItems();

        if (!result.data || result.data.length === 0) {
            document.getElementById('store-grid').innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400">
                    No merchandise available.
                </div>`;
            return;
        }

        document.getElementById('store-grid').innerHTML = result.data.map(item => `
            <div class="glass-card rounded-2xl p-6 space-y-4 hover:shadow-xl transition-all border-b-4 ${item.stock_quantity > 5 ? 'border-primary-500' : 'border-amber-500'}">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-primary-500">
                            ${item.category || 'Product'}
                        </span>
                        <h4 class="text-xl font-bold">${item.name}</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-xl font-black">${OperationsUI.formatCurrency(item.price)}</span>
                    </div>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">In Stock:</span>
                    <span class="font-bold ${item.stock_quantity <= 5 ? 'text-amber-500' : ''}">
                        ${item.stock_quantity}
                    </span>
                </div>
                <button
                    onclick="window.showQuickSale(${item.id}, '${item.name.replace(/'/g, "\\'")}')"
                    class="w-full bg-slate-900 dark:bg-white dark:text-slate-900 text-white py-3 rounded-xl font-bold text-sm hover:scale-[1.02] transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 btn-single-action"
                    ${item.stock_quantity <= 0 ? 'disabled' : ''}>
                    ${item.stock_quantity <= 0 ? 'Out of Stock' : 'Quick Sale'}
                </button>
            </div>`).join('');

    } catch (e) {
        console.error('Failed to load store:', e);
        document.getElementById('store-grid').innerHTML = `
            <div class="col-span-full py-12 text-center space-y-3">
                <p class="text-rose-500 font-bold">Failed to load merchandise.</p>
                <p class="text-sm text-slate-400">${e.message}</p>
                <button onclick="window.renderStore()" class="text-xs text-primary-600 underline">Try again</button>
            </div>`;
        OperationsUI.toast('Failed to load store', 'error');
    }
}

export async function showQuickSale(itemId, itemName) {
    const existingTab = () => `
        <div id="existing-customer-form" class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Select Customer</label>
                <select name="customer_id" id="sale-customer-select"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="">Loading customers...</option>
                </select>
            </div>
        </div>`;

    const walkInTab = () => `
        <div id="walkin-customer-form" class="space-y-4 hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="walkin-fullname" placeholder="Jane Doe"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">Phone <span class="text-rose-500">*</span></label>
                    <input type="text" id="walkin-phone" placeholder="+971..."
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 uppercase">Email <span class="text-slate-400">(optional)</span></label>
                <input type="email" id="walkin-email" placeholder="jane@example.com"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
        </div>`;

    const content = `
        <div class="space-y-6">
            <input type="hidden" id="sale-merchandise-id" value="${itemId}">

            <!-- Customer mode toggle -->
            <div class="flex rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 text-sm font-bold">
                <button id="btn-existing" onclick="window.toggleSaleMode('existing')"
                    class="flex-1 py-2.5 bg-primary-600 text-white transition-colors">
                    Existing Client
                </button>
                <button id="btn-walkin" onclick="window.toggleSaleMode('walkin')"
                    class="flex-1 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">
                    + Walk-in
                </button>
            </div>

            ${existingTab()}
            ${walkInTab()}

            <!-- Quantity -->
            <div class="space-y-2">
                <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Quantity</label>
                <input type="number" id="sale-quantity" value="1" min="1"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <button id="sale-submit-btn" onclick="window.submitQuickSale()"
                class="w-full bg-primary-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:scale-[1.01] transition-all btn-single-action">
                Confirm Purchase
            </button>
        </div>`;

    OperationsUI.openModal(`Sale: ${itemName}`, content);

    try {
        const result = await OperationsAPI.getClients('', 1);
        const select = document.getElementById('sale-customer-select');
        if (select) {
            select.innerHTML = '<option value="">-- Choose Client --</option>' +
                result.data.map(u => `<option value="${u.id}">${u.fullname} (${u.phone_number})</option>`).join('');
        }
    } catch (e) {
        console.error('Failed to load customers for sale:', e);
        const select = document.getElementById('sale-customer-select');
        if (select) select.innerHTML = '<option value="">Failed to load clients</option>';
        OperationsUI.toast('Failed to load customers', 'error');
    }
}

export function toggleSaleMode(mode) {
    const isExisting = mode === 'existing';

    document.getElementById('existing-customer-form').classList.toggle('hidden', !isExisting);
    document.getElementById('walkin-customer-form').classList.toggle('hidden', isExisting);

    document.getElementById('btn-existing').className = `flex-1 py-2.5 transition-colors font-bold text-sm ${isExisting ? 'bg-primary-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'}`;
    document.getElementById('btn-walkin').className   = `flex-1 py-2.5 transition-colors font-bold text-sm ${!isExisting ? 'bg-primary-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'}`;
}

export async function submitQuickSale() {
    const btn           = document.getElementById('sale-submit-btn');
    const merchandiseId = document.getElementById('sale-merchandise-id').value;
    const quantity      = document.getElementById('sale-quantity').value;
    const isWalkIn      = !document.getElementById('walkin-customer-form').classList.contains('hidden');

    try {
        if (isWalkIn) {
            const fullname = document.getElementById('walkin-fullname').value.trim();
            const phone    = document.getElementById('walkin-phone').value.trim();
            const email    = document.getElementById('walkin-email').value.trim() || null;

            if (!fullname || !phone) {
                OperationsUI.toast('Full name and phone are required for walk-in.', 'warning');
                return;
            }

            await OperationsAPI.storeWalkInOrder(merchandiseId, quantity, fullname, phone, email);
        } else {
            const customerId = document.getElementById('sale-customer-select').value;
            if (!customerId) {
                OperationsUI.toast('Please select a customer.', 'warning');
                return;
            }
            await OperationsAPI.placeOrder(customerId, merchandiseId, quantity);
        }

        OperationsUI.toast('Sale recorded successfully!', 'success');
        OperationsUI.closeModal();
        renderStore();
        updateGlobalStats();

    } catch (err) {
        console.error('Quick sale failed:', err);
        OperationsUI.toast(err.message, 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Confirm Purchase';
        }
    }
}

window.renderStore = renderStore;
window.showQuickSale = showQuickSale;
window.toggleSaleMode = toggleSaleMode;
window.submitQuickSale = submitQuickSale;
