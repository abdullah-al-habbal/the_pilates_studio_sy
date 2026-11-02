// public/js/operations/modules/client-order.js
//
// "Quick Order" opened from the client details modal. The customer is fixed, so the flow is
// client-first: pick an item, set quantity/currency, confirm. One item per order, paid in full
// immediately (the server computes the total) — same rules as the Store tab's Quick Sale.
import { showClientDetails } from "./clients.js";
import { updateGlobalStats } from "./tabs.js";

const state = {
    userId: null,
    userName: "",
    items: [],
    selected: null,
    query: "",
    submitting: false,
};

const INPUT_CLS =
    "w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none";

function esc(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

const t = (key) => window.__(`operations_ui.store.${key}`);

function modalTitle() {
    return t("quick_order_title");
}

function defaultCurrencyId() {
    // Same default the Store tab's Quick Sale uses.
    return window.OperationsCurrencies?.[0]?.id ?? null;
}

function unitPrice(item, currencyId) {
    if (item.base_price == null) return null;
    return OperationsUI.computeAmountFromBase(item.base_price, currencyId);
}

function header() {
    return `
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <p class="text-sm text-slate-500">
                ${esc(t("quick_order_for"))} <strong class="text-slate-800 dark:text-slate-100">${esc(state.userName)}</strong>
            </p>
            <button onclick="window.backToClientFromOrder()"
                    class="text-xs font-bold text-primary-600 hover:underline">${esc(t("back_to_client"))}</button>
        </div>`;
}

function shimmer() {
    return `
        <div class="space-y-4">
            ${Array(4)
                .fill("")
                .map(() => `<div class="shimmer-cell w-full" style="height:56px;border-radius:12px;"></div>`)
                .join("")}
        </div>`;
}

function errorView(message) {
    return `
        <div class="flex flex-col items-center py-12 gap-4 text-center">
            <p class="text-rose-500 font-bold">${esc(t("load_failed"))}</p>
            <p class="text-sm text-slate-400">${esc(message)}</p>
            <div class="flex gap-3">
                <button onclick="window.showClientOrder(${Number(state.userId)}, window.__clientOrderName)"
                        class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-bold">${esc(window.__("operations_ui.clients.try_again"))}</button>
                <button onclick="window.backToClientFromOrder()"
                        class="px-4 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-sm font-medium">${esc(t("back_to_client"))}</button>
            </div>
        </div>`;
}

export async function showClientOrder(userId, fullname = "") {
    Object.assign(state, {
        userId: Number(userId),
        userName: fullname,
        items: [],
        selected: null,
        query: "",
        submitting: false,
    });
    window.__clientOrderName = fullname;

    OperationsUI.openModal(modalTitle(), shimmer());

    try {
        // Always fetch: stock changes with every sale, and the Store tab cache may be empty or stale.
        const result = await OperationsAPI.getStoreItems();
        state.items = result?.data ?? [];
        window.OperationsStoreItems = state.items;
        renderPicker();
    } catch (e) {
        console.error("Quick order: failed to load items", e);
        OperationsUI.openModal(modalTitle(), errorView(e.message));
    }
}

function renderPicker() {
    OperationsUI.openModal(
        modalTitle(),
        `
        <div class="space-y-5">
            ${header()}
            <input type="text" id="qo-search" autocomplete="off"
                   placeholder="${esc(t("search_items_placeholder"))}"
                   value="${esc(state.query)}" class="${INPUT_CLS}">
            <div id="qo-items" class="space-y-2 max-h-96 overflow-y-auto"></div>
        </div>`,
    );

    const search = document.getElementById("qo-search");
    search?.addEventListener("input", (e) => {
        state.query = e.target.value;
        renderItemList();
    });
    renderItemList();
    search?.focus();
}

function renderItemList() {
    const list = document.getElementById("qo-items");
    if (!list) return;

    const q = state.query.trim().toLowerCase();
    const items = state.items.filter(
        (i) =>
            !q ||
            String(i.name ?? "").toLowerCase().includes(q) ||
            String(i.category ?? "").toLowerCase().includes(q),
    );

    if (items.length === 0) {
        list.innerHTML = `<p class="py-8 text-center text-slate-400 italic">${esc(
            state.items.length === 0 ? window.__("operations_ui.store.no_merchandise") : t("no_items_match"),
        )}</p>`;
        return;
    }

    const currencyId = defaultCurrencyId();
    list.innerHTML = items
        .map((item) => {
            const price = unitPrice(item, currencyId);
            const outOfStock = Number(item.stock_quantity) <= 0;
            const unavailable = outOfStock || item.base_price == null;
            return `
            <button type="button" ${unavailable ? "disabled" : ""}
                    onclick="window.selectQuickOrderItem(${Number(item.id)})"
                    class="w-full flex items-center justify-between gap-4 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-left transition-colors ${
                        unavailable ? "opacity-50 cursor-not-allowed" : "hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-slate-800"
                    }">
                <span class="min-w-0">
                    <span class="block font-bold truncate">${esc(item.name)}</span>
                    <span class="block text-xs ${Number(item.stock_quantity) <= 5 ? "text-amber-600" : "text-slate-400"}">
                        ${esc(item.category || t("product_fallback"))} &bull;
                        ${outOfStock ? esc(t("out_of_stock")) : `${esc(t("stock_label"))}: ${Number(item.stock_quantity)}`}
                    </span>
                </span>
                <span class="font-black whitespace-nowrap">${
                    price === null ? esc(t("no_price")) : esc(OperationsUI.formatCurrency(price, currencyId))
                }</span>
            </button>`;
        })
        .join("");
}

export function selectQuickOrderItem(itemId) {
    const item = state.items.find((i) => i.id === Number(itemId));
    if (!item) {
        OperationsUI.toast(esc(t("item_not_found")), "error");
        return;
    }
    state.selected = item;
    renderOrderForm();
}

function renderOrderForm() {
    const item = state.selected;
    const currencyId = defaultCurrencyId();

    OperationsUI.openModal(
        modalTitle(),
        `
        <div class="space-y-6">
            ${header()}

            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3 bg-slate-50 dark:bg-slate-900">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest text-primary-500">${esc(t("selected_item"))}</p>
                    <p class="font-bold truncate">${esc(item.name)}</p>
                    <p class="text-xs text-slate-400">${esc(t("in_stock_prefix"))} ${Number(item.stock_quantity)}</p>
                </div>
                <button onclick="window.changeQuickOrderItem()"
                        class="text-xs font-bold text-primary-600 hover:underline">${esc(t("change_item"))}</button>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">${esc(t("currency_label"))}</label>
                    <select id="qo-currency" class="${INPUT_CLS}">
                        ${(window.OperationsCurrencies || [])
                            .map(
                                (c) =>
                                    `<option value="${Number(c.id)}" ${c.id === currencyId ? "selected" : ""}>${esc(OperationsUI.currencyLabel(c))}</option>`,
                            )
                            .join("")}
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">${esc(t("quantity_label"))}</label>
                    <input type="number" id="qo-quantity" value="1" min="1" max="${Number(item.stock_quantity)}" step="1" class="${INPUT_CLS}">
                </div>
            </div>

            <p id="qo-error" class="hidden text-sm font-medium text-rose-600 rounded-xl bg-rose-50 dark:bg-rose-900/20 px-4 py-3"></p>

            <div id="qo-preview"></div>

            <p class="text-xs text-slate-400">${esc(t("pay_note"))}</p>

            <button id="qo-submit" onclick="window.submitQuickOrder()"
                    class="w-full bg-primary-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:scale-[1.01] transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                ${esc(t("pay_now"))}
            </button>
        </div>`,
    );

    const qty = document.getElementById("qo-quantity");
    const cur = document.getElementById("qo-currency");
    qty?.addEventListener("input", refreshPreview);
    cur?.addEventListener("change", refreshPreview);
    refreshPreview();
}

function readForm() {
    const quantity = Number(document.getElementById("qo-quantity")?.value);
    const currencyId = Number(document.getElementById("qo-currency")?.value);
    return { quantity, currencyId };
}

function showError(message) {
    const el = document.getElementById("qo-error");
    if (!el) return;
    el.textContent = message ?? "";
    el.classList.toggle("hidden", !message);
}

/** Returns an error message, or null when the form can be submitted. */
function validate({ quantity, currencyId }) {
    const item = state.selected;
    if (!item) return t("select_item_required");
    if (!currencyId) return t("select_currency");
    if (!Number.isInteger(quantity) || quantity < 1) return t("quantity_invalid");
    if (quantity > Number(item.stock_quantity)) {
        return t("insufficient_stock").replace(":stock", Number(item.stock_quantity));
    }
    return null;
}

function refreshPreview() {
    const item = state.selected;
    const box = document.getElementById("qo-preview");
    const btn = document.getElementById("qo-submit");
    if (!item || !box) return;

    const form = readForm();
    const error = validate(form);
    showError(error);
    if (btn && !state.submitting) btn.disabled = Boolean(error);

    const quantity = Number.isInteger(form.quantity) && form.quantity > 0 ? form.quantity : 1;
    const unit = unitPrice(item, form.currencyId) ?? 0;
    const fmt = (n) => esc(OperationsUI.formatCurrency(n, form.currencyId));

    box.innerHTML = `
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50 dark:bg-slate-900">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm font-medium text-slate-500">${esc(t("price_per_unit"))}</span>
                <span class="font-bold">${fmt(unit)}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-slate-500">${esc(t("quantity_label"))}</span>
                <span class="font-bold">${quantity}</span>
            </div>
            <div class="border-t border-slate-200 dark:border-slate-800 mt-4 pt-4 flex justify-between items-center">
                <span class="text-sm font-medium text-slate-500">${esc(t("total_label"))}</span>
                <span class="text-xl font-black">${fmt(unit * quantity)}</span>
            </div>
        </div>`;
}

export function changeQuickOrderItem() {
    state.selected = null;
    renderPicker();
}

export function backToClientFromOrder() {
    return showClientDetails(state.userId);
}

/** Server-side field errors (422) → one readable line; falls back to the response message. */
function describeError(e) {
    if (e?.errors) {
        const first = Object.values(e.errors).flat()[0];
        if (first) return String(first);
    }
    return e?.message || t("order_failed");
}

export async function submitQuickOrder() {
    if (state.submitting) return;

    const form = readForm();
    const error = validate(form);
    if (error) {
        showError(error);
        return;
    }

    const btn = document.getElementById("qo-submit");
    const label = btn?.textContent;
    state.submitting = true;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="btn-spinner"></span>${esc(label?.trim())}`;
    }

    try {
        await OperationsAPI.placeOrder(state.userId, state.selected.id, form.quantity, form.currencyId);

        OperationsUI.toast(esc(t("sale_success")), "success");
        updateGlobalStats();
        if (typeof window.renderStore === "function" && document.getElementById("store-grid")) {
            window.renderStore();
        }
        state.submitting = false;
        await showClientDetails(state.userId);
    } catch (e) {
        console.error("Quick order failed:", e);
        state.submitting = false;
        const message = describeError(e);
        showError(message);
        OperationsUI.toast(esc(message), "error");
        if (btn) {
            btn.textContent = label?.trim() ?? "";
            btn.disabled = false;
        }
        // Stock may have changed under us; refresh it so the next attempt validates correctly.
        if (e?.status === 422) refreshItemsSilently();
    }
}

async function refreshItemsSilently() {
    try {
        const result = await OperationsAPI.getStoreItems();
        state.items = result?.data ?? state.items;
        window.OperationsStoreItems = state.items;
        const current = state.selected && state.items.find((i) => i.id === state.selected.id);
        if (current) {
            state.selected = current;
            const qty = document.getElementById("qo-quantity");
            if (qty) qty.max = String(current.stock_quantity);
        }
    } catch {
        /* keep the stale list; the server re-checks stock on every submit */
    }
}

window.showClientOrder = showClientOrder;
window.selectQuickOrderItem = selectQuickOrderItem;
window.changeQuickOrderItem = changeQuickOrderItem;
window.backToClientFromOrder = backToClientFromOrder;
window.submitQuickOrder = submitQuickOrder;
