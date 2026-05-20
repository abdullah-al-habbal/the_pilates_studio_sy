// public\js\operations\modules\clients.js
import { renderShimmerRows } from "./tabs.js";
import {
    showPackageAssignment,
    handleFreeze,
    handleUnfreeze,
} from "./packages.js";

let clientSearchTimeout = null;
let activeClientFilter = "";
let currentClientSearch = "";
let currentClientPage = 1;

function humanizeRemainingDays(daysTotal) {
    if (daysTotal == null || isNaN(daysTotal)) return "";
    const total = Math.round(Number(daysTotal));
    if (total <= 0) return "No days left";

    const months = Math.floor(total / 30);
    const remainingAfterMonths = total % 30;
    const weeks = Math.floor(remainingAfterMonths / 7);
    const days = remainingAfterMonths % 7;

    const parts = [];
    if (months > 0) parts.push(`${months} month${months !== 1 ? "s" : ""}`);
    if (weeks > 0) parts.push(`${weeks} week${weeks !== 1 ? "s" : ""}`);
    if (days > 0) parts.push(`${days} day${days !== 1 ? "s" : ""}`);

    if (parts.length === 0) parts.push(`${days} day${days !== 1 ? "s" : ""}`);

    return parts.join(", ") + " left";
}

export function applyClientFilter(pillEl) {
    activeClientFilter = pillEl.dataset.filter || "";

    document.querySelectorAll(".filter-pill").forEach((p) => {
        p.classList.remove("active-pill", "bg-primary-600", "text-white");
        p.classList.add(
            "bg-slate-100",
            "dark:bg-slate-800",
            "text-slate-600",
            "dark:text-slate-300",
        );
    });
    pillEl.classList.add("active-pill", "bg-primary-600", "text-white");
    pillEl.classList.remove(
        "bg-slate-100",
        "dark:bg-slate-800",
        "text-slate-600",
        "dark:text-slate-300",
    );

    renderClients(document.getElementById("client-search")?.value ?? "");
}

export function initClientsTab() {
    const searchInput = document.getElementById("client-search");
    if (!searchInput) return;

    searchInput.addEventListener("input", (e) => {
        clearTimeout(clientSearchTimeout);
        clientSearchTimeout = setTimeout(
            () => renderClients(e.target.value),
            300,
        );
    });

    renderClients();
}

export async function renderClients(search = "", page = 1) {
    currentClientSearch = search;
    currentClientPage = page;
    renderShimmerRows("client-table-body", 6, 6);
    const tbody = document.getElementById("client-table-body");

    try {
        const result = await OperationsAPI.getClients(
            search,
            page,
            activeClientFilter,
        );

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-12 text-slate-400">No clients found.</td>
                </tr>`;
            return;
        }

        tbody.innerHTML = result.data
            .map((user) => {
                const statusBadge = buildStatusBadge(user.status);
                const listPackage = user.frozen_package ?? user.active_package;
                const packageCell = listPackage
                    ? `<span class="text-sm font-medium">${listPackage.name}${user.frozen_package ? ' <span class="text-sky-600 text-xs">(frozen)</span>' : ''}</span>
                   <span class="text-xs text-slate-400 ml-1">(${listPackage.remaining_credits}/${listPackage.total_credits})</span>`
                    : '<span class="text-xs text-slate-400 italic">No package</span>';

                return `
                <tr class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                    <td class="px-6 py-4 font-medium">${user.fullname}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm">${user.phone_number}</td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4">${packageCell}</td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-medium">${user.sessions_attended}</span>
                        <span class="text-xs text-slate-400 ml-1">attended</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick="window.showClientDetails(${user.id})"
                                class="text-primary-600 hover:text-primary-700 font-bold text-sm btn-single-action">
                            Details
                        </button>
                    </td>
                </tr>`;
            })
            .join("");

        renderPagination(result.meta);
    } catch (e) {
        console.error("Failed to load clients:", e);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-12">
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-rose-500 font-bold">Error loading clients</span>
                        <p class="text-xs text-slate-400">${e.message}</p>
                        <button onclick="window.renderClients()" class="text-xs text-primary-600 underline">Try again</button>
                    </div>
                </td>
            </tr>`;
        OperationsUI.toast("Failed to load clients", "error");
    }
}

function buildStatusBadge(status) {
    const map = {
        active: "bg-emerald-100 text-emerald-700",
        frozen: "bg-sky-100 text-sky-700",
        deactivated: "bg-slate-100 text-slate-500",
    };
    const cls = map[status] ?? "bg-slate-100 text-slate-500";
    // fix: why the status always UNKNOWN?
    const label = status?.toUpperCase() ?? "UNKNOWN";
    return `<span class="px-2 py-1 rounded-full text-xs font-bold ${cls}">${label}</span>`;
}

function renderPagination(meta) {
    const container = document.getElementById("client-pagination");
    if (!container || !meta?.pagination) return;

    const p = meta.pagination;
    container.innerHTML = `
        <span class="text-xs text-slate-500 font-medium">
            Page ${p.current_page} of ${p.total_pages} (${p.total} clients)
        </span>
        <div class="flex gap-1">
            <button onclick="window.renderClients(document.getElementById('client-search')?.value ?? '', ${p.current_page - 1})"
                ${p.current_page === 1 ? "disabled" : ""}
                class="px-3 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg disabled:opacity-50">&larr;</button>
            <button onclick="window.renderClients(document.getElementById('client-search')?.value ?? '', ${p.current_page + 1})"
                ${p.current_page === p.total_pages ? "disabled" : ""}
                class="px-3 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg disabled:opacity-50">&rarr;</button>
        </div>`;
}

export async function showClientDetails(userId) {
    OperationsUI.openModal(
        "Client Workspace",
        `
        <div class="space-y-6">
            ${Array(3)
                .fill("")
                .map(
                    () => `
                <div class="glass-card rounded-2xl p-6 space-y-3">
                    <div class="shimmer-cell w-30" style="height:12px;"></div>
                    <div class="shimmer-cell w-50" style="height:24px;border-radius:6px;"></div>
                    <div class="shimmer-cell w-20" style="height:12px;"></div>
                </div>`,
                )
                .join("")}
        </div>`,
    );

    try {
        const result = await OperationsAPI.getClientDetails(userId);
        const user = result.data;

        if (!user) throw new Error("Empty response from server.");

        const currentPackage = user.frozen_package ?? user.active_package;
        const isFrozen = Boolean(user.frozen_package);

        const actionButton = isFrozen
            ? `<button onclick="window.handleUnfreeze(${user.frozen_package.id}, ${user.id})"
                       class="bg-primary-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:scale-105 transition-all btn-single-action">
                       🔓 Unfreeze Package
                   </button>`
            : user.active_package?.remaining_credits === 0
              ? `<button onclick="window.showPackageAssignment(${user.id})"
                        class="bg-primary-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:scale-105 transition-all btn-single-action">
                        + Assign New Package
                   </button>`
              : !user.active_package
                ? `<button onclick="window.showPackageAssignment(${user.id})"
                          class="bg-primary-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:scale-105 transition-all btn-single-action">
                          + Assign Package
                      </button>`
                : "";

        const remainingDaysRaw = currentPackage?.remaining_days;
        const daysLeftText = humanizeRemainingDays(remainingDaysRaw);
        const daysBadge =
            remainingDaysRaw != null
                ? `<span class="px-2 py-0.5 rounded-lg bg-gold-100 text-gold-700 text-xs font-bold">
                   ${daysLeftText}
               </span>`
                : currentPackage?.expires_at
                  ? '<span class="px-2 py-0.5 rounded-lg bg-rose-100 text-rose-700 text-xs font-bold">No days left</span>'
                  : "";

        const snap = user.activity_snapshot ?? {
            total_sessions_attended: 0,
            total_sessions_cancelled: 0,
        };

        const content = `
            <div class="space-y-8">
                <!-- Header -->
                <div class="flex items-start justify-between flex-wrap gap-4">
                    <div class="flex gap-4 items-center">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-2xl font-bold text-slate-400">
                            ${(user.fullname ?? "?").charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="text-2xl font-bold">${user.fullname}</h4>
                            <p class="text-slate-500">${user.phone_number} &bull; Member since ${user.member_since ?? "—"}</p>
                        </div>
                    </div>
                    ${actionButton}
                </div>

                <!-- Package + Activity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Current Package -->
                    <div class="glass-card rounded-2xl p-6 border-l-4 ${isFrozen ? "border-sky-500" : "border-gold-500"}">
                        <h5 class="text-xs font-bold text-slate-400 uppercase mb-4">${isFrozen ? "Frozen Package" : "Current Package"}</h5>
                        ${
                            currentPackage
                                ? `
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-xl font-bold">${currentPackage.name}</p>
                                        <p class="text-sm text-slate-500">Source: ${currentPackage.source_type ?? "—"}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-primary-600">
                                            ${currentPackage.remaining_credits} / ${currentPackage.total_credits}
                                        </p>
                                        <p class="text-xs font-bold text-slate-400">CREDITS</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-2">
                                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">
                                        Expires: ${currentPackage.expires_at ?? "Never"}
                                    </span>
                                    ${daysBadge}
                                </div>
                                <div class="flex gap-2 pt-2">
                                    ${
                                        isFrozen
                                            ? `<button onclick="window.handleUnfreeze(${currentPackage.id}, ${user.id})"
                                                class="flex-1 bg-emerald-100 text-emerald-700 py-2 rounded-lg font-bold text-xs uppercase tracking-wider hover:bg-emerald-200 transition-colors btn-single-action">
                                                Unfreeze Now
                                           </button>`
                                            : currentPackage
                                                    .remaining_credits === 0
                                              ? `<div class="space-y-3 pt-2">
                                                <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 px-4 py-3 text-sm text-amber-700 dark:text-amber-300">
                                                    ⚠ This package has been fully used (0 credits remaining).<br>
                                                    <strong>Assign a new package to continue.</strong>
                                                </div>
                                                <button onclick="window.showPackageAssignment(${user.id})"
                                                    class="w-full bg-primary-600 text-white py-2.5 rounded-lg font-bold text-sm hover:bg-primary-700 transition-colors btn-single-action">
                                                    + Assign Package
                                                </button>
                                            </div>`
                                              : (() => {
                                                    const paidAmount =
                                                        currentPackage
                                                            .paid_amount ??
                                                        null;
                                                    const currencyId =
                                                        currentPackage
                                                            .currency_id ??
                                                        null;
                                                    const refundBtnDisabled =
                                                        paidAmount === null;
                                                    const refundTitle =
                                                        refundBtnDisabled
                                                            ? 'title="Payment amount not recorded — refund unavailable"'
                                                            : "";
                                                    const refundCls =
                                                        refundBtnDisabled
                                                            ? "opacity-50 cursor-not-allowed"
                                                            : "hover:bg-rose-200";

                                                    const paidAmountJs =
                                                        paidAmount !== null
                                                            ? paidAmount
                                                            : "null";
                                                    const currencyIdJs =
                                                        currencyId !== null
                                                            ? currencyId
                                                            : "null";

                                                    return `
                                                <div class="flex flex-1 gap-2">
                                                    <button onclick="window.handleFreeze(${currentPackage.id}, ${user.id})"
                                                        class="flex-1 bg-amber-100 text-amber-700 py-2 rounded-lg font-bold text-xs uppercase tracking-wider hover:bg-amber-200 transition-colors btn-single-action">
                                                        Freeze
                                                    </button>
                                                    <button onclick="window.showRefundModal(${currentPackage.id}, ${paidAmountJs}, ${currencyIdJs}, ${user.id})"
                                                        ${refundBtnDisabled ? "disabled" : ""}
                                                        ${refundTitle}
                                                        class="flex-1 bg-rose-100 text-rose-700 py-2 rounded-lg font-bold text-xs uppercase tracking-wider ${refundCls} transition-colors btn-single-action">
                                                        Refund
                                                    </button>
                                                </div>`;
                                                })()
                                    }
                                </div>
                            </div>`
                                : `
                            <div class="flex flex-col items-center py-6 text-center">
                                <p class="text-slate-400 font-medium">No active package found.</p>
                                <p class="text-xs text-slate-400 mt-1">Client needs a new subscription.</p>
                            </div>`
                        }
                    </div>

                    <!-- Activity Snapshot -->
                    <div class="glass-card rounded-2xl p-6">
                        <h5 class="text-xs font-bold text-slate-400 uppercase mb-4">Activity Snapshot</h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                <p class="text-2xl font-bold">${snap.total_sessions_attended}</p>
                                <p class="text-xs text-slate-500 font-medium">Attended</p>
                            </div>
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                <p class="text-2xl font-bold text-rose-500">${snap.total_sessions_cancelled}</p>
                                <p class="text-xs text-slate-500 font-medium">Cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Store Purchases -->
                <div class="space-y-4">
                    <h5 class="text-xs font-bold text-slate-400 uppercase">Recent Store Purchases</h5>
                    <div class="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden">
                        ${
                            user.store_purchases &&
                            user.store_purchases.length > 0
                                ? `
                            <table class="w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    ${user.store_purchases
                                        .map(
                                            (o) => `
                                        <tr>
                                            <td class="px-4 py-3">${o.item_name}</td>
                                            <td class="px-4 py-3 text-slate-500">${o.quantity} unit(s)</td>
                                            <td class="px-4 py-3 font-bold">${OperationsUI.formatCurrency(o.total_price ?? 0)}</td>
                                            <td class="px-4 py-3 text-right text-xs text-slate-400">${o.ordered_at ?? ""}</td>
                                        </tr>`,
                                        )
                                        .join("")}
                                </tbody>
                            </table>`
                                : `
                            <p class="p-6 text-center text-slate-400 italic">No purchase history.</p>`
                        }
                    </div>
                </div>
            </div>`;

        OperationsUI.openModal("Client Workspace", content);
    } catch (e) {
        console.error("Failed to load client details:", e);
        OperationsUI.openModal(
            "Client Workspace",
            `
            <div class="flex flex-col items-center py-16 gap-4">
                <svg class="w-12 h-12 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-rose-500 font-bold">Failed to load client details</p>
                <p class="text-sm text-slate-400">${e.message}</p>
                <button onclick="OperationsUI.closeModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-sm font-medium">Close</button>
            </div>`,
        );
    }
}

export function showRefundModal(bookingId, maxAmount, currencyId, userId) {
    if (maxAmount === null || maxAmount === undefined || maxAmount <= 0) {
        OperationsUI.toast(
            "Refund unavailable: no payment amount was recorded for this booking.",
            "error",
        );
        return;
    }

    const currency = window.OperationsCurrencies?.find(
        (c) => c.id == currencyId,
    );
    const code = currency?.code || "USD";
    const decimals = currency?.decimal_places || 2;
    const amountStr = OperationsUI.formatCurrencyBlock(
        maxAmount,
        decimals,
        code,
    );

    const content = `
        <div class="space-y-4">
            <p class="text-sm text-slate-600">
                The original payment was <strong>${amountStr}</strong>.
                You may issue a partial refund or leave the field empty for a full refund.
            </p>
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 uppercase">Refund Amount (optional)</label>
                <input
                    type="number"
                    id="refund-amount"
                    placeholder="Leave empty for full refund (${amountStr})"
                    min="1"
                    step="1"
                    max="${maxAmount}"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none"
                >
                <p class="text-xs text-slate-400">
                    Minimum: 1 &bull; Maximum: ${amountStr}
                </p>
            </div>
            <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3 text-xs text-amber-700 dark:text-amber-300">
                ⚠ Confirming will <strong>cancel the subscription</strong> immediately.
            </div>
            <button
                onclick="window.submitRefund(${bookingId}, ${userId}, ${maxAmount})"
                class="w-full bg-rose-600 text-white py-3 rounded-xl font-bold text-sm hover:bg-rose-700 transition-colors btn-single-action">
                Confirm Refund &amp; Cancel Subscription
            </button>
        </div>`;
    OperationsUI.openModal("Refund Package", content);
}

export async function submitRefund(bookingId, userId, maxAmount) {
    const input = document.getElementById("refund-amount");
    const rawVal = input?.value?.trim();

    if (rawVal !== "" && rawVal !== null && rawVal !== undefined) {
        if (!/^[0-9]+$/.test(rawVal)) {
            OperationsUI.toast( 
                "Refund amount must be a whole number in the smallest currency unit.",
                "error",
            );
            return;
        }

        const parsed = parseInt(rawVal, 10);
        if (parsed < 1) {
            OperationsUI.toast(
                "Refund amount must be a positive whole number.",
                "error",
            );
            return;
        }
        if (parsed > maxAmount) {
            OperationsUI.toast(
                `Refund amount cannot exceed the paid amount (${maxAmount}).`,
                "error",
            );
            return;
        }
    }

    const amount = rawVal !== "" ? parseInt(rawVal, 10) : undefined;
    try {
        await OperationsAPI.refundBooking(bookingId, amount);
        OperationsUI.toast(
            "Refund processed and subscription cancelled successfully.",
            "success",
        );
        OperationsUI.closeModal();
        showClientDetails(userId);
        renderClients(currentClientSearch, currentClientPage);
    } catch (e) {
        OperationsUI.toast(e.message, "error");
    }
}

window.applyClientFilter = applyClientFilter;
window.renderClients = renderClients;
window.showClientDetails = showClientDetails;
window.showRefundModal = showRefundModal;
window.submitRefund = submitRefund;
