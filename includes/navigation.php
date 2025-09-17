<?php
 // file_full_path = /opt/lampp/htdocs/infinityAdmin/includes/navigation.php
 require_once __DIR__ . '/../config/config.php';
 require_once __DIR__ . '/../utils/authFunctions.php'; // so isLoggedIn() works
 ?>
<nav class="navigation">
    <div class="container">
        <a href="<?= BASE_URL ?>/index.php" class="brand nav-btn"> <b>Infinity Waves</b></a>
        <a href="<?= BASE_URL ?>/index.php" class="nav-btn <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'nav-active' : ''; ?>">Home</a>
        <a href="<?= BASE_URL ?>/products.php" class="nav-btn <?= (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'nav-active' : ''; ?>">Products</a>
        <a href="<?= BASE_URL ?>/support.php" class="nav-btn <?= (basename($_SERVER['PHP_SELF']) == 'support.php') ? 'nav-active' : ''; ?>">Support</a>
        <a href="<?= BASE_URL ?>/about.php" class="nav-btn  <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'nav-active' : ''; ?>">About</a>
        <a href="<?= BASE_URL ?>/carts.php" class="nav-btn <?= (basename($_SERVER['PHP_SELF']) == 'carts.php') ? 'nav-active' : ''; ?>">Cart</a>

        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/logout.php" class="nav-btn <?= (basename($_SERVER['PHP_SELF']) == 'logout.php') ? 'nav-active' : ''; ?>">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="nav-btn <?= (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'nav-active' : ''; ?>">Login</a>
        <?php endif; ?>
    </div>
</nav>