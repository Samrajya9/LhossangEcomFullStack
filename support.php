<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/authFunctions.php';

$pageTitle = "Support - InfinityWaves";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main>
    <section class="main-content-area">
        <div class="container">
            <h2 class="section-title">Get in Touch</h2>
            <p class="section-subtitle">Have a question or need assistance? Fill out the form below and we'll get back to you shortly.</p>

            <div class="support-page-container">
                <!-- Section 1: Contact Form -->
                <div class="support-form-section">
                    <form id="support-form" novalidate>
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="6" required></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="action-btn">Send Message</button>
                        </div>
                    </form>
                </div>

                <!-- Section 2: Other Contact Info (Now below the form) -->
                <div class="contact-info-section">
                    <h3>Other Ways to Contact Us</h3>
                    <div class="contact-details">
                        <p class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <span>+977 9829552379</span>
                        </p>
                        <p class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>lhosanglama555@gmail.com</span>
                        </p>
                        <p class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>PadmaShree College, Tinkune, Kathmandu</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Reusable Notification Function (copied from single-product.php)
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);

    // Animate out and remove
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 500); // Remove from DOM after animation
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    const supportForm = document.getElementById('support-form');
    if (!supportForm) return;

    supportForm.addEventListener('submit', function(event) {
        // 1. Prevent the default browser action (reloading the page)
        event.preventDefault();

        // 2. Get form data
        const fullName = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const message = document.getElementById('message').value.trim();

        // 3. Simple validation
        if (!fullName || !email || !subject || !message) {
            showNotification('Please fill out all required fields.', 'error');
            return;
        }

        // 4. --- MOCK THE SUBMISSION ---
        // This is where you would normally send data to an API.
        // We will just log it to the console to show it's working.
        console.log('Mock form submission successful. Data:', { fullName, email, subject, message });

        // 5. Provide user feedback
        // Clear the form fields for the next submission
        supportForm.reset();

        // Show a success notification
        showNotification('Message sent successfully!', 'success');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>