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
           
                </div>
            </div>
        </div>
    </div> <!-- End .content-container -->
    
    <?php include '../includes/footer.php'; ?>
</div>
<!-- Link to the new dynamic dashboard script -->
<script>
    class DashboardManager {
  constructor() {
    this.api = {
      orders: "<?=BASE_URL?>/api/orders.php",
      products: "<?=BASE_URL?>/api/products.php",
      customers: "<?=BASE_URL?>/api/customers.php",
    };
  }

  init() {
    document.addEventListener("DOMContentLoaded", () => {
      this.fetchAllDashboardData();
      // Auto-refresh stats every 2 minutes for real-time feel
      setInterval(() => this.fetchStats(), 120000);
    });
  }

  async fetchApi(url) {
    try {
      const response = await fetch(url);
      if (!response.ok) {
        console.error(
          `API request failed: ${response.statusText} for URL: ${url}`
        );
        return null;
      }
      return await response.json();
    } catch (error) {
      console.error(`Failed to fetch from ${url}`, error);
      return null;
    }
  }

  async fetchAllDashboardData() {
    this.fetchStats();
    this.fetchRecentOrders();
    this.fetchTopProducts();
  }

  async fetchStats() {
    const results = await Promise.all([
      this.fetchApi(`${this.api.orders}?action=statistics`),
      this.fetchApi(`${this.api.products}?action=count`),
      this.fetchApi(`${this.api.customers}?action=count`),
    ]);

    const [orderStatsRes, productCountRes, customerCountRes] = results;

    this.renderStats({ orderStatsRes, productCountRes, customerCountRes });
  }

  async fetchRecentOrders() {
    const result = await this.fetchApi(
      `${this.api.orders}?action=recent&limit=5`
    );
    if (result && result.success) {
      this.renderRecentOrders(result.data);
    }
  }

  async fetchTopProducts() {
    const result = await this.fetchApi(
      `${this.api.orders}?action=top-products&limit=5`
    );
    if (result && result.success) {
      this.renderTopProducts(result.data);
    }
  }

  renderStats(data) {
    const { orderStatsRes, productCountRes, customerCountRes } = data;

    // Total Products
    const totalProductsEl = document.getElementById("totalProductsStat");
    if (totalProductsEl && productCountRes && productCountRes.success) {
      totalProductsEl.classList.remove("skeleton", "skeleton-text");
      totalProductsEl.textContent = Number(
        productCountRes.data.count
      ).toLocaleString();
    }

    // Total Customers
    const totalCustomersEl = document.getElementById("totalCustomersStat");
    if (totalCustomersEl && customerCountRes && customerCountRes.success) {
      totalCustomersEl.classList.remove("skeleton", "skeleton-text");
      totalCustomersEl.textContent = Number(
        customerCountRes.data.count
      ).toLocaleString();
    }

    if (orderStatsRes && orderStatsRes.success) {
      const stats = orderStatsRes.data;
      // Total Orders
      const totalOrdersEl = document.getElementById("totalOrdersStat");
      if (totalOrdersEl) {
        totalOrdersEl.classList.remove("skeleton", "skeleton-text");
        totalOrdersEl.textContent = Number(stats.total_orders).toLocaleString();
      }
      // Total Revenue
      const totalRevenueEl = document.getElementById("totalRevenueStat");
      if (totalRevenueEl) {
        totalRevenueEl.classList.remove("skeleton", "skeleton-text");
        totalRevenueEl.textContent = `<?=CURRENCY?>${parseFloat(
          stats.total_revenue || 0
        ).toLocaleString("en-IN", { minimumFractionDigits: 2 })}`;
      }
      // Avg Order Value
      const avgOrderValueEl = document.getElementById("avgOrderValue");
      if (avgOrderValueEl) {
        avgOrderValueEl.textContent = `<?=CURRENCY?>${parseFloat(
          stats.average_order_value || 0
        ).toLocaleString("en-IN", { minimumFractionDigits: 2 })}`;
      }
    }
    // Conversion Rate
    const conversionRateEl = document.getElementById("conversionRate");
    if (conversionRateEl && orderStatsRes && customerCountRes) {
      const totalOrders = orderStatsRes.data?.total_orders || 0;
      const totalCustomers = customerCountRes.data?.count || 0;
      const rate =
        totalCustomers > 0
          ? ((totalOrders / totalCustomers) * 100).toFixed(1)
          : 0;
      conversionRateEl.textContent = `${rate}%`;
    }
  }

  renderRecentOrders(orders) {
    const tableBody = document.getElementById("recentOrdersBody");
    if (!tableBody) return;

    if (!orders || orders.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="4"><div class="empty-state" style="padding: 20px;">
                <i class="fas fa-receipt"></i><p>No recent orders found.</p>
            </div></td></tr>`;
      return;
    }

    tableBody.innerHTML = orders
      .map(
        (order) => `
            <tr>
                <td><strong>#${order.id}</strong></td>
                <td>${order.customer_name || "N/A"}</td>
                <td class="text-success"><strong><?=CURRENCY?>${parseFloat(
                  order.total_amount || 0
                ).toLocaleString("en-IN", {
                  minimumFractionDigits: 2,
                })}</strong></td>
                <td>
                    <span class="status-badge status-${order.status.toLowerCase()}">
                        ${order.status}
                    </span>
                </td>
            </tr>
        `
      )
      .join("");
  }

  renderTopProducts(products) {
    const tableBody = document.getElementById("topProductsBody");
    if (!tableBody) return;

    if (!products || products.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="3"><div class="empty-state" style="padding: 20px;">
                <i class="fas fa-box-open"></i><p>No product sales data available.</p>
            </div></td></tr>`;
      return;
    }

    tableBody.innerHTML = products
      .map(
        (product) => `
            <tr>
                <td>${product.product_name || "Unknown"}</td>
                <td class="text-success"><strong><?=CURRENCY?>${parseFloat(
                  product.product_price || 0
                ).toLocaleString("en-IN", {
                  minimumFractionDigits: 2,
                })}</strong></td>
                <td><span class="order-count-badge">${
                  product.total_quantity_sold
                }</span></td>
            </tr>
        `
      )
      .join("");
  }
}
// Initialize the dashboard
const dashboard = new DashboardManager();
dashboard.init();

</script>
</body>
</html>