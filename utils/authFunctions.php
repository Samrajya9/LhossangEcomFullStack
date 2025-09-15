<?php 
// file_full_path = /opt/lampp/htdocs/infinityAdmin/utils/authFunctions.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

?>