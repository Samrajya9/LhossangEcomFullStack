<?php
require_once __DIR__ . '/../config/config.php';
// file_full_path = /opt/lampp/htdocs/infinityAdmin/admin/index.php
require_once "/".BASE_PATH . '/utils/authFunctions.php';

if (isLoggedIn() && isAdmin()) {
    header("Location: pages/dashboard.php");
    exit();
} else {
    header("Location: pages/signin.php");
    exit();
}
?>
