<?php
// Assuming BASE_PATH is defined in config.php
require_once __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Admin Dashboard'; ?> | InfinityWaves</title>
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Consolidated Stylesheet -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-styles.css">
</head>
<body>
