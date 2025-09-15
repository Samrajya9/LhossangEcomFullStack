<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/includes/header.php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'InfinityWaves'; ?></title>
    
    <!-- Consolidated Stylesheet -->
    <link rel="stylesheet" href="<?=BASE_URL?>/assets/css/user-style.css">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="wrapper">
    <!-- HEADER -->
    <header class="header">
        <a href="<?=BASE_URL?>/index.php" class="logo">
            <img src="<?=BASE_URL?>/assets/images/innfinity.png" alt="Infinity Logo" class="logo-img">
        </a>
        
        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search for speakers, home theaters...">
            <button class="search-btn" aria-label="Search">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <div class="auth-buttons">
            <a href="<?=BASE_URL?>/admin/pages/signin.php" aria-label="My Account"><i class="fas fa-user"></i></a>
            <a href="#" aria-label="Notifications"><i class="fas fa-bell"></i></a>
            <a href="#" aria-label="Shopping Cart"><i class="fas fa-shopping-cart"></i></a>
        </div>
    </header>