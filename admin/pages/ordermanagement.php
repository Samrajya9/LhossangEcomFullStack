<?php 
// file_full_path = /opt/lampp/htdocs/infinityAdmin/pages/ordermanagement.php
$pageTitle = "Order Management";

require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/utils/authFunctions.php';

if(!isLoggedIn() || !isAdmin()){
    header("Location: signin.php");
    exit();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?> 

<div class="main-content">
    <?php include '../includes/TopBar.php'; ?> 

    <div class="content-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2 class="page-title">Orders</h2>
                <p class="page-subtitle">Track and manage all customer orders</p>
            </div>
        </div>

        <!-- Stats Container -->
        <div class="stats-container" id="statsContainer">
            <div class="stat-card"><h3>Total Orders</h3><p class="loading">...</p></div>
            <div class="stat-card"><h3>Total Revenue</h3><p class="loading">...</p></div>
            <div class="stat-card"><h3>Pending</h3><p class="loading">...</p></div>
            <div class="stat-card"><h3>Delivered</h3><p class="loading">...</p></div>
        </div>

        <!-- Filter and Search Section -->
        <div class="search-filter-section">
            <div class="search-row">
                <div class="form-group flex-grow">
                    <label class="form-label">Search Orders</label>
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by Order ID, Customer Name or Email">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-secondary" id="clearFilters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <!-- Orders will be loaded here dynamically -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination will be loaded here -->
        </div>
    </div>

     <!-- Footer -->
     <?php include '../includes/footer.php'; ?>
</div>

<!-- Order Details Modal -->
<div class="modal" id="orderDetailsModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Order Details</h3>
            <button class="close-btn" id="closeModal">&times;</button>
        </div>
        <div class="modal-body" id="modalBodyContent">
            <!-- Order details will be injected here -->
        </div>
        <div class="modal-footer" id="modalFooterContent">
             <button type="button" class="btn btn-secondary" id="cancelBtn">Close</button>
        </div>
    </div>
</div>

<script>
class OrderManager {
    constructor() {
        this.API_URL = '../api/orders.php';
        this.currentPage = 1;
        this.ordersPerPage = 10;
    }

    init() {
        this.bindEvents();
        this.fetchStats();
        this.fetchOrders();
    }

    bindEvents() {
        document.getElementById('searchInput').addEventListener('input', () => this.debounce(this.filterOrders, 500)());
        document.getElementById('statusFilter').addEventListener('change', () => this.filterOrders());
        document.getElementById('clearFilters').addEventListener('click', () => this.clearFilters());
        document.getElementById('closeModal').addEventListener('click', () => this.closeModal());
        document.getElementById('cancelBtn').addEventListener('click', () => this.closeModal());
    }
    
    // Debounce to prevent API calls on every keystroke
    debounce(func, delay) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    async fetchApi(url, options = {}) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            this.showNotification('Failed to communicate with the server.', 'error');
            return null;
        }
    }

    async fetchStats() {
        const result = await this.fetchApi(`${this.API_URL}?action=statistics`);
        const statsContainer = document.getElementById('statsContainer');
        if (result && result.success && result.data) {
            const stats = result.data;
            statsContainer.innerHTML = `
                <div class="stat-card"><h3>Total Orders</h3><p>${stats.total_orders || 0}</p></div>
                <div class="stat-card"><h3>Total Revenue</h3><p>$${parseFloat(stats.total_revenue || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p></div>
                <div class="stat-card"><h3>Pending</h3><p>${stats.pending_orders || 0}</p></div>
                <div class="stat-card"><h3>Delivered</h3><p>${stats.delivered_orders || 0}</p></div>
            `;
        }
    }

    async fetchOrders() {
        const searchTerm = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const offset = (this.currentPage - 1) * this.ordersPerPage;

        let url = `${this.API_URL}?action=${searchTerm ? 'search' : 'all'}&limit=${this.ordersPerPage}&offset=${offset}`;
        if (searchTerm) url += `&q=${encodeURIComponent(searchTerm)}`;
        if (status) url += `&status=${status}`;

        const countUrl = `${this.API_URL}?action=count${status ? `&status=${status}` : ''}${searchTerm ? `&q=${encodeURIComponent(searchTerm)}` : ''}`;

        // Fetch orders and total count concurrently
        const [ordersResult, countResult] = await Promise.all([
            this.fetchApi(url),
            this.fetchApi(countUrl)
        ]);

        if (ordersResult && ordersResult.success) {
            this.renderTable(ordersResult.data);
            if(countResult && countResult.success) {
                this.renderPagination(countResult.data.count);
            }
        } else {
             document.getElementById('ordersTableBody').innerHTML = '<tr><td colspan="6">Could not load orders.</td></tr>';
        }
    }

    filterOrders() {
        this.currentPage = 1;
        this.fetchOrders();
    }

    clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        this.filterOrders();
    }
    
    renderTable(orders) {
        const tableBody = document.getElementById('ordersTableBody');
        if (!orders || orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No orders found.</td></tr>';
            return;
        }

        tableBody.innerHTML = orders.map(order => {
            const statusClass = order.status.toLowerCase();
            return `
                <tr>
                    <td><strong>#${order.id}</strong></td>
                    <td>${order.customer_name || 'N/A'}</td>
                    <td>${new Date(order.order_date).toLocaleDateString()}</td>
                    <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td><span class="status-badge status-${statusClass}">${order.status}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-view" onclick="orderManager.viewOrder(${order.id})"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-delete" onclick="orderManager.deleteOrder(${order.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    renderPagination(totalOrders) {
        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(totalOrders / this.ordersPerPage);

        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let paginationHTML = '';
        if (this.currentPage > 1) {
            paginationHTML += `<button class="page-btn" onclick="orderManager.goToPage(${this.currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            paginationHTML += `<button class="page-btn ${i === this.currentPage ? 'active' : ''}" onclick="orderManager.goToPage(${i})">${i}</button>`;
        }

        if (this.currentPage < totalPages) {
            paginationHTML += `<button class="page-btn" onclick="orderManager.goToPage(${this.currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
        }
        pagination.innerHTML = paginationHTML;
    }

    goToPage(page) {
        this.currentPage = page;
        this.fetchOrders();
    }

    async viewOrder(orderId) {
        const result = await this.fetchApi(`${this.API_URL}?action=details&id=${orderId}`);
        if (result && result.success) {
            this.populateDetailsModal(result.data);
            this.openModal();
        }
    }

    populateDetailsModal(data) {
        const { order, items } = data;
        const modalBody = document.getElementById('modalBodyContent');
        document.getElementById('modalTitle').innerText = `Order #${order.id}`;

        const itemsHtml = items.map(item => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="../${item.product_image || 'assets/images/placeholder.png'}" style="width:40px;height:40px;border-radius:4px;margin-right:10px;object-fit:cover;" alt="${item.product_name}">
                        <span>${item.product_name}</span>
                    </div>
                </td>
                <td>$${parseFloat(item.price).toFixed(2)}</td>
                <td>${item.quantity}</td>
                <td class="text-right">$${(item.price * item.quantity).toFixed(2)}</td>
            </tr>
        `).join('');

        const statusOptions = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        const statusDropdown = `
            <select id="statusUpdater" class="form-control" onchange="orderManager.updateStatus(${order.id}, this.value)">
                ${statusOptions.map(s => `<option value="${s}" ${order.status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`).join('')}
            </select>
        `;

        modalBody.innerHTML = `
            <div class="order-details-grid">
                <div class="detail-card">
                    <h4>Customer Details</h4>
                    <p><strong>Name:</strong> ${order.customer_name}</p>
                    <p><strong>Email:</strong> ${order.customer_email}</p>
                    <p><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</p>
                </div>
                 <div class="detail-card">
                    <h4>Order Summary</h4>
                    <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                    <p><strong>Grand Total:</strong> <span class="text-success">$${parseFloat(order.total_amount).toFixed(2)}</span></p>
                    <div class="d-flex align-items-center"><strong>Status:</strong> &nbsp; ${statusDropdown}</div>
                </div>
                <div class="detail-card full-width">
                     <h4>Shipping Address</h4>
                     <p>${order.shipping_address ? order.shipping_address.replace(/\n/g, '<br>') : 'Not specified.'}</p>
                </div>
                <div class="detail-card full-width">
                    <h4>Order Items (${items.length})</h4>
                    <div class="table-container">
                        <table>
                            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-right">Subtotal</th></tr></thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    async updateStatus(orderId, newStatus) {
        if (!confirm(`Are you sure you want to change the status to "${newStatus}"?`)) {
            // If user cancels, revert the dropdown
            this.viewOrder(orderId); 
            return;
        }
        
        const result = await this.fetchApi(`${this.API_URL}?action=update-status&id=${orderId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        });

        if (result && result.success) {
            this.showNotification('Order status updated!', 'success');
            this.closeModal();
            this.fetchOrders();
            this.fetchStats(); // Refresh stats as well
        } else {
            this.showNotification(result.message || 'Failed to update status.', 'error');
        }
    }

    async deleteOrder(orderId) {
        if (confirm('Are you sure you want to permanently delete this order? This action cannot be undone.')) {
            const result = await this.fetchApi(`${this.API_URL}?action=delete&id=${orderId}`, { method: 'DELETE' });
            if (result && result.success) {
                this.showNotification('Order deleted successfully.', 'success');
                this.fetchOrders();
                this.fetchStats();
            } else {
                 this.showNotification(result.message || 'Failed to delete order.', 'error');
            }
        }
    }

    openModal() { document.getElementById('orderDetailsModal').classList.add('active'); }
    closeModal() { document.getElementById('orderDetailsModal').classList.remove('active'); }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 4000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.orderManager = new OrderManager();
    window.orderManager.init();
});
</script>

