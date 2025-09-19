<?php
// file full path = /opt/lampp/htdocs/infinityAdmin/config/config.php

// Turn on error reporting during development for easier debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Core Paths and URLs ---

// File System Path to the project root (e.g., /opt/lampp/htdocs/infinityAdmin)
// This is calculated automatically and should NOT be changed.
// BASE_PATH =/opt/lampp/htdocs/infinityAdmin
define('BASE_PATH', dirname(__DIR__));

// Web Server URL to the project root.
// IMPORTANT: Change this value if you move your project or deploy it to a live server.

$FolderName = basename(BASE_PATH); // e.g., infinityAdmin
$XamppPort = '8080'; // Change this if your XAMPP uses a different port

define('BASE_URL', 'http://127.0.0.1:'. $XamppPort .'/' . $FolderName);



// --- Database Connection Details ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Your database username
define('DB_PASS', '');         // Your database password
define('DB_NAME', 'infinity'); // Your database name


define('CURRENCY', 'Rs. '); // Currency symbol for display
?>