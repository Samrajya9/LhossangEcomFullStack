<?php
// /opt/lampp/htdocs/infinityAdmin/carts.php
require_once __DIR__ . '/utils/authFunctions.php';
$pageTitle = "Your Shopping Cart";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';

// Check if the user is logged in before displaying the cart
if (!isLoggedIn()) {
    // If not logged in, show a styled message and a link to the login page.
    echo '
    <main class="main-content-area">
        <div class="cart-info-container">
            <i class="fas fa-user-lock"></i>
            <h2>Authentication Required</h2>
            <p>You need to be logged in to view your cart.</p>
            <a href="'.BASE_URL.'/login.php" class="btn">Login Now</a>
        </div>
    </main>';
    // Include the footer and stop executing the rest of the page.
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>
<main class="main-content-area">

    <h1 class="section-title">Your Shopping Cart</h1>
    
    <div class="cart-page-container">
        <div id="cart-container" class="cart-items-container">
            <!-- Cart items will be dynamically inserted here by JavaScript -->
            <div class="cart-info-container">
                <i class="fas fa-spinner fa-spin"></i>
                <h2>Loading your cart...</h2>
            </div>
        </div>
        
        <div id="cart-summary" class="cart-summary-container" style="display: none;">
            <!-- Cart summary (total price, etc.) will be shown here -->
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- JavaScript to fetch and display cart items -->
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const cartContainer = document.getElementById('cart-container');
    const cartSummary = document.getElementById('cart-summary');

    // Get cart data from localStorage.
    const cartProductIds = JSON.parse(localStorage.getItem('cart')) || [];

    if (cartProductIds.length === 0) {
        // If the cart is empty, display a message.
        cartContainer.innerHTML = `
            <div class="cart-info-container">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your Cart is Empty</h2>
                <p>Looks like you haven't added any products yet. Start shopping!</p>
                 <a href="<?=BASE_URL?>/products.php" class="btn">Browse Products</a>
            </div>
        `;
        // Hide the summary container if the cart is empty
        cartSummary.style.display = 'none';
        return;
    }

    // Count the quantity of each product.
    const productQuantities = cartProductIds.reduce((acc, id) => {
        acc[id] = (acc[id] || 0) + 1;
        return acc;
    }, {});
    
    // Get the unique product IDs to fetch their details.
    const uniqueProductIds = Object.keys(productQuantities);

    try {
        // Fetch product details from the API.
        const response = await fetch(`<?php echo BASE_URL; ?>/api/products.php?action=get_multiple&ids=${uniqueProductIds.join(',')}`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch product data.');
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            displayCartItems(result.data, productQuantities);
        } else {
            throw new Error(result.error || 'Could not find product data.');
        }
    } catch (error) {
        cartContainer.innerHTML = `<div class="cart-info-container"><p class="error-message">Error loading cart: ${error.message}</p></div>`;
    }

    function displayCartItems(products, quantities) {
        let cartHTML = `
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        let totalPrice = 0;

        products.forEach(product => {
            const quantity = quantities[product.id];
            const subtotal = product.price * quantity;
            totalPrice += subtotal;
            
            cartHTML += `
                <tr data-product-id="${product.id}">
                    <td class="cart-product-info">
                        <img src="<?php echo BASE_URL; ?>${product.image_url}" alt="${product.name}" class="cart-product-image">
                        <div class="product-name-desc">
                           <strong>${product.name}</strong>
                        </div>
                    </td>
                    <td>Rs. ${parseFloat(product.price).toFixed(2)}</td>
                    <td class="cart-quantity">
                         ${quantity}
                    </td>
                    <td><strong>Rs. ${subtotal.toFixed(2)}</strong></td>
                    <td>
                        <button class="remove-from-cart-btn" data-product-id="${product.id}" title="Remove item">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </td>
                </tr>
            `;
        });
        
        cartHTML += '</tbody></table>';
        cartContainer.innerHTML = cartHTML;
        
        // --- NEW: Display enhanced cart summary ---
        const summaryHTML = `
            <div class="cart-summary-card">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rs. ${totalPrice.toFixed(2)}</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>FREE</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row total-row">
                    <span>Total</span>
                    <span>Rs. ${totalPrice.toFixed(2)}</span>
                </div>
                <button class="checkout-btn">Proceed to Checkout</button>
            </div>
        `;
        cartSummary.innerHTML = summaryHTML;
        cartSummary.style.display = 'block';

        // Add event listeners to "Remove" buttons.
        document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productIdToRemove = this.dataset.productId;
                
                // Filter out all instances of this product ID.
                let updatedCart = cartProductIds.filter(id => id !== productIdToRemove);
                
                // Save the updated cart back to localStorage.
                localStorage.setItem('cart', JSON.stringify(updatedCart));
                
                // Reload the page to reflect changes.
                window.location.reload();
            });
        });
    }
});
</script>```