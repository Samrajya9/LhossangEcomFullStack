<?php
// /opt/lampp/htdocs/infinityAdmin/logout.php

// Always start the session to access and manage session data.
session_start();

// Unset all of the session variables.
// $_SESSION = array(); is an alternative way to clear all session data.
session_unset();

// Finally, destroy the session. This will remove the session data from the server.
session_destroy();

// Define the base URL from your configuration to ensure a correct redirect.
// Note: It's good practice to include your config file if BASE_URL is not hardcoded.
require_once __DIR__ . '/config/config.php';

// Redirect the user to the homepage after logging out.
// The user is now logged out and will see the public version of the site.
header('Location: ' . BASE_URL . '/index.php');

// Ensure that no further code is executed after the redirect.
exit;
?>