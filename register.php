<?php
// /opt/lampp/htdocs/infinityAdmin/register.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/authFunctions.php';
require_once __DIR__ . '/utils/customerFunctions.php';

// If the user is already logged in, redirect them.
if(isLoggedIn()){
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // --- Validation ---
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $errorMessage = 'Please fill out all required fields.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters long.';
    } else {
        // Prepare data for the API/function call
        $customerData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password, // The function will hash this
        ];

        // Call the createCustomer function
        $result = createCustomer($customerData);

        if (isset($result['success']) && $result['success']) {
            // On success, redirect to the login page with a success message
            header('Location: ' . BASE_URL . '/login.php?registered=success');
            exit;
        } else {
            // Display the error message from the function
            $errorMessage = $result['error'] ?? 'An unknown error occurred.';
        }
    }
}

$pageTitle = "Create Account";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="main-content-area">
    <div class="container">
        <div class="auth-container">
            <h1>Create an Account</h1>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                 <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="action-btn" style="width: 100%;">Create Account</button>
            </form>
            
            <div class="signup-link">
                <p>Already have an account? <a href="login.php">Log In</a></p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>