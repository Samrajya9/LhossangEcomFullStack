<?php 
// file_full_path = /opt/lampp/htdocs/infinityAdmin/pages/productManagement.php

require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/utils/authFunctions.php';


if(!isLoggedIn() || !isAdmin()){
    header("Location: signin.php");
    exit();
}
$pageTitle ="Product Management" ;
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?> 

<!-- Main Content -->
<div class="main-content">
    <?php include '../includes/TopBar.php'; ?> 

    <!-- Page Content Container -->
    <div class="content-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2 class="page-title">Products</h2>
                <p class="page-subtitle">Manage your product inventory</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="exportBtn">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-primary" id="addProductBtn">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <div class="search-row">
                <div class="form-group">
                    <label class="form-label">Search Products</label>
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name or description">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control" id="categoryFilter">
                        <option value="">All Categories</option>
                        <!-- Categories will be loaded dynamically -->
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-secondary" id="clearFilters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
            <!-- Products will be loaded here -->
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination will be loaded here -->
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

</div>

<!-- Add/Edit Product Modal -->
<div class="modal" id="productModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Product</h3>
            <button class="close-btn" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="productId" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Product Name *</label>
                        <input type="text" class="form-control" id="productName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select class="form-control" id="productCategory" name="category_id" required>
                            <!-- Categories will be loaded dynamically -->
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price *</label>
                        <input type="number" class="form-control" id="productPrice" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock Quantity *</label>
                        <input type="number" class="form-control" id="productStock" name="stock_quantity" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="productStatus" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control textarea" id="productDescription" name="description" placeholder="Enter product description"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Product Image</label>
                    <div class="image-upload" id="imageUpload">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload or drag & drop</p>
                        <small>PNG, JPG up to 5MB</small>
                    </div>
                    <input type="file" id="productImage" name="image" accept="images/*" style="display: none;">
                    <div id="imagePreview" class="image-preview"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveBtn" form="productForm">Save Product</button>
        </div>
    </div>
</div>

<script>
class ProductManager {
    constructor() {
        this.products = [];
        this.currentPage = 1;
        this.productsPerPage = 6;
        this.editingProductId = null;
        this.API_URL = '../api/products.php';
        this.CATEGORIES_API_URL = '../api/categories.php'; 
    }

    async init() {
        this.bindEvents();
        await this.loadCategories();
        await this.fetchProducts();
    }
    
    bindEvents() {
        document.getElementById('searchInput').addEventListener('input', () => this.debounce(this.filterProducts, 500)());
        document.getElementById('categoryFilter').addEventListener('change', () => this.filterProducts());
        document.getElementById('statusFilter').addEventListener('change', () => this.filterProducts());
        document.getElementById('clearFilters').addEventListener('click', () => this.clearFilters());

        document.getElementById('addProductBtn').addEventListener('click', () => this.openModal());
        document.getElementById('closeModal').addEventListener('click', () => this.closeModal());
        document.getElementById('cancelBtn').addEventListener('click', () => this.closeModal());
        document.getElementById('productForm').addEventListener('submit', (e) => this.saveProduct(e));

        document.getElementById('imageUpload').addEventListener('click', () => document.getElementById('productImage').click());
        document.getElementById('productImage').addEventListener('change', (e) => this.handleImageUpload(e));
        document.getElementById('exportBtn').addEventListener('click', () => this.exportProducts());
    }

    debounce(func, delay) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    async fetchProducts() {
        const searchTerm = document.getElementById('searchInput').value;
        const categoryId = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;

        let url = `${this.API_URL}?action=all&offset=${(this.currentPage - 1) * this.productsPerPage}&limit=${this.productsPerPage}`;
        if (searchTerm) url += `&q=${encodeURIComponent(searchTerm)}`;
        if (categoryId) url += `&category_id=${categoryId}`;
        if (status !== '') url += `&active_only=${status}`;

        try {
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success && result.data) {
                // Correctly access the nested data and total from the API response
                this.products = result.data.data; 
                this.renderProducts();
                this.renderPagination(result.data.total);
            } else {
                this.showNotification(result.message || 'Failed to fetch products', 'error');
                document.getElementById('productsGrid').innerHTML = `<div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>${result.message || 'Could not load products.'}</p></div>`;
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            this.showNotification('An error occurred while fetching products.', 'error');
        }
    }
    
    async loadCategories() {
        try {
            const response = await fetch(`${this.CATEGORIES_API_URL}?action=all`);
            const result = await response.json();
            if (result.success && result.data) {
                const categoryFilter = document.getElementById('categoryFilter');
                const productCategory = document.getElementById('productCategory');
                categoryFilter.innerHTML = '<option value="">All Categories</option>';
                productCategory.innerHTML = ''; // Clear existing options
                result.data.forEach(category => {
                    const option = `<option value="${category.id}">${category.name}</option>`;
                    categoryFilter.innerHTML += option;
                    productCategory.innerHTML += option;
                });
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    filterProducts() {
        this.currentPage = 1;
        this.fetchProducts();
    }

    clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('categoryFilter').value = '';
        document.getElementById('statusFilter').value = '';
        this.filterProducts();
    }

    renderProducts() {
        const grid = document.getElementById('productsGrid');
        if (!this.products || this.products.length === 0) {
            grid.innerHTML = `<div class="empty-state">
                <i class="fas fa-box-open"></i><h3>No products found</h3>
                <p>Try adjusting your search or filter criteria, or add a new product!</p></div>`;
            return;
        }

        grid.innerHTML = this.products.map(product => {
            const statusClass = product.is_active == 1 ? 'active' : 'inactive';
            const statusText = product.is_active == 1 ? 'Active' : 'Inactive';
            return `
            <div class="product-card">
                <div class="product-image">
                    ${product.image_url ? `<img src="../${product.image_url}" alt="${product.name}">` : '<i class="fas fa-box-open"></i>'}
                    <div class="product-status status-${statusClass}">${statusText}</div>
                </div>
                <div class="product-info">
                    <h3 class="product-name">${product.name}</h3>
                    <p class="product-category">${product.category_name || 'Uncategorized'}</p>
                    <div class="product-price">$${parseFloat(product.price).toFixed(2)}</div>
                    <div class="product-stats">
                        <div class="stat-item">
                            <div class="stat-value">${product.stock_quantity}</div>
                            <div class="stat-label">Stock</div>
                        </div>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-sm btn-edit" onclick="productManager.editProduct(${product.id})"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-sm btn-delete" onclick="productManager.deleteProduct(${product.id})"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
            </div>`;
        }).join('');
    }
    
    renderPagination(totalProducts) {
        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(totalProducts / this.productsPerPage);
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        let paginationHTML = '';
        if (this.currentPage > 1) {
            paginationHTML += `<button class="page-btn" onclick="productManager.goToPage(${this.currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            paginationHTML += `<button class="page-btn ${i === this.currentPage ? 'active' : ''}" onclick="productManager.goToPage(${i})">${i}</button>`;
        }
        if (this.currentPage < totalPages) {
            paginationHTML += `<button class="page-btn" onclick="productManager.goToPage(${this.currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
        }
        pagination.innerHTML = paginationHTML;
    }

    goToPage(page) {
        this.currentPage = page;
        this.fetchProducts();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    openModal() {
        this.editingProductId = null;
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.getElementById('productForm').reset();
        document.getElementById('imagePreview').innerHTML = '';
        document.getElementById('productModal').classList.add('active');
    }

    closeModal() {
        document.getElementById('productModal').classList.remove('active');
        this.editingProductId = null;
    }

    async saveProduct(e) {
        e.preventDefault();
        const form = document.getElementById('productForm');
        const formData = new FormData(form);
        const isCreating = !this.editingProductId;

        const url = isCreating 
            ? `${this.API_URL}?action=create`
            : `${this.API_URL}?action=update&id=${this.editingProductId}`;
        
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                this.showNotification(`Product ${isCreating ? 'created' : 'updated'} successfully!`, 'success');
                this.closeModal();
                this.fetchProducts();
            } else {
                this.showNotification(result.message || 'Failed to save product.', 'error');
            }
        } catch (error) {
            console.error('Save Error:', error);
            this.showNotification('An error occurred while saving the product.', 'error');
        }
    }
    
    async editProduct(id) {
        try {
            const response = await fetch(`${this.API_URL}?action=get&id=${id}`);
            const result = await response.json();
            if (result.success && result.data) {
                const product = result.data;
                this.editingProductId = id;
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productCategory').value = product.category_id;
                document.getElementById('productPrice').value = product.price;
                document.getElementById('productStock').value = product.stock_quantity;
                document.getElementById('productStatus').value = product.is_active;
                document.getElementById('productDescription').value = product.description;

                const imagePreview = document.getElementById('imagePreview');
                imagePreview.innerHTML = product.image_url ? `<img src="../${product.image_url}" alt="Product Image Preview">` : '';
                
                document.getElementById('productModal').classList.add('active');
            } else {
                this.showNotification(result.message || 'Could not find product details.', 'error');
            }
        } catch (error) {
            console.error('Edit Error:', error);
            this.showNotification('An error occurred while fetching product details.', 'error');
        }
    }

    async deleteProduct(id) {
        if (confirm('Are you sure you want to delete this product?')) {
            try {
                const response = await fetch(`${this.API_URL}?action=delete&id=${id}`, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
                    this.showNotification('Product deleted successfully!', 'success');
                    this.fetchProducts();
                } else {
                    this.showNotification(result.message || 'Failed to delete product.', 'error');
                }
            } catch (error) {
                console.error('Delete Error:', error);
                this.showNotification('An error occurred while deleting the product.', 'error');
            }
        }
    }

    handleImageUpload(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            const imagePreview = document.getElementById('imagePreview');
            reader.onload = function(event) {
                imagePreview.innerHTML = `<img src="${event.target.result}" alt="Image Preview">`;
            }
            reader.readAsDataURL(file);
        }
    }
    
     exportProducts() {
        const csvContent = "data:text/csv;charset=utf-8," 
            + "ID,Name,Category,Price,Stock,Status\n"
            + this.products.map(p => 
                `${p.id},"${p.name}","${p.category_name}",${p.price},${p.stock_quantity},${p.is_active == 1 ? 'Active' : 'Inactive'}`
            ).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "products.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.showNotification('Products exported successfully!', 'success');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.productManager = new ProductManager();
    window.productManager.init();
});

</script>

