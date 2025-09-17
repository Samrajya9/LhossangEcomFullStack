<?php
// /opt/lampp/htdocs/infinityAdmin/login.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/authFunctions.php';
require_once __DIR__ . '/utils/customerFunctions.php';

// If the user is already logged in, redirect them.
if(isLoggedIn()){
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errorMessage = '';

// Check if the form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errorMessage = 'Email and password are required.';
    } else {
        $customer = getCustomerAuthByEmail($email);

        if ($customer && isset($customer['password_hash']) && password_verify($password, $customer['password_hash'])) {
            // Set session variables upon successful login.
            $_SESSION['logged_in'] = true;
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['first_name'];
            $_SESSION['is_admin'] = false;
            
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $errorMessage = 'Invalid email or password.';
        }
    }
}

$pageTitle = "Customer Login";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="main-content-area">
    <div class="container">
        <div class="auth-container">
            <h1>Customer Login</h1>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <!-- Using the existing .action-btn class for consistency -->
                <button type="submit" class="action-btn" style="width: 100%;">Sign In</button>
            </form>

            <!-- ADDED: Sign up link section -->
            <div class="signup-link">
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>