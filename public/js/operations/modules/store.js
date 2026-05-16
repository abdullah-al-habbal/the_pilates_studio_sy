import { updateGlobalStats } from "./tabs.js";

export function initStoreTab() {
    renderStore();
}

export async function renderStore() {
    OperationsUI.renderStoreShimmer();

    try {
        const result = await OperationsAPI.getStoreItems();
        window.OperationsStoreItems = result.data || [];

        if (!result.data || result.data.length === 0) {
            window.OperationsStoreItems = [];
            document.getElementById("store-grid").innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400">
                    No merchandise available.
                </div>`;
            return;
        }

        document.getElementById("store-grid").innerHTML = result.data
            .map(
                (item) => `
            <div class="glass-card rounded-2xl p-6 space-y-4 hover:shadow-xl transition-all border-b-4 ${item.stock_quantity > 5 ? "border-primary-500" : "border-amber-500"}">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-primary-500">
                            ${item.category || "Product"}
                        </span>
                        <h4 class="text-xl font-bold">${item.name}</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-xl font-black">${OperationsUI.formatCurrency(OperationsUI.computeAmountFromBase(item.base_price, window.OperationsCurrencies?.[0]?.id))}</span>
                    </div>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">In Stock:</span>
                    <span class="font-bold ${item.stock_quantity <= 5 ? "text-amber-500" : ""}">
                        ${item.stock_quantity}
                    </span>
                </div>
                <button
                    onclick="window.showQuickSale(${item.id})"
                    class="w-full bg-slate-900 dark:bg-white dark:text-slate-900 text-white py-3 rounded-xl font-bold text-sm hover:scale-[1.02] transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 btn-single-action"
                    ${item.stock_quantity <= 0 ? "disabled" : ""}>
                    ${item.stock_quantity <= 0 ? "Out of Stock" : "Quick Sale"}
                </button>
            </div>`,
            )
            .join("");
    } catch (e) {
        console.error("Failed to load store:", e);
        document.getElementById("store-grid").innerHTML = `
            <div class="col-span-full py-12 text-center space-y-3">
                <p class="text-rose-500 font-bold">Failed to load merchandise.</p>
                <p class="text-sm text-slate-400">${e.message}</p>
                <button onclick="window.renderStore()" class="text-xs text-primary-600 underline">Try again</button>
            </div>`;
        OperationsUI.toast("Failed to load store", "error");
    }
}

function getStoreItemById(itemId) {
    return (
        (window.OperationsStoreItems || []).find(
            (item) => item.id === Number(itemId),
        ) || null
    );
}

function renderSalePreview(item, quantity, currencyId) {
    const unitPrice = OperationsUI.computeAmountFromBase(item.base_price, currencyId);
    const totalPrice = unitPrice * quantity;
    const currencyLabel = currencyId
        ? OperationsUI.formatCurrency(unitPrice, currencyId)
        : OperationsUI.formatCurrency(unitPrice);
    const totalLabel = currencyId
        ? OperationsUI.formatCurrency(totalPrice, currencyId)
        : OperationsUI.formatCurrency(totalPrice);

    return `
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50 dark:bg-slate-900">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm font-medium text-slate-500">Price per unit</span>
                <span class="font-bold">${currencyLabel}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-slate-500">Quantity</span>
                <span class="font-bold">${quantity}</span>
            </div>
            <div class="border-t border-slate-200 dark:border-slate-800 mt-4 pt-4 flex justify-between items-center">
                <span class="text-sm font-medium text-slate-500">Total</span>
                <span class="text-xl font-black text-slate-900 dark:text-white">${totalLabel}</span>
            </div>
        </div>`;
}

export async function showQuickSale(itemId) {
    const item = getStoreItemById(itemId);
    if (!item) {
        OperationsUI.toast(
            "Could not find the selected merchandise item.",
            "error",
        );
        return;
    }

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

    const defaultCurrencyId = window.OperationsCurrencies?.[0]?.id || null;
    const currentQuantity = 1;
    const currentCurrencyId = defaultCurrencyId;

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

            <!-- Quantity & Currency -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Currency</label>
                    <select id="sale-currency-id" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                        ${(window.OperationsCurrencies || [])
                            .map(
                                (c) =>
                                    `<option value="${c.id}" ${c.id === defaultCurrencyId ? "selected" : ""}>${c.code} (${c.symbol})</option>`,
                            )
                            .join("")}
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Quantity</label>
                    <input type="number" id="sale-quantity" value="1" min="1" step="1"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>

            <div id="sale-preview-container">
                ${renderSalePreview(item, currentQuantity, currentCurrencyId)}
            </div>

            <button id="sale-submit-btn" onclick="window.submitQuickSale()"
                class="w-full bg-primary-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:scale-[1.01] transition-all btn-single-action">
                Confirm Purchase
            </button>
        </div>`;

    OperationsUI.openModal(`Sale: ${item.name}`, content);

    const quantityInput = document.getElementById("sale-quantity");
    const currencySelect = document.getElementById("sale-currency-id");

    const refreshPreview = () => {
        const quantity = Math.max(1, parseInt(quantityInput?.value || "1", 10));
        const currencyId = parseInt(
            currencySelect?.value || defaultCurrencyId,
            10,
        );
        const previewContainer = document.getElementById(
            "sale-preview-container",
        );
        if (previewContainer) {
            previewContainer.innerHTML = renderSalePreview(
                item,
                quantity,
                currencyId,
            );
        }
    };

    quantityInput?.addEventListener("input", refreshPreview);
    currencySelect?.addEventListener("change", refreshPreview);

    refreshPreview();

    try {
        const result = await OperationsAPI.getClients("", 1);
        const select = document.getElementById("sale-customer-select");
        if (select) {
            select.innerHTML =
                '<option value="">-- Choose Client --</option>' +
                result.data
                    .map(
                        (u) =>
                            `<option value="${u.id}">${u.fullname} (${u.phone_number})</option>`,
                    )
                    .join("");
        }
    } catch (e) {
        console.error("Failed to load customers for sale:", e);
        const select = document.getElementById("sale-customer-select");
        if (select)
            select.innerHTML =
                '<option value="">Failed to load clients</option>';
        OperationsUI.toast("Failed to load customers", "error");
    }
}

export function toggleSaleMode(mode) {
    const isExisting = mode === "existing";

    document
        .getElementById("existing-customer-form")
        .classList.toggle("hidden", !isExisting);
    document
        .getElementById("walkin-customer-form")
        .classList.toggle("hidden", isExisting);

    document.getElementById("btn-existing").className =
        `flex-1 py-2.5 transition-colors font-bold text-sm ${isExisting ? "bg-primary-600 text-white" : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"}`;
    document.getElementById("btn-walkin").className =
        `flex-1 py-2.5 transition-colors font-bold text-sm ${!isExisting ? "bg-primary-600 text-white" : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"}`;
}

export async function submitQuickSale() {
    const btn = document.getElementById("sale-submit-btn");
    const merchandiseId = document.getElementById("sale-merchandise-id").value;
    const quantityInput = document.getElementById("sale-quantity");
    const quantity = parseInt(quantityInput?.value || "0", 10);
    const currencyId = document.getElementById("sale-currency-id").value;
    const isWalkIn = !document
        .getElementById("walkin-customer-form")
        .classList.contains("hidden");

    try {
        if (!currencyId) {
            OperationsUI.toast("Please select a currency.", "warning");
            return;
        }

        if (!Number.isInteger(quantity) || quantity < 1) {
            OperationsUI.toast(
                "Quantity must be a whole number of items.",
                "warning",
            );
            return;
        }

        if (isWalkIn) {
            const fullname = document
                .getElementById("walkin-fullname")
                .value.trim();
            const phone = document.getElementById("walkin-phone").value.trim();
            const email =
                document.getElementById("walkin-email").value.trim() || null;

            if (!fullname || !phone) {
                OperationsUI.toast(
                    "Full name and phone are required for walk-in.",
                    "warning",
                );
                return;
            }

            await OperationsAPI.storeWalkInOrder(
                merchandiseId,
                quantity,
                currencyId,
                fullname,
                phone,
                email,
            );
        } else {
            const customerId = document.getElementById(
                "sale-customer-select",
            ).value;
            if (!customerId) {
                OperationsUI.toast("Please select a customer.", "warning");
                return;
            }
            await OperationsAPI.placeOrder(
                customerId,
                merchandiseId,
                quantity,
                currencyId,
            );
        }

        OperationsUI.toast("Sale recorded successfully!", "success");
        OperationsUI.closeModal();
        renderStore();
        updateGlobalStats();
    } catch (err) {
        console.error("Quick sale failed:", err);
        OperationsUI.toast(err.message, "error");
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = "Confirm Purchase";
        }
    }
}

window.renderStore = renderStore;
window.showQuickSale = showQuickSale;
window.toggleSaleMode = toggleSaleMode;
window.submitQuickSale = submitQuickSale;
