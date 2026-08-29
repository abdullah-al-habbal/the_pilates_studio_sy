// public\js\operations\modules\packages.js
import { renderClients, showClientDetails } from "./clients.js";

const bf = (key) => window.__(`operations_ui.historical_backfill.${key}`);

// Selections live here, not in the DOM. OperationsUI.openModal replaces
// #modal-container.innerHTML on every render, so anything held in a checkbox is gone the moment
// the step changes or the session list appends a page.
const backfill = {
    active: false,
    step: 1,
    userId: null,
    packages: [],
    package: null,
    currencyId: null,
    purchasedAt: "",
    exchangeRate: "",
    rateTouched: false,
    sessions: [],
    attended: new Set(),
    missed: new Set(),
    cursor: null,
    hasMore: false,
    loading: false,
    window: null,
    submitting: false,
};

function resetBackfill(userId) {
    Object.assign(backfill, {
        active: false,
        step: 1,
        userId,
        packages: [],
        package: null,
        currencyId: window.OperationsCurrencies?.[0]?.id ?? null,
        purchasedAt: "",
        exchangeRate: "",
        rateTouched: false,
        sessions: [],
        attended: new Set(),
        missed: new Set(),
        cursor: null,
        hasMore: false,
        loading: false,
        window: null,
        submitting: false,
    });
}

function clearSelections() {
    // The package and the date together define the validity window, so a selection made under a
    // different one is stale rather than merely inconvenient.
    backfill.sessions = [];
    backfill.attended.clear();
    backfill.missed.clear();
    backfill.cursor = null;
    backfill.hasMore = false;
    backfill.window = null;
}

function selectedCount() {
    return backfill.attended.size + backfill.missed.size;
}

function remainingCredits() {
    return (backfill.package?.total_credits ?? 0) - selectedCount();
}

function currentRateFor(currencyId) {
    const currency = (window.OperationsCurrencies || []).find(
        (c) => Number(c.id) === Number(currencyId),
    );
    return currency?.exchange_rate ?? 1;
}

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (c) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
    })[c]);
}


async function createNewPackage(userId, formData) {
    try {
        await OperationsAPI.createPackage(formData);
        OperationsUI.toast(window.__('operations_ui.packages.package_created'), "success");
        showPackageAssignment(userId);
    } catch (e) {
        OperationsUI.toast(e.message, "error");
    }
}

async function updatePackage(userId, packageId, formData) {
    try {
        await OperationsAPI.updatePackage(packageId, formData);
        OperationsUI.toast(window.__('operations_ui.packages.package_updated'), "success");
        showPackageAssignment(userId);
    } catch (e) {
        OperationsUI.toast(e.message, "error");
    }
}

async function deletePackage(userId, packageId) {
    const result = await Swal.fire({
        title: window.__('operations_ui.packages.delete_title'),
        text: window.__('operations_ui.packages.delete_text'),
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e11d48",
        cancelButtonColor: "#64748b",
        confirmButtonText: window.__('operations_ui.packages.confirm_delete'),
    });

    if (!result.isConfirmed) return;

    try {
        await OperationsAPI.deletePackage(packageId);
        OperationsUI.toast(window.__('operations_ui.packages.package_deleted'), "success");
        showPackageAssignment(userId);
    } catch (e) {
        OperationsUI.toast(e.message, "error");
    }
}

export async function showPackageAssignment(userId) {
    resetBackfill(userId);

    OperationsUI.openModal(
        window.__('operations_ui.packages.assign_modal_title'),
        `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            ${Array(4)
                .fill("")
                .map(
                    () => `
                <div class="rounded-2xl border-2 border-slate-100 dark:border-slate-800 p-6 space-y-3">
                    <div class="shimmer-cell w-50" style="height:20px;border-radius:4px;"></div>
                    <div class="shimmer-cell w-30" style="height:14px;"></div>
                    <div class="shimmer-cell w-20" style="height:28px;border-radius:6px;margin-top:1rem;"></div>
                </div>`,
                )
                .join("")}
        </div>`,
    );

    try {
        const result = await OperationsAPI.getPackages();
        backfill.packages = result.data;

        renderAssignModal();
    } catch (e) {
        console.error("Failed to load packages:", e);
        OperationsUI.toast(window.__('operations_ui.packages.load_failed'), "error");
    }
}

function renderAssignModal() {
    if (backfill.step === 2) return renderConfirmStep();
    if (backfill.step === 3) return renderSessionStep();
    if (backfill.step === 4) return renderReviewStep();

    renderPackageStep();
}

function renderPackageStep() {
    const userId = backfill.userId;

    const content = `
        <div class="mb-4">
            <button id="show-create-package-form" class="w-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 py-2.5 rounded-xl font-medium text-sm transition-colors">
                ${window.__('operations_ui.packages.new_package_button')}
            </button>
        </div>
        <div id="package-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            ${backfill.packages.map((p) => renderPackageCard(p, userId)).join("")}
        </div>
        <div id="create-package-form" class="hidden space-y-4">${renderPackageForm("create", userId, null)}</div>
        <div id="edit-package-container" class="hidden"></div>`;

    OperationsUI.openModal(window.__('operations_ui.packages.assign_modal_title'), content);

    attachGlobalHandlers(userId);

    backfill.packages.forEach((p) => {
        setTimeout(() => window.updatePackageAmount(p.id), 0);
    });
}

function renderStepper() {
    // Only rendered on the historical branch. With the checkbox unticked there is exactly one
    // step and a progress bar would be noise.
    const labels = [bf("step_1_label"), bf("step_2_label"), bf("step_3_label")];
    const current = backfill.step - 1;

    return `
        <div class="flex items-center gap-2 mb-6">
            ${labels.map((label, i) => {
                const n = i + 1;
                const done = current > n;
                const active = current === n;
                const tone = active
                    ? "bg-primary-600 text-white"
                    : done
                      ? "bg-emerald-500 text-white"
                      : "bg-slate-200 dark:bg-slate-700 text-slate-500";
                return `
                    <div class="flex items-center gap-2 ${done ? "cursor-pointer" : ""}"
                         ${done ? `onclick="window.backfillGoToStep(${n + 1})"` : ""}>
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold ${tone}">${done ? "&check;" : n}</span>
                        <span class="text-xs font-bold uppercase tracking-wider ${active ? "text-primary-600" : "text-slate-400"}">${label}</span>
                    </div>
                    ${n < labels.length ? '<div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>' : ""}`;
            }).join("")}
        </div>`;
}

function renderHistoricalFields() {
    const today = new Date().toISOString().slice(0, 10);
    const currencies = window.OperationsCurrencies || [];

    return `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">${bf("purchased_at_label")}</label>
                <input type="date" id="bf-purchased-at" value="${backfill.purchasedAt}" max="${today}"
                       onchange="window.backfillDateChanged(this.value)"
                       class="w-full rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-transparent px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">${bf("currency_label")}</label>
                <select id="bf-currency" onchange="window.backfillCurrencyChanged(this.value)"
                        class="w-full rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-transparent px-4 py-2.5 text-sm">
                    ${currencies.map((c) => `<option value="${c.id}" ${Number(backfill.currencyId) === Number(c.id) ? "selected" : ""}>${c.code}</option>`).join("")}
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">${bf("exchange_rate_label")}</label>
                <input type="number" step="0.000001" min="0.000001" id="bf-rate" value="${backfill.exchangeRate}"
                       placeholder="${currentRateFor(backfill.currencyId)}"
                       oninput="window.backfillRateEdited(this.value)"
                       class="w-full rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-transparent px-4 py-2.5 text-sm text-slate-500">
            </div>
        </div>
        <p class="text-xs text-slate-400 mb-4">${bf("exchange_rate_hint")}</p>`;
}

function renderPackageCard(p, userId) {
    const prices = (p.prices || []).reduce((acc, pr) => {
        acc[pr.currency_id] = pr.amount;
        return acc;
    }, {});
    const pricesJson = JSON.stringify(prices).replace(/"/g, "&quot;");
    const defaultCurrencyId = window.OperationsCurrencies?.[0]?.id || 1;
    const selectedCurrencyId =
        p.prices && p.prices.length > 0
            ? p.prices[0].currency_id
            : defaultCurrencyId;
    const amount = getPackageAmount(p, selectedCurrencyId);

    return `
        <div id="card-${p.id}" class="flex flex-col p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-800 transition-all text-left group">
            <div class="flex justify-between items-start">
                <span class="text-lg font-bold text-slate-900 dark:text-white">${p.name}</span>
                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="window.editPackage(${userId}, ${p.id})" title="${window.__('operations_ui.packages.edit_title')}" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button onclick="window.deletePackage(${userId}, ${p.id})" title="${window.__('operations_ui.packages.delete_title_attr')}" class="p-1 hover:bg-rose-100 dark:hover:bg-rose-900/20 rounded-lg text-rose-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            <span class="text-sm text-slate-500 mb-4">
                ${p.total_credits} ${window.__('operations_ui.packages.sessions_unit')} &bull; ${p.validity_days ? `${p.validity_days} ${window.__('operations_ui.packages.days_unit')}` : window.__('operations_ui.packages.no_expiry')}
            </span>
            <div class="space-y-3 mt-auto">
                <div class="flex gap-2">
                    <select id="currency-${p.id}" onchange="window.updatePackageAmount(${p.id})"
                            class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                        ${(window.OperationsCurrencies || []).map((c) => `<option value="${c.id}" ${c.id == selectedCurrencyId ? "selected" : ""}>${c.code} (${c.symbol})</option>`).join("")}
                    </select>
                    <input type="number" id="amount-${p.id}" value="${amount}" min="0" readonly
                           class="flex-1 px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none cursor-not-allowed"
                           placeholder="${window.__('operations_ui.packages.amount_placeholder')}"
                           data-prices="${pricesJson}"
                           data-base-price="${p.base_price ?? 0}">
                </div>
                <button id="assign-btn-${p.id}" onclick="window.confirmPackageAssignment(${p.id})"
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 rounded-xl transition-colors btn-single-action">
                    ${window.__('operations_ui.packages.assign_button')}
                </button>
            </div>
        </div>`;
}

function getPackageAmount(packageData, currencyId) {
    const basePrice = Number(packageData.base_price || 0);
    const converted =
        basePrice > 0
            ? OperationsUI.computeAmountFromBase(basePrice, currencyId)
            : 0;

    return converted > 0 ? converted : 0;
}

function renderPackageForm(context, userId, packageData = null) {
    const isEdit = context === "edit";
    const defaultValidity = packageData?.validity_days ?? "";
    const defaultCredits = packageData?.total_credits ?? 10;
    const defaultName = packageData?.name ?? "";
    const defaultCurrId =
        packageData?.prices?.[0]?.currency_id ??
        (window.OperationsCurrencies?.[0]?.id || 1);
    const defaultAmount = packageData?.prices?.[0]?.amount ?? 0;

    return `
        <div class="space-y-4 p-6 glass-card rounded-2xl border-2 border-primary-500/20">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">${window.__('operations_ui.packages.name_label')}</label>
                <input id="${context}-pkg-name" value="${defaultName}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" placeholder="${window.__('operations_ui.packages.name_placeholder')}">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">${window.__('operations_ui.packages.sessions_label')}</label>
                    <input type="number" id="${context}-pkg-credits" min="1" value="${defaultCredits}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">${window.__('operations_ui.packages.validity_label')}</label>
                    <input type="number" id="${context}-pkg-validity" min="0" value="${defaultValidity}" placeholder="${window.__('operations_ui.packages.validity_placeholder')}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Currency</label>
                    <select id="${context}-pkg-currency" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                        ${(window.OperationsCurrencies || []).map((c) => `<option value="${c.id}" ${c.id == defaultCurrId ? "selected" : ""}>${c.code} (${c.symbol})</option>`).join("")}
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">${window.__('operations_ui.packages.price_label')}</label>
                    <input type="number" id="${context}-pkg-price" min="0" value="${defaultAmount}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button id="cancel-${context}-package" class="flex-1 bg-slate-100 dark:bg-slate-800 py-2.5 rounded-xl text-sm font-medium btn-single-action transition-colors">${window.__('operations_ui.packages.cancel_button')}</button>
                <button id="submit-${context}-package" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-xl text-sm font-bold btn-single-action transition-all">
                    ${isEdit ? window.__('operations_ui.packages.save_changes') : window.__('operations_ui.packages.create_reload')}
                </button>
            </div>
        </div>`;
}

function attachGlobalHandlers(userId) {
    document
        .getElementById("show-create-package-form")
        ?.addEventListener("click", () => {
            document.getElementById("package-grid").classList.add("hidden");
            document
                .getElementById("edit-package-container")
                .classList.add("hidden");
            document
                .getElementById("create-package-form")
                .classList.remove("hidden");
            document
                .getElementById("show-create-package-form")
                .classList.add("hidden");
        });

    document
        .getElementById("cancel-create-package")
        ?.addEventListener("click", () => {
            document
                .getElementById("create-package-form")
                .classList.add("hidden");
            document.getElementById("package-grid").classList.remove("hidden");
            document
                .getElementById("show-create-package-form")
                .classList.remove("hidden");
        });

    document
        .getElementById("submit-create-package")
        ?.addEventListener("click", () => {
            const data = {
                name: document.getElementById("create-pkg-name")?.value,
                total_credits:
                    document.getElementById("create-pkg-credits")?.value,
                validity_days: document.getElementById("create-pkg-validity")
                    ?.value,
                currency_id: document.getElementById("create-pkg-currency")
                    ?.value,
                amount: document.getElementById("create-pkg-price")?.value,
            };

            if (!data.name || !data.total_credits) {
                OperationsUI.toast(window.__('operations_ui.packages.name_sessions_required'), "error");
                return;
            }

            createNewPackage(userId, data);
        });
}

window.editPackage = async function (userId, packageId) {
    try {
        const result = await OperationsAPI.getPackages();
        const packages = result.data;
        const pkg = packages.find((p) => p.id == packageId);
        if (!pkg) throw new Error(window.__('operations_ui.packages.package_not_found'));

        const card = document.getElementById(`card-${packageId}`);
        if (!card) return;

        document.getElementById("package-grid").classList.add("hidden");
        document
            .getElementById("show-create-package-form")
            .classList.add("hidden");
        document.getElementById("create-package-form").classList.add("hidden");

        const container = document.getElementById("edit-package-container");
        container.innerHTML = renderPackageForm("edit", userId, pkg);
        container.classList.remove("hidden");

        document
            .getElementById("cancel-edit-package")
            ?.addEventListener("click", () => {
                container.classList.add("hidden");
                document
                    .getElementById("package-grid")
                    .classList.remove("hidden");
                document
                    .getElementById("show-create-package-form")
                    .classList.remove("hidden");
            });

        document
            .getElementById("submit-edit-package")
            ?.addEventListener("click", () => {
                const data = {
                    name: document.getElementById("edit-pkg-name")?.value,
                    total_credits:
                        document.getElementById("edit-pkg-credits")?.value,
                    validity_days:
                        document.getElementById("edit-pkg-validity")?.value,
                    currency_id:
                        document.getElementById("edit-pkg-currency")?.value,
                    amount: document.getElementById("edit-pkg-price")?.value,
                };

                if (!data.name || !data.total_credits) {
                    OperationsUI.toast(
                        window.__('operations_ui.packages.name_sessions_required'),
                        "error",
                    );
                    return;
                }

                updatePackage(userId, packageId, data);
            });
    } catch (e) {
        OperationsUI.toast(window.__('operations_ui.packages.could_not_load_edit'), "error");
    }
};

window.deletePackage = function (userId, packageId) {
    deletePackage(userId, packageId);
};

export async function handlePackageAssign(userId, packageId) {
    const currencyId = document.getElementById(`currency-${packageId}`)?.value;

    if (!currencyId) {
        OperationsUI.toast(window.__('operations_ui.store.select_currency'), "error");
        return;
    }

    try {
        await OperationsAPI.assignPackage(userId, packageId, currencyId);
        OperationsUI.toast(window.__('operations_ui.packages.assign_success'), "success");
        OperationsUI.closeModal();
        renderClients();
    } catch (e) {
        OperationsUI.toast(e.message, "error");
    }
}

export async function handleFreeze(bookingId, userId) {
    const result = await Swal.fire({
        title: window.__('operations_ui.packages.freeze_title'),
        text: window.__('operations_ui.packages.freeze_text'),
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#f59e0b",
        cancelButtonColor: "#64748b",
        confirmButtonText: window.__('operations_ui.packages.confirm_freeze'),
    });

    if (!result.isConfirmed) return;

    try {
        await OperationsAPI.freezeBooking(bookingId);
        OperationsUI.toast(window.__('operations_ui.packages.package_frozen'), "success");
        showClientDetails(userId);
    } catch (e) {
        OperationsUI.toast(e.message, "error");
    }
}

export async function handleUnfreeze(bookingId, userId) {
    const result = await Swal.fire({
        title: window.__('operations_ui.packages.unfreeze_title'),
        text: window.__('operations_ui.packages.unfreeze_text'),
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "#10b981",
        cancelButtonColor: "#64748b",
        confirmButtonText: window.__('operations_ui.packages.confirm_unfreeze'),
    });

    if (!result.isConfirmed) return;

    try {
        await OperationsAPI.unfreezeBooking(bookingId);
        OperationsUI.toast(window.__('operations_ui.packages.package_unfrozen'), "success");
        showClientDetails(userId);
    } catch (e) {
        OperationsUI.toast(e.message, "error");
    }
}

window.updatePackageAmount = function (packageId) {
    const currencySelect = document.getElementById(`currency-${packageId}`);
    const amountInput = document.getElementById(`amount-${packageId}`);
    if (!currencySelect || !amountInput) return;

    const selectedCurrencyId = parseInt(currencySelect.value, 10);
    const basePrice = Number(amountInput.dataset.basePrice || 0);
    let amount = 0;
    if (basePrice > 0) {
        amount = OperationsUI.computeAmountFromBase(
            basePrice,
            selectedCurrencyId,
        );
    }

    amountInput.value = amount;

    const assignBtn = document.getElementById(`assign-btn-${packageId}`);
    if (assignBtn) {
        if (amount === 0) {
            assignBtn.disabled = true;
            assignBtn.title = window.__('operations_ui.packages.no_price_currency');
            assignBtn.classList.add("opacity-50", "cursor-not-allowed");
        } else {
            assignBtn.disabled = false;
            assignBtn.removeAttribute("title");
            assignBtn.classList.remove("opacity-50", "cursor-not-allowed");
        }
    }
};

// ------------------------------------------------------- historical: steps 2 & 3

function renderSummaryBar() {
    if (!backfill.package) return "";

    const cell = (label, value, tone = "") => `
        <div class="glass-card rounded-xl px-3 py-2">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">${label}</p>
            <p class="text-sm font-bold truncate ${tone}">${value}</p>
        </div>`;

    return `
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 text-center">
            ${cell(bf("summary_package"), escapeHtml(backfill.package.name))}
            ${cell(bf("summary_attended"), backfill.attended.size, "text-emerald-600")}
            ${cell(bf("summary_missed"), backfill.missed.size, "text-amber-600")}
            ${cell(bf("summary_remaining"), remainingCredits(), remainingCredits() < 0 ? "text-rose-600" : "")}
            ${cell(bf("summary_total"), backfill.package.total_credits)}
            ${backfill.window ? cell(bf("summary_window_from"), backfill.window.from) : ""}
            ${backfill.window ? cell(bf("summary_window_to"), backfill.window.to) : ""}
        </div>`;
}

// Written out in full, never interpolated: Tailwind scans source text, so a runtime-built
// `bg-${colour}-500` is absent from the compiled stylesheet and renders unstyled.
const TOGGLE_ACTIVE = {
    attended: "bg-emerald-500 text-white",
    missed: "bg-amber-500 text-white",
};

function sessionToggleButton(id, kind, label, active) {
    const cls = active
        ? TOGGLE_ACTIVE[kind]
        : "bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700";

    return `<button onclick="window.backfillToggleSession(${id}, '${kind}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${cls}">${label}</button>`;
}

function renderSessionRows() {
    if (backfill.sessions.length === 0 && !backfill.loading) {
        return `<div class="px-4 py-8 text-center text-sm text-slate-400">${bf("no_sessions_in_window")}</div>`;
    }

    const rows = backfill.sessions.map((s) => {
        const isAttended = backfill.attended.has(s.id);
        const isMissed = backfill.missed.has(s.id);
        const tone = isAttended
            ? "bg-emerald-50 dark:bg-emerald-900/20"
            : isMissed
              ? "bg-amber-50 dark:bg-amber-900/20"
              : "";

        return `
            <div class="flex items-center justify-between gap-3 px-4 py-3 ${tone}">
                <div class="min-w-0">
                    <p class="text-sm font-bold truncate">${escapeHtml(s.class_title ?? "—")}</p>
                    <p class="text-xs text-slate-500">
                        ${s.date} &bull; ${s.start_time}–${s.end_time}
                        ${s.instructor_name ? `&bull; ${escapeHtml(s.instructor_name)}` : ""}
                    </p>
                </div>
                <div class="flex gap-1 shrink-0">
                    ${sessionToggleButton(s.id, "attended", bf("mark_attended"), isAttended)}
                    ${sessionToggleButton(s.id, "missed", bf("mark_missed"), isMissed)}
                </div>
            </div>`;
    }).join("");

    return rows + (backfill.loading
        ? `<div class="px-4 py-3 text-center text-xs text-slate-400">${bf("loading")}</div>`
        : "");
}

function renderConfirmStep() {
    const p = backfill.package;
    const eligible = Number(p.validity_days) > 0;
    const currency = (window.OperationsCurrencies || []).find(
        (c) => Number(c.id) === Number(backfill.currencyId),
    );
    const amount = OperationsUI.computeAmountFromBase(Number(p.base_price || 0), backfill.currencyId);

    const row = (label, value) => `
        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
            <span class="text-sm text-slate-500">${label}</span>
            <span class="text-sm font-bold">${value}</span>
        </div>`;

    OperationsUI.openModal(window.__('operations_ui.packages.assign_modal_title'), `
        ${backfill.active ? renderStepper() : ""}

        <div class="glass-card rounded-2xl p-5 mb-5">
            ${row(bf("summary_package"), escapeHtml(p.name))}
            ${row(bf("summary_total"), `${p.total_credits} ${bf("credits_suffix")}`)}
            ${row(bf("summary_window_to"), eligible ? `${p.validity_days} ${bf("days_suffix")}` : window.__('operations_ui.packages.no_expiry'))}
            ${row(bf("currency_label"), currency ? `${currency.code} (${currency.symbol})` : "—")}
            ${row(window.__('operations_ui.packages.price_label'), amount)}
        </div>

        <label class="flex items-start gap-3 mb-5 px-4 py-3 rounded-xl border-2 ${backfill.active ? "border-primary-500" : "border-slate-100 dark:border-slate-800"} ${eligible ? "cursor-pointer" : "opacity-50"}">
            <input type="checkbox" ${backfill.active ? "checked" : ""} ${eligible ? "" : "disabled"}
                   onchange="window.toggleHistoricalEntry(this.checked)"
                   class="w-4 h-4 mt-0.5 rounded accent-primary-600">
            <span>
                <span class="block text-sm font-bold">${bf("trigger_button")}</span>
                ${eligible ? "" : `<span class="block text-xs text-amber-600 mt-1">${bf("requires_validity")}</span>`}
            </span>
        </label>

        ${backfill.active ? renderHistoricalFields() : ""}

        <div id="bf-step1-error" class="hidden mb-4 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:text-rose-300"></div>

        <div class="flex justify-between pt-2">
            <button onclick="window.backfillGoToStep(1)"
                    class="px-6 py-2.5 rounded-xl font-bold text-sm bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                ${window.__('operations_ui.packages.cancel_button')}
            </button>
            <button id="bf-approve" onclick="window.backfillNext()"
                    class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-emerald-700 transition-colors disabled:opacity-50">
                ${backfill.active ? bf("next_button") : window.__('operations_ui.packages.assign_button')}
            </button>
        </div>`);
}

function renderSessionStep() {
    OperationsUI.openModal(window.__('operations_ui.packages.assign_modal_title'), `
        ${renderStepper()}
        ${renderSummaryBar()}
        <p class="text-sm text-slate-500 mb-4">${bf("step_2_hint")}</p>
        <div id="bf-session-list"
             class="max-h-80 overflow-y-auto rounded-2xl border-2 border-slate-100 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800">
            ${renderSessionRows()}
        </div>
        <div class="flex justify-between pt-4">
            <button onclick="window.backfillGoToStep(2)"
                    class="px-6 py-2.5 rounded-xl font-bold text-sm bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                ${bf("back_button")}
            </button>
            <button onclick="window.backfillNext()"
                    class="bg-primary-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-primary-700 transition-colors">
                ${bf("next_button")}
            </button>
        </div>`);

    const list = document.getElementById("bf-session-list");
    list?.addEventListener("scroll", () => {
        const atBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 40;
        if (atBottom && backfill.hasMore && !backfill.loading) loadBackfillSessions(false);
    });
}

function renderReviewStep() {
    const rate = backfill.rateTouched && backfill.exchangeRate !== ""
        ? backfill.exchangeRate
        : currentRateFor(backfill.currencyId);
    const overridden = backfill.rateTouched && backfill.exchangeRate !== "";

    const listed = (ids) => [...ids].map((id) => {
        const s = backfill.sessions.find((x) => x.id === id);
        return `<li class="text-slate-600 dark:text-slate-300">${s ? `${s.date} · ${escapeHtml(s.class_title ?? "")}` : `#${id}`}</li>`;
    }).join("");

    OperationsUI.openModal(window.__('operations_ui.packages.assign_modal_title'), `
        ${renderStepper()}
        ${renderSummaryBar()}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="glass-card rounded-2xl p-4">
                <h5 class="text-xs font-bold text-emerald-600 uppercase mb-2">${bf("summary_attended")} (${backfill.attended.size})</h5>
                <ul class="text-xs space-y-1 max-h-40 overflow-y-auto">${listed(backfill.attended) || `<li class="text-slate-400">—</li>`}</ul>
            </div>
            <div class="glass-card rounded-2xl p-4">
                <h5 class="text-xs font-bold text-amber-600 uppercase mb-2">${bf("summary_missed")} (${backfill.missed.size})</h5>
                <ul class="text-xs space-y-1 max-h-40 overflow-y-auto">${listed(backfill.missed) || `<li class="text-slate-400">—</li>`}</ul>
            </div>
        </div>

        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 px-4 py-3 text-sm space-y-1 mt-4">
            <p><strong>${bf("purchased_at_label")}:</strong> ${backfill.purchasedAt}</p>
            <p><strong>${bf("exchange_rate_label")}:</strong> ${rate}
               <span class="text-xs ${overridden ? "text-amber-600" : "text-slate-400"}">(${overridden ? bf("rate_overridden") : bf("rate_current")})</span></p>
        </div>

        ${remainingCredits() > 0 ? `
            <div class="rounded-xl bg-sky-50 dark:bg-sky-900/20 border border-sky-200 px-4 py-3 text-xs text-sky-700 dark:text-sky-300 mt-4">
                ${bf("leftover_credits_notice")}
            </div>` : ""}

        <div id="bf-submit-error" class="hidden mt-4 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:text-rose-300 whitespace-pre-line"></div>

        <div class="flex justify-between pt-4">
            <button onclick="window.backfillGoToStep(3)"
                    class="px-6 py-2.5 rounded-xl font-bold text-sm bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                ${bf("back_button")}
            </button>
            <button id="bf-submit" onclick="window.backfillSubmit()"
                    class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-emerald-700 transition-colors disabled:opacity-50">
                ${bf("submit_button")}
            </button>
        </div>`);
}

async function loadBackfillSessions(replace = false) {
    if (backfill.loading) return;
    backfill.loading = true;

    try {
        const result = await OperationsAPI.getBackfillSessions({
            userId: backfill.userId,
            packageId: backfill.package.id,
            purchasedAt: backfill.purchasedAt,
            cursor: replace ? null : backfill.cursor,
        });

        const rows = result.data || [];
        backfill.sessions = replace ? rows : [...backfill.sessions, ...rows];
        backfill.cursor = result.meta?.next_cursor ?? null;
        backfill.hasMore = Boolean(result.meta?.has_more);
        backfill.window = result.meta?.window ?? null;
    } catch (e) {
        console.error("Failed to load backfill sessions:", e);
        OperationsUI.toast(e.message || bf("load_sessions_failed"), "error");
    } finally {
        backfill.loading = false;
        if (backfill.active && backfill.step === 3) renderSessionStep();
    }
}

// ------------------------------------------------------- historical: handlers

window.confirmPackageAssignment = (packageId) => {
    backfill.package = backfill.packages.find((p) => Number(p.id) === Number(packageId)) ?? null;

    if (! backfill.package) return;

    // Read the currency NOW. The per-card select lives in the grid, and openModal replaces
    // innerHTML wholesale — by the time Approve is pressed that element no longer exists.
    const selected = document.getElementById(`currency-${packageId}`)?.value;
    if (selected) backfill.currencyId = Number(selected);

    backfill.active = false;
    clearSelections();
    backfill.step = 2;
    renderConfirmStep();
};

window.toggleHistoricalEntry = (enabled) => {
    backfill.active = Boolean(enabled);
    clearSelections();
    renderConfirmStep();
};

window.backfillDateChanged = (value) => {
    backfill.purchasedAt = value;
    clearSelections();
};

window.backfillCurrencyChanged = (currencyId) => {
    backfill.currencyId = Number(currencyId);

    // Re-fill the default, but never clobber a figure the admin typed themselves (D-A03).
    if (!backfill.rateTouched) {
        const input = document.getElementById("bf-rate");
        if (input) input.placeholder = String(currentRateFor(backfill.currencyId));
    }
};

window.backfillRateEdited = (value) => {
    backfill.exchangeRate = value;
    backfill.rateTouched = value !== "";
};

window.backfillGoToStep = (step) => {
    backfill.step = step;

    if (step === 1) {
        // Cancel: nothing was written, so drop the historical branch too.
        backfill.active = false;
        backfill.package = null;
        clearSelections();
    }

    renderAssignModal();
};

window.backfillNext = async () => {
    if (backfill.step === 2) {
        // Unticked: this is an ordinary live sale and Approve submits it outright.
        if (! backfill.active) {
            await submitLiveAssignment();

            return;
        }

        if (! backfill.purchasedAt) {
            const box = document.getElementById("bf-step1-error");
            if (box) {
                box.textContent = bf("error_date_required");
                box.classList.remove("hidden");
            } else {
                OperationsUI.toast(bf("error_date_required"), "error");
            }

            return;
        }

        backfill.step = 3;
        renderSessionStep();
        await loadBackfillSessions(true);

        return;
    }

    if (backfill.step === 3) {
        backfill.step = 4;
        renderReviewStep();
    }
};

async function submitLiveAssignment() {
    const button = document.getElementById("bf-approve");

    if (button) {
        button.disabled = true;
        button.innerHTML = `<span class="btn-spinner"></span>${bf("submitting")}`;
    }

    try {
        await OperationsAPI.assignPackage(backfill.userId, backfill.package.id, backfill.currencyId);
        OperationsUI.toast(window.__('operations_ui.packages.assign_success'), "success");
        OperationsUI.closeModal();
        showClientDetails(backfill.userId);
    } catch (e) {
        const box = document.getElementById("bf-step1-error");
        if (box) {
            box.textContent = e.message;
            box.classList.remove("hidden");
        } else {
            OperationsUI.toast(e.message, "error");
        }
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = window.__('operations_ui.packages.assign_button');
        }
    }
}

window.backfillToggleSession = (sessionId, kind) => {
    const [target, other] = kind === "attended"
        ? [backfill.attended, backfill.missed]
        : [backfill.missed, backfill.attended];

    if (target.has(sessionId)) {
        target.delete(sessionId);
    } else {
        // One list or the other, never both — the server rejects an intersection, so make it
        // structurally impossible rather than catching it at submit.
        other.delete(sessionId);

        if (selectedCount() >= backfill.package.total_credits) {
            OperationsUI.toast(bf("error_credit_overflow_short"), "warning");
            return;
        }
        target.add(sessionId);
    }

    renderSessionStep();
};

window.backfillSubmit = async () => {
    if (backfill.submitting) return;

    const button = document.getElementById("bf-submit");
    const errorBox = document.getElementById("bf-submit-error");

    backfill.submitting = true;
    if (button) {
        button.disabled = true;
        button.innerHTML = `<span class="btn-spinner"></span>${bf("submitting")}`;
    }
    errorBox?.classList.add("hidden");

    try {
        await OperationsAPI.assignPackage(
            backfill.userId,
            backfill.package.id,
            backfill.currencyId,
            {
                purchased_at: backfill.purchasedAt,
                attended_session_ids: [...backfill.attended],
                missed_session_ids: [...backfill.missed],
                // A11: a fresh token per submission, and only ever on the historical path — its
                // absence is part of what marks a request as a live sale.
                idempotency_key: crypto.randomUUID(),
                ...(backfill.rateTouched && backfill.exchangeRate !== ""
                    ? { exchange_rate_snapshot: Number(backfill.exchangeRate) }
                    : {}),
            },
        );

        OperationsUI.toast(bf("success"), "success");
        OperationsUI.closeModal();
        showClientDetails(backfill.userId);
    } catch (e) {
        // The modal stays open with every selection intact — a rejection is something the admin
        // corrects and resends, and the server releases the idempotency key for exactly that.
        if (errorBox) {
            errorBox.textContent = e.message || bf("submit_failed");
            errorBox.classList.remove("hidden");
        } else {
            OperationsUI.toast(e.message || bf("submit_failed"), "error");
        }
    } finally {
        backfill.submitting = false;
        if (button) {
            button.disabled = false;
            button.textContent = bf("submit_button");
        }
    }
};

window.showPackageAssignment = showPackageAssignment;
window.handlePackageAssign = handlePackageAssign;
window.handleFreeze = handleFreeze;
window.handleUnfreeze = handleUnfreeze;
window.updatePackageAmount = window.updatePackageAmount;
