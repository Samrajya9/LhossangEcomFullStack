<?php
$pageTitle = "Admin Management";
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH .'/utils/authFunctions.php';

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
                <h2 class="page-title">Admins</h2>
                <p class="page-subtitle">Manage your admin and staff users</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" id="addAdminBtn">
                    <i class="fas fa-plus"></i> Add Admin
                </button>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <div class="search-filter-section">
            <div class="search-row">
                <div class="form-group flex-grow">
                    <label class="form-label">Search Admins</label>
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by username or email">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Sort By</label>
                    <select class="form-control" id="sortFilter">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="username_asc">Username (A-Z)</option>
                        <option value="username_desc">Username (Z-A)</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-secondary" id="clearFilters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Admin Table -->
        <div class="table-container">
            <table class="data-table" id="adminTable">
                <thead>
                    <tr>
                        <th>Admin ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="adminTableBody">
                    <!-- Data will be loaded via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination will be loaded via JavaScript -->
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</div>

<!-- Admin Modal Component -->
<div class="modal" id="adminModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Admin</h3>
            <button class="close-btn" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="adminForm" novalidate>
                <input type="hidden" id="adminId" name="id">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-control" id="adminUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" id="adminEmail" name="email" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" id="passwordLabel">Password *</label>
                        <input type="password" class="form-control" id="adminPassword" name="password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="confirmPasswordLabel">Confirm Password *</label>
                        <input type="password" class="form-control" id="adminConfirmPassword" required minlength="8">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveBtn" form="adminForm">Save Admin</button>
        </div>
    </div>
</div>

<script>
class AdminManager {
    constructor() {
        this.admins = [];
        this.filteredAdmins = [];
        this.currentPage = 1;
        this.adminsPerPage = 8;
        this.editingAdminId = null;
        this.API_URL = '<?=BASE_URL?>/api/admins.php'; 
    }

    async init() {
        this.bindEvents();
        await this.loadAdmins();
    }

    bindEvents() {
        document.getElementById('searchInput')?.addEventListener('input', () => this.filterAdmins());
        document.getElementById('sortFilter')?.addEventListener('change', () => this.filterAdmins());
        document.getElementById('clearFilters')?.addEventListener('click', () => this.clearFilters());

        document.getElementById('addAdminBtn')?.addEventListener('click', () => this.openModal());
        document.getElementById('closeModal')?.addEventListener('click', () => this.closeModal());
        document.getElementById('cancelBtn')?.addEventListener('click', () => this.closeModal());
        document.getElementById('adminForm')?.addEventListener('submit', (e) => this.saveAdmin(e));
        
        document.getElementById('adminModal')?.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) this.closeModal();
        });
    }

    async fetchApi(url, options = {}) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP Error: ${response.statusText}`);
            return await response.json();
        } catch (error) {
            console.error('API Fetch Error:', error);
            this.showNotification('Could not connect to the server.', 'error');
            return null;
        }
    }

    async loadAdmins() {
        const result = await this.fetchApi(`${this.API_URL}?action=all`);
        if (result && result.success) {
            this.admins = Array.isArray(result.data) ? result.data : [];
        } else {
            this.admins = [];
            this.showNotification(result?.message || 'Failed to load admin data.', 'error');
        }
        this.filterAdmins();
    }
    
    filterAdmins() {
        const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const sortFilter = document.getElementById('sortFilter')?.value || 'newest';

        this.filteredAdmins = this.admins.filter(admin => 
            (admin.username?.toLowerCase() || '').includes(searchTerm) || 
            (admin.email?.toLowerCase() || '').includes(searchTerm)
        );

        this.filteredAdmins.sort((a, b) => {
            const aUsername = (a.username || '').toLowerCase();
            const bUsername = (b.username || '').toLowerCase();
            switch(sortFilter) {
                case 'oldest': return new Date(a.created_at) - new Date(b.created_at);
                case 'username_asc': return aUsername.localeCompare(bUsername);
                case 'username_desc': return bUsername.localeCompare(aUsername);
                case 'newest':
                default:
                    return new Date(b.created_at) - new Date(a.created_at);
            }
        });

        this.currentPage = 1;
        this.renderAdmins();
        this.renderPagination();
    }

    clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('sortFilter').value = 'newest';
        this.filterAdmins();
    }

    renderAdmins() {
        const tbody = document.getElementById('adminTableBody');
        const startIndex = (this.currentPage - 1) * this.adminsPerPage;
        const adminsToShow = this.filteredAdmins.slice(startIndex, startIndex + this.adminsPerPage);

        if (adminsToShow.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">
                <i class="fas fa-user-shield"></i><h3>No Admins Found</h3>
                <p>Try adjusting your search or add a new admin.</p>
            </div></td></tr>`;
            return;
        }

        tbody.innerHTML = adminsToShow.map(admin => `
            <tr>
                <td><strong>#${admin.id}</strong></td>
                <td>${admin.username}</td>
                <td>${admin.email}</td>
                <td>${new Date(admin.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-sm btn-edit" onclick="adminManager.openModal(${admin.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-delete" onclick="adminManager.deleteAdmin(${admin.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderPagination() {
        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(this.filteredAdmins.length / this.adminsPerPage);
        if (totalPages <= 1) { pagination.innerHTML = ''; return; }

        let html = '';
        if (this.currentPage > 1) html += `<button class="page-btn" onclick="adminManager.goToPage(${this.currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
        for (let i = 1; i <= totalPages; i++) html += `<button class="page-btn ${i === this.currentPage ? 'active' : ''}" onclick="adminManager.goToPage(${i})">${i}</button>`;
        if (this.currentPage < totalPages) html += `<button class="page-btn" onclick="adminManager.goToPage(${this.currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
        pagination.innerHTML = html;
    }

    goToPage(page) {
        this.currentPage = page;
        this.renderAdmins();
        this.renderPagination();
    }

    async openModal(adminId = null) {
        this.editingAdminId = adminId;
        const form = document.getElementById('adminForm');
        const modalTitle = document.getElementById('modalTitle');
        const passwordField = document.getElementById('adminPassword');
        const confirmPasswordField = document.getElementById('adminConfirmPassword');
        const passwordLabel = document.getElementById('passwordLabel');

        form.reset();
        
        if (adminId) {
            modalTitle.textContent = 'Edit Admin';
            passwordField.required = false;
            confirmPasswordField.required = false;
            passwordLabel.textContent = 'New Password (optional)';
            
            const result = await this.fetchApi(`${this.API_URL}?action=get&id=${adminId}`);
            if (result && result.success) {
                const admin = result.data;
                document.getElementById('adminUsername').value = admin.username;
                document.getElementById('adminEmail').value = admin.email;
            } else {
                this.showNotification('Could not load admin data.', 'error'); return;
            }
        } else {
            modalTitle.textContent = 'Add New Admin';
            passwordField.required = true;
            confirmPasswordField.required = true;
            passwordLabel.textContent = 'Password *';
        }
        document.getElementById('adminModal').classList.add('active');
    }

    closeModal() {
        document.getElementById('adminModal').classList.remove('active');
        this.editingAdminId = null;
    }

    async saveAdmin(e) {
        e.preventDefault();
        const form = document.getElementById('adminForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        if (data.password !== document.getElementById('adminConfirmPassword').value) {
            this.showNotification('Passwords do not match.', 'error'); return;
        }
        
        if (this.editingAdminId && !data.password) delete data.password;

        const url = this.editingAdminId 
            ? `${this.API_URL}?action=update&id=${this.editingAdminId}`
            : `${this.API_URL}?action=create`;
            
        const method = this.editingAdminId ? 'PUT' : 'POST';

        const result = await this.fetchApi(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (result && result.success) {
            this.showNotification(result.message || 'Admin saved successfully!', 'success');
            this.closeModal();
            this.loadAdmins();
        } else {
            this.showNotification(result.error || 'Failed to save admin.', 'error');
        }
    }

    async deleteAdmin(id) {
        if (!confirm('Are you sure you want to delete this admin?')) return;

        const result = await this.fetchApi(`${this.API_URL}?action=delete&id=${id}`, { method: 'DELETE' });
        
        if (result && result.success) {
            this.showNotification('Admin deleted successfully.', 'success');
            this.loadAdmins();
        } else {
            this.showNotification(result.error || 'Failed to delete admin.', 'error');
        }
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
    window.adminManager = new AdminManager();
    window.adminManager.init();
});
</script>

</body>
</html>