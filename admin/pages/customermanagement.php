<?php
$pageTitle = "Customer Management";

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
                <h2 class="page-title">Customers</h2>
                <p class="page-subtitle">Manage your customer database</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="exportBtn">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-primary" id="addCustomerBtn">
                    <i class="fas fa-plus"></i> Add Customer
                </button>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <div class="search-filter-section">
            <div class="search-row">
                <div class="form-group flex-grow">
                    <label class="form-label">Search Customers</label>
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, or location">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Sort By</label>
                    <select class="form-control" id="sortFilter">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-secondary" id="clearFilters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Customer Table -->
        <div class="table-container">
            <table class="data-table" id="customerTable">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact Info</th>
                        <th>Location</th>
                        <th>Date Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody">
                    <!-- Data loaded via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination loaded via JavaScript -->
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal" id="customerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Customer</h3>
            <button class="close-btn" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="customerForm" novalidate>
                <input type="hidden" id="customerId" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="firstName" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea class="form-control textarea" id="address" name="address" rows="3"></textarea>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city">
                    </div>
                     <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveBtn" form="customerForm">Save Customer</button>
        </div>
    </div>
</div>

<script>
class CustomerManager {
    constructor() {
        this.customers = [];
        this.filteredCustomers = [];
        this.currentPage = 1;
        this.customersPerPage = 10;
        this.editingCustomerId = null;
        // Correct API endpoint
        this.API_URL = '../api/customers.php'; 
    }

    async init() {
        this.bindEvents();
        await this.loadCustomers();
    }

    bindEvents() {
        document.getElementById('searchInput')?.addEventListener('input', () => this.filterCustomers());
        document.getElementById('sortFilter')?.addEventListener('change', () => this.filterCustomers());
        document.getElementById('clearFilters')?.addEventListener('click', () => this.clearFilters());

        document.getElementById('addCustomerBtn')?.addEventListener('click', () => this.openModal());
        document.getElementById('closeModal')?.addEventListener('click', () => this.closeModal());
        document.getElementById('cancelBtn')?.addEventListener('click', () => this.closeModal());
        document.getElementById('customerForm')?.addEventListener('submit', (e) => this.saveCustomer(e));

        // Added export button event listener
        document.getElementById('exportBtn')?.addEventListener('click', () => this.exportCustomers());

        document.getElementById('customerModal')?.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) this.closeModal();
        });
    }

    async fetchApi(url, options = {}) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                const errorResult = await response.json();
                throw new Error(errorResult.message || `HTTP Error: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API Fetch Error:', error);
            this.showNotification(error.message || 'Could not connect to the server.', 'error');
            return null;
        }
    }

    async loadCustomers() {
        const result = await this.fetchApi(`${this.API_URL}?action=all`);
        if (result && result.success) {
            this.customers = Array.isArray(result.data) ? result.data : [];
        } else {
            this.customers = [];
            this.showNotification(result?.message || 'Failed to load customer data.', 'error');
        }
        this.filterCustomers();
    }
    
    filterCustomers() {
        const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const sortFilter = document.getElementById('sortFilter')?.value || 'newest';

        this.filteredCustomers = this.customers.filter(customer => {
            const fullName = `${customer.first_name || ''} ${customer.last_name || ''}`.toLowerCase();
            const location = `${customer.city || ''} ${customer.country || ''}`.toLowerCase();
            return fullName.includes(searchTerm) ||
                   (customer.email?.toLowerCase() || '').includes(searchTerm) ||
                   location.includes(searchTerm);
        });

        this.filteredCustomers.sort((a, b) => {
            const aName = `${a.first_name || ''} ${a.last_name || ''}`.toLowerCase();
            const bName = `${b.first_name || ''} ${b.last_name || ''}`.toLowerCase();
            switch(sortFilter) {
                case 'oldest': return new Date(a.created_at) - new Date(b.created_at);
                case 'name_asc': return aName.localeCompare(bName);
                case 'name_desc': return bName.localeCompare(aName);
                case 'newest':
                default:
                    return new Date(b.created_at) - new Date(a.created_at);
            }
        });

        this.currentPage = 1;
        this.renderCustomers();
        this.renderPagination();
    }

    clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('sortFilter').value = 'newest';
        this.filterCustomers();
    }

    renderCustomers() {
        const tbody = document.getElementById('customerTableBody');
        const startIndex = (this.currentPage - 1) * this.customersPerPage;
        const customersToShow = this.filteredCustomers.slice(startIndex, startIndex + this.customersPerPage);

        if (customersToShow.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">
                <i class="fas fa-users"></i><h3>No Customers Found</h3>
                <p>Try adjusting your search or add a new customer.</p>
            </div></td></tr>`;
            return;
        }

        tbody.innerHTML = customersToShow.map(customer => {
            const fullName = `${customer.first_name || ''} ${customer.last_name || ''}`;
            const initials = `${customer.first_name?.charAt(0) || ''}${customer.last_name?.charAt(0) || ''}`.toUpperCase();
            const location = [customer.city, customer.country].filter(Boolean).join(', ') || 'N/A';
            const joinDate = new Date(customer.created_at).toLocaleDateString();

            return `
                <tr>
                    <td>
                        <div class="customer-info">
                            <div class="customer-avatar">${initials}</div>
                            <div class="customer-details">
                                <div class="customer-name">${fullName}</div>
                                <div class="customer-email">${customer.email}</div>
                            </div>
                        </div>
                    </td>
                    <td>${customer.phone || 'N/A'}</td>
                    <td class="location-info">${location}</td>
                    <td>${joinDate}</td>
                    <td>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-edit" onclick="customerManager.openModal(${customer.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-delete" onclick="customerManager.deleteCustomer(${customer.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    renderPagination() {
        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(this.filteredCustomers.length / this.customersPerPage);
        if (totalPages <= 1) { pagination.innerHTML = ''; return; }

        let html = '';
        if (this.currentPage > 1) html += `<button class="page-btn" onclick="customerManager.goToPage(${this.currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
        for (let i = 1; i <= totalPages; i++) html += `<button class="page-btn ${i === this.currentPage ? 'active' : ''}" onclick="customerManager.goToPage(${i})">${i}</button>`;
        if (this.currentPage < totalPages) html += `<button class="page-btn" onclick="customerManager.goToPage(${this.currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
        pagination.innerHTML = html;
    }

    goToPage(page) {
        this.currentPage = page;
        this.renderCustomers();
        this.renderPagination();
    }

    async openModal(customerId = null) {
        this.editingCustomerId = customerId;
        const form = document.getElementById('customerForm');
        const modalTitle = document.getElementById('modalTitle');
        form.reset();
        
        if (customerId) {
            modalTitle.textContent = 'Edit Customer';
            const result = await this.fetchApi(`${this.API_URL}?action=get&id=${customerId}`);
            if (result && result.success) {
                const customer = result.data;
                document.getElementById('firstName').value = customer.first_name;
                document.getElementById('lastName').value = customer.last_name;
                document.getElementById('email').value = customer.email;
                document.getElementById('phone').value = customer.phone || '';
                document.getElementById('address').value = customer.address || '';
                document.getElementById('city').value = customer.city || '';
                document.getElementById('country').value = customer.country || '';
            } else {
                this.showNotification('Could not load customer data.', 'error'); return;
            }
        } else {
            modalTitle.textContent = 'Add New Customer';
        }
        document.getElementById('customerModal').classList.add('active');
    }

    closeModal() {
        document.getElementById('customerModal').classList.remove('active');
        this.editingCustomerId = null;
    }

    async saveCustomer(e) {
        e.preventDefault();
        const form = document.getElementById('customerForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        const url = this.editingCustomerId 
            ? `${this.API_URL}?action=update&id=${this.editingCustomerId}`
            : `${this.API_URL}?action=create`;
            
        const method = this.editingCustomerId ? 'PUT' : 'POST';

        const result = await this.fetchApi(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (result && result.success) {
            this.showNotification(result.message || 'Customer saved successfully!', 'success');
            this.closeModal();
            this.loadCustomers();
        } else {
            this.showNotification(result.message || 'Failed to save customer.', 'error');
        }
    }

    async deleteCustomer(id) {
        if (!confirm('Are you sure you want to delete this customer? This may affect their order history.')) return;

        const result = await this.fetchApi(`${this.API_URL}?action=delete&id=${id}`, { method: 'DELETE' });
        
        if (result && result.success) {
            this.showNotification('Customer deleted successfully.', 'success');
            this.loadCustomers();
        } else {
            this.showNotification(result.message || 'Failed to delete customer.', 'error');
        }
    }
    
    exportCustomers() {
        if (this.filteredCustomers.length === 0) {
            this.showNotification('No customers to export.', 'error');
            return;
        }

        const csvContent = "data:text/csv;charset=utf-8," 
            + "ID,FirstName,LastName,Email,Phone,Address,City,Country,DateJoined\n"
            + this.filteredCustomers.map(c => 
                `${c.id},"${c.first_name}","${c.last_name}","${c.email}","${c.phone || ''}","${(c.address || '').replace(/"/g, '""')}","${c.city || ''}","${c.country || ''}",${c.created_at}`
            ).join("\n");
        
        const link = document.createElement("a");
        link.setAttribute("href", encodeURI(csvContent));
        link.setAttribute("download", "customers.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        this.showNotification('Customer data exported.', 'success');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.customerManager = new CustomerManager();
    window.customerManager.init();
});
</script>

</body>
</html>