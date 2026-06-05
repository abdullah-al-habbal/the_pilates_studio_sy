// public/js/operations/api.js
const OperationsAPI = {
    async request(url, method = "GET", body = null, timeoutMs = 10_000) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), timeoutMs);

        const headers = {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        };

        const config = { method, headers, signal: controller.signal };
        if (body) config.body = JSON.stringify(body);

        try {
            const response = await fetch(url, config);
            clearTimeout(timer);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || "Something went wrong");
            }

            return data;
        } catch (error) {
            clearTimeout(timer);
            if (error.name === "AbortError") {
                throw new Error(
                    "Request timed out. The server took too long to respond.",
                );
            }
            console.error("API Error:", error);
            throw error;
        }
    },

    getClients(search = "", page = 1, filter = "", perPage = 15) {
        return this.request(
            `/admin/operations/clients?search=${encodeURIComponent(search)}&page=${page}&filter=${encodeURIComponent(filter)}&per_page=${perPage}`,
        );
    },

    sendNotification(payload) {
        return this.request('/admin/operations/notifications/send', 'POST', payload);
    },

    getClientDetails(userId) {
        return this.request(`/admin/operations/clients/${userId}/details`);
    },

    getPackages() {
        return this.request("/admin/operations/packages");
    },

    assignPackage(userId, packageId, currencyId) {
        return this.request(
            `/admin/operations/packages/${packageId}/assign`,
            "POST",
            {
                user_id: userId,
                currency_id: parseInt(currencyId, 10),
            },
        );
    },

    getStoreItems() {
        return this.request("/admin/operations/store/items");
    },

    placeOrder(customerId, merchandiseId, quantity, currencyId) {
        return this.request("/admin/operations/store/orders", "POST", {
            customer_id: customerId,
            merchandise_id: merchandiseId,
            quantity: parseInt(quantity, 10),
            currency_id: parseInt(currencyId, 10),
        });
    },

    storeWalkInOrder(
        merchandiseId,
        quantity,
        currencyId,
        fullname,
        phoneNumber,
        email = null,
    ) {
        return this.request("/admin/operations/store/walk-in-order", "POST", {
            merchandise_id: merchandiseId,
            quantity: parseInt(quantity, 10),
            currency_id: parseInt(currencyId, 10),
            fullname,
            phone_number: phoneNumber,
            email,
        });
    },

    getDailyBalance(date = "", currencies = [], convertToBase = false) {
        let url = `/admin/operations/finance/daily?date=${date}`;
        if (convertToBase) {
            url += "&convertToBase=1";
        }
        if (currencies && currencies.length > 0) {
            currencies.forEach((c) => {
                url += `&currencies[]=${encodeURIComponent(c)}`;
            });
        }
        return this.request(url);
    },

    recordExpense(data) {
        return this.request("/admin/operations/finance/expenses", "POST", data);
    },

    freezeBooking(bookingId) {
        return this.request(
            `/admin/operations/bookings/${bookingId}/freeze`,
            "POST",
        );
    },

    unfreezeBooking(bookingId) {
        return this.request(
            `/admin/operations/bookings/${bookingId}/unfreeze`,
            "POST",
        );
    },

    refundBooking(bookingId, amount) {
        const parsedAmount =
            amount == null || amount === ""
                ? null
                : parseInt(String(amount), 10);
        return this.request(
            `/admin/operations/bookings/${bookingId}/refund`,
            "POST",
            {
                amount: Number.isNaN(parsedAmount) ? null : parsedAmount,
            },
        );
    },

    updatePackage(packageId, data) {
        return this.request(`/admin/operations/packages/${packageId}`, "PUT", {
            name: data.name,
            total_credits: parseInt(data.total_credits, 10),
            validity_days: data.validity_days
                ? parseInt(data.validity_days, 10)
                : null,
            currency_id: parseInt(data.currency_id, 10),
            amount: parseInt(data.amount, 10),
        });
    },

    deletePackage(packageId) {
        return this.request(
            `/admin/operations/packages/${packageId}`,
            "DELETE",
        );
    },

    createPackage(data) {
        return this.request("/admin/operations/packages", "POST", {
            name: data.name,
            total_credits: parseInt(data.total_credits, 10),
            validity_days: data.validity_days
                ? parseInt(data.validity_days, 10)
                : null,
            currency_id: parseInt(data.currency_id, 10),
            amount: parseInt(data.amount, 10),
        });
    },

    getPendingExpenses() {
        return this.request("/admin/operations/approvals/pending");
    },

    approveExpense(expenseId) {
        return this.request(`/admin/operations/approvals/${expenseId}/approve`, "POST");
    },

    rejectExpense(expenseId, reason) {
        return this.request(`/admin/operations/approvals/${expenseId}/reject`, "POST", {
            rejection_reason: reason,
        });
    },
};
