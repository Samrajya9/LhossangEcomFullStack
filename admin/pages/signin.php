<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/admin/pages/signin.php
require_once __DIR__ . '/../../config/config.php';
require_once BASE_PATH . '/utils/authFunctions.php';

// If the user is already logged in, redirect them to the dashboard
if(isLoggedIn()){
    header("Location: dashboard.php");
    exit();
}


$pageTitle = "Sign In";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo $pageTitle; ?> | InfinityWaves</title>
<!-- Link to the consolidated stylesheet -->
<link rel="stylesheet" href="/infinityAdmin/assets/css/admin-styles.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-container">
        <div class="logo">InfinityWaves</div>
        <h1>Welcome Back</h1>
        <p class="subtitle">Sign in to continue</p>

        <!-- Error message container, controlled by JavaScript -->
        <div class="error-message" id="errorMessage"></div>

        <form id="loginForm">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••••">
            </div>

            <button type="submit" class="btn btn-primary sign-in-btn" id="signInBtn">
                <span class="btn-text">Sign In</span>
            </button>
        </form>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const signInBtn = document.getElementById("signInBtn");
    const errorMessageDiv = document.getElementById("errorMessage");

    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || !password) {
            showError("Please enter both email and password.");
            return;
        }

        // Show loading state
        setLoading(true);

        try {
            // Use a relative URL for portability
            const response = await fetch("/infinityAdmin/api/admins.php?action=login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, password }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Success! The session is set by the API. Redirect to dashboard.
                window.location.href = "dashboard.php";
            } else {
                // Show API error message
                showError(result.error || "Login failed. Please check your credentials.");
            }
        } catch (error) {
            console.error("Login Error:", error);
            showError("An unexpected server error occurred. Please try again later.");
        } finally {
            // Revert loading state
            setLoading(false);
        }
    });

    function showError(message) {
        errorMessageDiv.textContent = message;
        errorMessageDiv.classList.add("show");
    }

    function hideError() {
        errorMessageDiv.classList.remove("show");
    }
    
    function setLoading(isLoading) {
        const btnText = signInBtn.querySelector('.btn-text');
        if (isLoading) {
            signInBtn.disabled = true;
            btnText.innerHTML = '<div class="spinner"></div>';
        } else {
            signInBtn.disabled = false;
            btnText.innerHTML = 'Sign In';
        }
    }
    
    // Hide error message when user starts typing again
    emailInput.addEventListener('input', hideError);
    passwordInput.addEventListener('input', hideError);
});
</script>

</body>
</html>