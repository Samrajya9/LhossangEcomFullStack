<?php
$pageTitle = "Admin Dashboard";

require_once __DIR__ . "/../../config/config.php";

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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome Back, Admin!</h1>
            <p>Here's a real-time overview of your store's performance. Manage all aspects of your business from the sidebar.</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p class="stat-value skeleton skeleton-text" id="totalProductsStat">&nbsp;</p>
            </div>
            <div class="stat-card">
                <h3>Total Customers</h3>
                <p class="stat-value skeleton skeleton-text" id="totalCustomersStat">&nbsp;</p>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p class="stat-value skeleton skeleton-text" id="totalOrdersStat">&nbsp;</p>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p class="stat-value skeleton skeleton-text" id="totalRevenueStat">&nbsp;</p>
            </div>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-grid">
            
            <!-- Recent Orders Section -->
            <div class="table-container">
                <div class="page-header">
                    <div><h2 class="page-title">Recent Orders</h2></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentOrdersBody">
                        <!-- SKELETON LOADER FOR TABLE -->
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                            <td><div class="skeleton skeleton-text"></div></td>
                            <td><div class="skeleton skeleton-text"></div></td>
                            <td><div class="skeleton skeleton-text"></div></td>
                            <td><div class="skeleton skeleton-text"></div></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Products Section -->
            <div class="table-container">
                 <div class="page-header">
                    <div><h2 class="page-title">Top Selling Products</h2></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty Sold</th>
                        </tr>
                    </thead>
                    <tbody id="topProductsBody">
                        <!-- SKELETON LOADER FOR TABLE -->
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                            <td><div class="skeleton skeleton-text"></div></td>
                            <td><div class="skeleton skeleton-text"></div></td>
                            <td><div class="skeleton skeleton-text"></div></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions & Analytics -->
        <div class="dashboard-grid">
            <div class="table-container">
                 <div class="page-header"><h2 class="page-title">Quick Actions</h2></div>
                <div class="quick-actions-grid">
                    <a href="productManagement.php" class="quick-action-card products"><i class="fas fa-plus"></i><h3>Add Product</h3><p>Create a new item</p></a>
                    <a href="ordermanagement.php" class="quick-action-card orders"><i class="fas fa-receipt"></i><h3>Manage Orders</h3><p>View all orders</p></a>
                    <a href="customermanagement.php" class="quick-action-card customers"><i class="fas fa-users"></i><h3>Customers</h3><p>View customer data</p></a>
                    <a href="adminmanagement.php" class="quick-action-card users"><i class="fas fa-user-shield"></i><h3>Admins</h3><p>Manage admin users</p></a>
                </div>
            </div>
             <div class="table-container">
                 <div class="page-header"><h2 class="page-title">Analytics</h2></div>
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="analytics-icon chart"><i class="fas fa-chart-line"></i></div>
                        <h3 class="analytics-value" id="avgOrderValue">...</h3>
                        <p class="analytics-label">Avg. Order Value</p>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-icon percentage"><i class="fas fa-percentage"></i></div>
                        <h3 class="analytics-value" id="conversionRate">...</h3>
                        <p class="analytics-label">Conversion Rate</p>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- End .content-container -->
    
    <?php include '../includes/footer.php'; ?>
</div>
<!-- Link to the new dynamic dashboard script -->
<script src="<?=BASE_URL?>/assets/js/dashboard.js"></script>
</body>
</html>