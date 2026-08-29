// public/js/operations/modules/create-client.js
//
// "Add new client" modal for the Clients & Packages tab. Creates a user with role `customer`
// via POST /admin/operations/clients.

const t = (key) => window.__(`operations_ui.clients.${key}`);

function field({ id, label, type = "text", required = false, hint = "", attrs = "" }) {
    return `
        <div>
            <label for="${id}" class="block text-xs font-bold text-slate-400 uppercase mb-2">
                ${label}${required ? ' <span class="text-rose-500">*</span>' : ""}
            </label>
            <input type="${type}" id="${id}" ${attrs}
                   class="w-full rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-transparent px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition-all">
            ${hint ? `<p class="text-xs text-slate-400 mt-1">${hint}</p>` : ""}
            <p class="hidden text-xs text-rose-500 mt-1" data-error-for="${id}"></p>
        </div>`;
}

export function showCreateClient() {
    const today = new Date().toISOString().slice(0, 10);

    OperationsUI.openModal(t("add_client_title"), `
        <form id="create-client-form" class="space-y-5" onsubmit="return false;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${field({ id: "cc-fullname", label: t("field_fullname"), required: true })}
                ${field({ id: "cc-phone", label: t("field_phone"), required: true, attrs: 'inputmode="tel"' })}
                ${field({ id: "cc-email", label: t("field_email"), type: "email" })}
                ${field({ id: "cc-dob", label: t("field_date_of_birth"), type: "date", attrs: `max="${today}"` })}
            </div>

            ${field({
                id: "cc-password",
                label: t("field_password"),
                type: "text",
                hint: t("password_hint"),
            })}

            <div id="cc-error" class="hidden rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:text-rose-300"></div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="OperationsUI.closeModal()"
                        class="px-6 py-2.5 rounded-xl font-bold text-sm bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    ${t("cancel")}
                </button>
                <button type="button" id="cc-submit" onclick="window.submitCreateClient()"
                        class="bg-primary-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-primary-700 transition-colors disabled:opacity-50">
                    ${t("create_client")}
                </button>
            </div>
        </form>`);

    document.getElementById("cc-fullname")?.focus();
}

function clearErrors() {
    document.querySelectorAll("[data-error-for]").forEach((el) => {
        el.textContent = "";
        el.classList.add("hidden");
    });
    const box = document.getElementById("cc-error");
    box?.classList.add("hidden");
}

/** Laravel returns { errors: { field: [msg] } } on 422 — pin each message to its own input. */
function showFieldErrors(errors) {
    const map = {
        fullname: "cc-fullname",
        phone_number: "cc-phone",
        email: "cc-email",
        date_of_birth: "cc-dob",
        password: "cc-password",
    };

    let shown = false;

    Object.entries(errors || {}).forEach(([field, messages]) => {
        const el = document.querySelector(`[data-error-for="${map[field]}"]`);
        if (!el) return;
        el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        el.classList.remove("hidden");
        shown = true;
    });

    return shown;
}

window.submitCreateClient = async () => {
    const button = document.getElementById("cc-submit");
    const value = (id) => document.getElementById(id)?.value.trim() ?? "";

    clearErrors();

    const payload = {
        fullname: value("cc-fullname"),
        phone_number: value("cc-phone"),
        email: value("cc-email") || null,
        date_of_birth: value("cc-dob") || null,
        password: value("cc-password") || null,
    };

    if (!payload.fullname || !payload.phone_number) {
        showFieldErrors({
            ...(payload.fullname ? {} : { fullname: [t("error_required")] }),
            ...(payload.phone_number ? {} : { phone_number: [t("error_required")] }),
        });
        return;
    }

    button.disabled = true;
    const original = button.textContent;
    button.innerHTML = `<span class="btn-spinner"></span>${t("creating")}`;

    try {
        await OperationsAPI.createClient(payload);

        OperationsUI.toast(t("client_created"), "success");
        OperationsUI.closeModal();

        // Re-run the current search so the new client shows without a page reload.
        if (typeof window.renderClients === "function") {
            window.renderClients(document.getElementById("client-search")?.value ?? "");
        }
    } catch (e) {
        // Prefer the per-field bag so a duplicate phone lands under the phone input rather than
        // in a generic banner; fall back to the sentence when the server sent no field errors.
        if (!showFieldErrors(e.errors)) {
            const box = document.getElementById("cc-error");
            if (box) {
                box.textContent = e.message || t("create_client_failed");
                box.classList.remove("hidden");
            } else {
                OperationsUI.toast(e.message || t("create_client_failed"), "error");
            }
        }
    } finally {
        button.disabled = false;
        button.textContent = original;
    }
};

window.showCreateClient = showCreateClient;
