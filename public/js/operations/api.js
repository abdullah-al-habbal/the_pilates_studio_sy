/**
 * API Wrapper for Operations Hub
 */
const OperationsAPI = {
    async request(url, method = 'GET', body = null) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        };

        const config = {
            method,
            headers,
        };

        if (body) {
            config.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Something went wrong');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // Clients
    getClients(search = '', page = 1) {
        return this.request(`/admin/operations/clients?search=${encodeURIComponent(search)}&page=${page}`);
    },

    getClientDetails(userId) {
        return this.request(`/admin/operations/clients/${userId}/details`);
    },

    // Packages
    getPackages() {
        return this.request('/admin/operations/packages');
    },

    assignPackage(userId, packageId) {
        return this.request(`/admin/operations/packages/${packageId}/assign`, 'POST', { user_id: userId });
    },

    // Store
    getStoreItems() {
        return this.request('/admin/operations/store/items');
    },

    placeOrder(customerId, merchandiseId, quantity) {
        return this.request('/admin/operations/store/orders', 'POST', {
            customer_id: customerId,
            merchandise_id: merchandiseId,
            quantity
        });
    },

    // Finance
    getDailyBalance(date = '') {
        return this.request(`/admin/operations/finance/daily?date=${date}`);
    },

    recordExpense(data) {
        return this.request('/admin/operations/finance/expenses', 'POST', data);
    },

    // Freezes
    freezeBooking(bookingId) {
        return this.request(`/admin/operations/bookings/${bookingId}/freeze`, 'POST');
    },

    unfreezeBooking(bookingId) {
        return this.request(`/admin/operations/bookings/${bookingId}/unfreeze`, 'POST');
    }
};
