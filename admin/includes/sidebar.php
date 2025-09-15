<?php
// file _full_path = /opt/lampp/htdocs/infinityAdmin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="logo">InfinityWaves</div>
    <div class="nav-menu">
        <a class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-item <?php echo ($current_page == 'productManagement.php') ? 'active' : ''; ?>" href="productManagement.php">
            <i class="fas fa-box"></i> Product Management
        </a>
        <a class="nav-item <?php echo ($current_page == 'ordermanagement.php') ? 'active' : ''; ?>" href="ordermanagement.php">
            <i class="fas fa-shopping-cart"></i> Order Management
        </a>
        <a class="nav-item <?php echo ($current_page == 'customermanagement.php') ? 'active' : ''; ?>" href="customermanagement.php">
            <i class="fas fa-user-friends"></i> Customer Management
        </a>
        <a class="nav-item <?php echo ($current_page == 'adminmanagement.php') ? 'active' : ''; ?>" href="adminmanagement.php">
            <i class="fas fa-chart-bar"></i> User Management
        </a>
        <div class="bottom-nav">
            <a class="nav-item <?php echo ($current_page == 'support.php') ? 'active' : ''; ?>" href="support.php">
                <i class="fas fa-question-circle"></i> Support
            </a>
            <a class="nav-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-item" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>