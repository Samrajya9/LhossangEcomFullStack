class DashboardManager {
  constructor() {
    this.api = {
      orders: "../api/orders.php",
      products: "../api/products.php",
      customers: "../api/customers.php",
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
        totalRevenueEl.textContent = `Rs. ${parseFloat(
          stats.total_revenue || 0
        ).toLocaleString("en-IN", { minimumFractionDigits: 2 })}`;
      }
      // Avg Order Value
      const avgOrderValueEl = document.getElementById("avgOrderValue");
      if (avgOrderValueEl) {
        avgOrderValueEl.textContent = `Rs. ${parseFloat(
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
                <td class="text-success"><strong>Rs. ${parseFloat(
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
                <td class="text-success"><strong>Rs. ${parseFloat(
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
