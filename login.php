<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/customer_login.php

require_once __DIR__ . '/config/config.php';

require_once __DIR__ . '/utils/authFunctions.php';
// Include necessary files for database functions and configuration.
require_once __DIR__ . '/utils/customerFunctions.php';

// If the user is already logged in, redirect them to the homepage.
if(isLoggedIn()){
      header('Location: ' . BASE_URL . '/index.php');
    exit;
}
// Initialize an error message variable.
$errorMessage = '';

// --- API LOGIC INTEGRATION ---
// Check if the form was submitted using the POST method.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate that inputs are not empty.
    if (empty($email) || empty($password)) {
        $errorMessage = 'Email and password are required.';
    } else {
        // Fetch customer data using the authentication function.
        $customer = getCustomerAuthByEmail($email);

        // Verify if the customer exists and the password is correct.
        if ($customer && isset($customer['password_hash']) && password_verify($password, $customer['password_hash'])) {
            // Password is correct, so we set the session variables.
            $_SESSION['logged_in'] = true;
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['first_name'];
            $_SESSION['is_admin'] = false; // Differentiate from admin users.
            // Redirect to the homepage upon successful login.
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            // If credentials do not match, set an error message.
            $errorMessage = 'Invalid email or password.';
        }
    }
}

$pageTitle = "Customer Login";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<style>
    .login-container {
        max-width: 400px;
        margin: 50px auto;
        padding: 30px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        background-color: #fff;
    }
    .login-container h1 {
        text-align: center;
        margin-bottom: 25px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .login-btn {
        width: 100%;
        padding: 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    .login-btn:hover {
        background-color: #0056b3;
    }
    .error-message {
        color: #D8000C;
        background-color: #FFD2D2;
        border: 1px solid #D8000C;
        padding: 10px;
        border-radius: 4px;
        text-align: center;
        margin-bottom: 20px;
    }
</style>

<main class="main-content-area">
    <div class="login-container">
        <h1>Customer Login</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <!-- The form now posts to itself. -->
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Sign In</button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>