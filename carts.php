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

// Get the logged-in customer's ID from the session.
$customerId = $_SESSION['customer_id'] ?? null;
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

<!-- MODIFICATION: Script updated to use notifications instead of alerts -->
<script>
// Reusable Notification Function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animate out and remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        notification.style.transition = 'all 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', async function() {
    const cartContainer = document.getElementById('cart-container');
    const cartSummary = document.getElementById('cart-summary');
    const customerId = <?php echo json_encode($customerId); ?>;
    const cart = JSON.parse(localStorage.getItem('cart')) || {};
    const productIds = Object.keys(cart);

    if (productIds.length === 0) {
        cartContainer.innerHTML = `
            <div class="cart-info-container">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your Cart is Empty</h2>
                <p>Looks like you haven't added any products yet. Start shopping!</p>
                 <a href="<?=BASE_URL?>/products.php" class="btn">Browse Products</a>
            </div>
        `;
        cartSummary.style.display = 'none';
        return;
    }
    
    try {
        const response = await fetch(`<?php echo BASE_URL; ?>/api/products.php?action=get_multiple&ids=${productIds.join(',')}`);
        if (!response.ok) throw new Error('Failed to fetch product data.');
        
        const result = await response.json();
        if (result.success && result.data) {
            displayCartItems(result.data, cart);
        } else {
            throw new Error(result.error || 'Could not find product data.');
        }
    } catch (error) {
        cartContainer.innerHTML = `<div class="cart-info-container"><p class="error-message">Error loading cart: ${error.message}</p></div>`;
    }

    function displayCartItems(products, cartQuantities) {
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
            const quantity = cartQuantities[product.id];
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
                    <td><?=CURRENCY?>${parseFloat(product.price).toFixed(2)}</td>
                    <td class="cart-quantity">${quantity}</td>
                    <td><strong><?=CURRENCY?>${subtotal.toFixed(2)}</strong></td>
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
        
        const summaryHTML = `
            <div class="cart-summary-card">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?=CURRENCY?>${totalPrice.toFixed(2)}</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>FREE</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row total-row">
                    <span>Total</span>
                    <span><?=CURRENCY?>${totalPrice.toFixed(2)}</span>
                </div>
                <button class="checkout-btn">Proceed to Checkout</button>
            </div>
        `;
        cartSummary.innerHTML = summaryHTML;
        cartSummary.style.display = 'block';

        document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productIdToRemove = this.dataset.productId;
                let currentCart = JSON.parse(localStorage.getItem('cart')) || {};
                delete currentCart[productIdToRemove];
                localStorage.setItem('cart', JSON.stringify(currentCart));
                window.location.reload();
            });
        });

        const checkoutButton = document.querySelector('.checkout-btn');
        checkoutButton.addEventListener('click', () => {
            checkoutButton.disabled = true;
            checkoutButton.textContent = 'Processing...';
            placeOrder(products, cartQuantities, totalPrice);
        });
    }

    async function placeOrder(products, quantities, total) {
        if (!customerId) {
            // MODIFIED: Replaced alert with notification
            showNotification('Could not verify customer session. Please log in again.', 'error');
            window.location.href = 'login.php';
            return;
        }

        const orderItems = products.map(product => ({
            product_id: product.id,
            quantity: quantities[product.id],
            price: product.price
        }));

        const orderData = {
            customer_id: customerId,
            items: orderItems,
            total_amount: total,
            shipping_address: 'User Address from Profile', // Placeholder
            notes: 'Order placed from website.' // Placeholder
        };

        try {
            const response = await fetch('<?php echo BASE_URL; ?>/api/orders.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // MODIFIED: Replaced alert with notification
                showNotification('Order placed successfully! Your', 'success');
                localStorage.removeItem('cart');
                // Reload after a short delay so the user can see the notification
                setTimeout(() => window.location.reload(), 2000);
            } else {
                throw new Error(result.error || 'An unknown error occurred while placing the order.');
            }
        } catch (error) {
            // MODIFIED: Replaced alert with notification
            showNotification(`Failed to place order: ${error.message}`, 'error');
            const checkoutButton = document.querySelector('.checkout-btn');
            checkoutButton.disabled = false;
            checkoutButton.textContent = 'Proceed to Checkout';
        }
    }
});
</script>