<?php 
// /opt/lampp/htdocs/infinityAdmin/profile.php

require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/utils/authFunctions.php';

// Authentication Check: Redirect if not logged in.
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Set page title and include header/navigation
$pageTitle = "My Profile";
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navigation.php';

// Get the logged-in customer's ID from the session.
$customerId = $_SESSION['customer_id'] ?? null;
?>

<!-- =================================================================
     REFINED UI STYLES FOR PROFILE PAGE
     ================================================================= -->
<style>
    .main-content-area {
        padding: 40px 20px;
    }
    .profile-container {
        max-width: 1100px; margin: 0 auto; background: var(--background-white);
        border-radius: 12px; box-shadow: var(--shadow-md); overflow: hidden;
    }
    .profile-tabs {
        display: flex; background-color: var(--background-light);
        border-bottom: 1px solid var(--border-color);
    }
    .tab-link {
        padding: 18px 30px; cursor: pointer; font-weight: 600;
        color: var(--text-muted); border-bottom: 3px solid transparent;
        transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;
    }
    .tab-link:hover { color: var(--dark-color); }
    .tab-link.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
    .tab-content { display: none; padding: 30px 40px 40px; }
    .tab-content.active { display: block; }
    
    .tab-header {
        display: flex; justify-content: space-between; align-items: center;
        padding-bottom: 15px; margin-bottom: 30px; border-bottom: 1px solid var(--border-color);
    }
    .tab-header h2 { font-size: 1.5rem; color: var(--dark-color); margin: 0;}
    .tab-header p { color: var(--text-muted); margin: 5px 0 0; }
    
    .view-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;
    }
    .view-group { padding: 10px 0; }
    .view-label {
        display: block; font-weight: 500; font-size: 14px;
        color: var(--text-muted); margin-bottom: 8px;
    }
    .view-value { font-size: 16px; color: var(--dark-color); }
    .hidden { display: none !important; }

    .form-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;
    }
    .form-group { display: flex; flex-direction: column; }
    .form-label { margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--text-muted); }
    .form-control {
        padding: 12px 15px; border: 1px solid var(--border-color);
        border-radius: 5px; font-size: 16px; transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(93, 168, 217, 0.15); outline: none;
    }
    .form-actions { margin-top: 30px; display: flex; justify-content: flex-end; gap: 15px; }
    .action-btn.saving .spinner {
        display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite;
    }
    .action-btn.saving .btn-text { display: none; }
    .action-btn.saving .spinner { display: inline-block; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .orders-list { display: grid; gap: 20px; }
    .order-card {
        background: #fdfdfd; border: 1px solid var(--border-color);
        border-radius: 8px; transition: box-shadow 0.3s ease;
    }
    .order-card:hover { box-shadow: var(--shadow-sm); }
    .order-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 15px 20px; background: var(--background-light);
    }
    .order-id { font-weight: 600; color: var(--dark-color); }
    .order-card-body {
        display: flex; justify-content: space-between; padding: 20px;
        flex-wrap: wrap; gap: 15px;
    }
    .order-detail { font-size: 14px; }
    .order-detail strong {
        display: block; color: var(--text-muted); font-weight: 500;
        margin-bottom: 5px; font-size: 12px;
    }
    .order-card-footer { padding: 15px 20px; text-align: right; }
    .view-details-btn {
        background: var(--primary-color); color: white; border: none;
        padding: 8px 16px; border-radius: 50px; cursor: pointer; transition: all 0.3s ease;
    }
    .view-details-btn:hover { background: var(--primary-hover); }

    /* =================================================================
       IMPROVED MODAL STYLES
       ================================================================= */
    .modal {
        display: none; position: fixed; z-index: 1050; left: 0; top: 0;
        width: 100%; height: 100%; overflow-y: auto; 
        background-color: rgba(0,0,0,0.5); padding: 20px;
    }
    .modal.active { animation: fadeInModal 0.3s; display: flex; align-items: center; justify-content: center; }
    .modal-content {
        background-color: #fff; margin: auto; padding: 0; border-radius: 12px;
        width: 90%; max-width: 700px; animation: fadeInContent 0.4s ease-out;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2); display: flex; flex-direction: column;
    }
    .modal-header {
        background: var(--background-light); padding: 20px 25px;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid var(--border-color); border-radius: 12px 12px 0 0;
    }
    .modal-title { font-size: 1.4rem; color: var(--dark-color); font-weight: 600; }
    .close-btn {
        background: none; border: none; font-size: 1.5rem; color: var(--text-muted);
        cursor: pointer; transition: transform 0.2s, color 0.2s;
    }
    .close-btn:hover { color: var(--dark-color); transform: rotate(90deg); }
    .modal-body { max-height: 65vh; overflow-y: auto; padding: 25px; }
    .modal-summary {
        background: var(--background-light); padding: 20px; border-radius: 8px;
        margin-bottom: 25px; display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;
    }
    .order-item {
        display: flex; align-items: center; gap: 15px;
        padding: 15px 0; border-bottom: 1px solid var(--border-color);
    }
    .order-item:last-child { border-bottom: none; }
    .order-item img { width: 60px; height: 60px; border-radius: 5px; object-fit: cover; }
    .order-item-info { flex-grow: 1; }
    .order-item-price { font-weight: 600; }
    @keyframes fadeInModal { from { background: rgba(0,0,0,0); } to { background: rgba(0,0,0,0.5); } }
    @keyframes fadeInContent { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }

</style>


<main class="main-content-area">
    <div class="profile-container">
        <!-- Tabs Navigation -->
        <div class="profile-tabs">
            <div class="tab-link active" onclick="openTab(event, 'profile')"><i class="fas fa-user-circle"></i> My Profile</div>
            <div class="tab-link" onclick="openTab(event, 'orders')"><i class="fas fa-box"></i> My Orders</div>
        </div>

        <!-- Profile Content -->
        <div id="profile" class="tab-content active">
            <div class="tab-header">
                <div>
                    <h2>Account Information</h2>
                    <p>View and update your personal details here.</p>
                </div>
                <div id="profile-view-actions">
                    <button class="action-btn" id="editProfileBtn">Edit Profile</button>
                </div>
            </div>
            
            <div id="profile-view-mode">
                <div class="view-grid">
                    <div class="view-group"><span class="view-label">First Name</span><span id="view-firstName" class="view-value"></span></div>
                    <div class="view-group"><span class="view-label">Last Name</span><span id="view-lastName" class="view-value"></span></div>
                    <div class="view-group"><span class="view-label">Email</span><span id="view-email" class="view-value"></span></div>
                    <div class="view-group"><span class="view-label">Phone</span><span id="view-phone" class="view-value"></span></div>
                    <div class="view-group"><span class="view-label">Address</span><span id="view-address" class="view-value"></span></div>
                    <div class="view-group"><span class="view-label">City</span><span id="view-city" class="view-value"></span></div>
                </div>
            </div>

            <div id="profile-edit-mode" class="hidden">
                <form id="profileForm">
                    <div class="form-grid">
                        <div class="form-group"><label for="firstName" class="form-label">First Name</label><input type="text" id="firstName" name="first_name" class="form-control" required></div>
                        <div class="form-group"><label for="lastName" class="form-label">Last Name</label><input type="text" id="lastName" name="last_name" class="form-control" required></div>
                        <div class="form-group"><label for="email" class="form-label">Email</label><input type="email" id="email" name="email" class="form-control" required></div>
                        <div class="form-group"><label for="phone" class="form-label">Phone</label><input type="tel" id="phone" name="phone" class="form-control"></div>
                        <div class="form-group"><label for="address" class="form-label">Address</label><input type="text" id="address" name="address" class="form-control"></div>
                        <div class="form-group"><label for="city" class="form-label">City</label><input type="text" id="city" name="city" class="form-control"></div>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="cancelEditBtn" class="action-btn" style="background-color: #6c757d;">Cancel</button>
                        <button type="submit" id="saveBtn" class="action-btn">
                            <span class="btn-text">Save Changes</span>
                            <span class="spinner"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Content -->
        <div id="orders" class="tab-content">
             <div class="tab-header">
                <h2>Order History</h2>
                <p>Track your past orders and view their details.</p>
            </div>
            <div id="orders-list" class="orders-list">
                <p>Loading your orders...</p>
            </div>
        </div>
    </div>
</main>

<div id="orderDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalOrderId">Order Details</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBodyContent">
            <!-- Order details will be injected here -->
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    const customerId = <?php echo json_encode($customerId); ?>;
    const profileViewMode = document.getElementById('profile-view-mode');
    const profileEditMode = document.getElementById('profile-edit-mode');
    const profileViewActions = document.getElementById('profile-view-actions');
    // --- TABS FUNCTIONALITY ---
    function openTab(evt, tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    }
    function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        notification.style.transition = 'all 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

    document.addEventListener('DOMContentLoaded', function() {
        if (!customerId) {
            showNotification('Could not identify user. Please log in again.', 'error');
            return;
        }

        const modal = document.getElementById('orderDetailsModal');
        document.getElementById('editProfileBtn').addEventListener('click', () => toggleEditMode(true));
        document.getElementById('cancelEditBtn').addEventListener('click', () => toggleEditMode(false));
        document.getElementById('profileForm').addEventListener('submit', updateProfile);
        
        // NEW: Add click listener for closing modal by clicking on the background overlay
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        fetchProfile();
        fetchOrders();
    });

    // --- PROFILE VIEW/EDIT LOGIC ---
    function toggleEditMode(isEditing) {
        profileViewMode.classList.toggle('hidden', isEditing);
        profileViewActions.classList.toggle('hidden', isEditing);
        profileEditMode.classList.toggle('hidden', !isEditing);
    }

    async function fetchProfile() {
        try {
            const response = await fetch(`<?= BASE_URL ?>/api/customers.php?action=get&id=${customerId}`);
            const result = await response.json();
            if (result.success && result.data) {
                const customer = result.data;
                const placeholder = 'Not Set';

                // Populate form inputs
                document.getElementById('firstName').value = customer.first_name || '';
                document.getElementById('lastName').value = customer.last_name || '';
                document.getElementById('email').value = customer.email || '';
                document.getElementById('phone').value = customer.phone || '';
                document.getElementById('address').value = customer.address || '';
                document.getElementById('city').value = customer.city || '';

                // Populate view fields
                document.getElementById('view-firstName').textContent = customer.first_name || placeholder;
                document.getElementById('view-lastName').textContent = customer.last_name || placeholder;
                document.getElementById('view-email').textContent = customer.email || placeholder;
                document.getElementById('view-phone').textContent = customer.phone || placeholder;
                document.getElementById('view-address').textContent = customer.address || placeholder;
                document.getElementById('view-city').textContent = customer.city || placeholder;

            } else { throw new Error(result.error || 'Failed to fetch profile data.'); }
        } catch (error) { showNotification(error.message, 'error'); }
    }

    async function updateProfile(event) {
        event.preventDefault();
        const saveButton = document.getElementById('saveBtn');
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData.entries());

        saveButton.classList.add('saving');
        saveButton.disabled = true;

        try {
            const response = await fetch(`<?= BASE_URL ?>/api/customers.php?action=update&id=${customerId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showNotification('Profile updated successfully!', 'success');
                toggleEditMode(false); // Switch back to view mode
                await fetchProfile(); // Re-fetch to update view mode
            } else {
                throw new Error(result.error || 'Failed to update profile.');
            }
        } catch (error) {
            showNotification(error.message, 'error');
        } finally {
            saveButton.classList.remove('saving');
            saveButton.disabled = false;
        }
    }

    // --- ORDERS FUNCTIONS ---
    async function fetchOrders() {
        // ... This function remains the same ...
        const ordersListContainer = document.getElementById('orders-list');
        try {
            const response = await fetch(`<?= BASE_URL ?>/api/customers.php?action=orders&id=${customerId}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                const orders = result.data;
                let cardsHTML = orders.map(order => `
                    <div class="order-card">
                        <div class="order-card-header">
                            <span class="order-id">Order #${order.id}</span>
                            <span class="status-badge status-${order.status}">${order.status}</span>
                        </div>
                        <div class="order-card-body">
                            <div class="order-detail"><strong>Order Date</strong>${new Date(order.order_date).toLocaleDateString()}</div>
                            <div class="order-detail"><strong>Total Amount</strong><?= CURRENCY ?>${parseFloat(order.total_amount).toFixed(2)}</div>
                        </div>
                        <div class="order-card-footer"><button class="view-details-btn" onclick="viewOrderDetails(${order.id})">View Details</button></div>
                    </div>
                `).join('');
                ordersListContainer.innerHTML = cardsHTML;
            } else if (result.success) {
                ordersListContainer.innerHTML = '<p>You have not placed any orders yet.</p>';
            } else { throw new Error(result.error || 'Failed to fetch orders.'); }
        } catch (error) { ordersListContainer.innerHTML = `<p style="color: red;">${error.message}</p>`; }
    }

    // --- MODAL FUNCTIONS ---
    async function viewOrderDetails(orderId) {
        // ... This function remains the same ...
        const modal = document.getElementById('orderDetailsModal');
        const modalBody = document.getElementById('modalBodyContent');
        document.getElementById('modalOrderId').textContent = `Order #${orderId}`;
        modalBody.innerHTML = '<p>Loading details...</p>';
        modal.classList.add('active');

        try {
            const response = await fetch(`<?= BASE_URL ?>/api/orders.php?action=details&id=${orderId}`);
            const result = await response.json();

            if (result.success && result.data) {
                const { order, items } = result.data;
                let contentHTML = `
                    <div class="modal-summary">
                        <div class="order-detail"><strong>Order ID</strong> #${order.id}</div>
                        <div class="order-detail"><strong>Status</strong> <span class="status-badge status-${order.status}">${order.status}</span></div>
                        <div class="order-detail"><strong>Order Date</strong> ${new Date(order.order_date).toLocaleDateString()}</div>
                        <div class="order-detail"><strong>Order Total</strong> <?= CURRENCY ?>${parseFloat(order.total_amount).toFixed(2)}</div>
                    </div>
                    <hr style="border:none; border-top: 1px solid #eee; margin: 20px 0;">
                    <h4>Items in this order:</h4>`;

                items.forEach(item => {
                    contentHTML += `
                        <div class="order-item">
                            <img src="<?= BASE_URL ?>${item.product_image || '/path/to/default-image.png'}" alt="${item.product_name}">
                            <div class="order-item-info">
                                <strong>${item.product_name}</strong>
                                <p>Quantity: ${item.quantity}</p>
                            </div>
                            <div class="order-item-price"><?= CURRENCY ?>${(item.price * item.quantity).toFixed(2)}</div>
                        </div>`;
                });
                modalBody.innerHTML = contentHTML;
            } else { throw new Error(result.error || 'Could not fetch order details.'); }
        } catch (error) { modalBody.innerHTML = `<p style="color: red;">${error.message}</p>`; }
    }

   function closeModal() {
        document.getElementById('orderDetailsModal').classList.remove('active');
    }
</script>