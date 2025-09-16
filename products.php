<?php 
// /opt/lampp/htdocs/infinityAdmin/products.php
require_once __DIR__ . '/utils/authFunctions.php';

$pageTitle = "All Products";

include __DIR__ . '/includes/header.php'; 
include __DIR__ . '/includes/navigation.php';

// Fetch products from the API
$api_url = BASE_URL . '/api/products.php?action=all';
$response_json = @file_get_contents($api_url);
$response_data = $response_json ? json_decode($response_json, true) : null;

$products = [];
if ($response_data && $response_data['success'] && isset($response_data['data']['data'])) {
    $products = $response_data['data']['data'];
}
$isUserLoggedIn = isLoggedIn();
?>

<main class="main-content-area">
    <h1 class="section-title">Our Products</h1>  
    <?php if (!empty($products)): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <a href="single-product.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                        <div class="product-image">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo BASE_URL . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-photo">
                            <?php else: ?>
                                <i class="fas fa-box-open" style="font-size: 50px; color: #ccc;"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-details"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                        <p class="product-price"><?=CURRENCY?><?php echo number_format($product['price'], 2); ?></p>
                        
                        <!-- MODIFICATION: Removed the 'disabled' attribute check for login status. -->
                        <button 
                            class="add-to-cart-btn" 
                            data-product-id="<?php echo htmlspecialchars($product['id']); ?>"
                        >
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="centered-container">
            <i class="fas fa-exclamation-circle" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
            <h2>No Products Found</h2>
            <p>We couldn't find any products at the moment. Please check back later!</p>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- MODIFICATION: Removed the inline style block for notifications -->
<script>
// Reusable Notification Function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        notification.style.transition = 'all 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    const isUserLoggedIn = <?php echo json_encode($isUserLoggedIn); ?>;

    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            // This logic now works correctly because the button is no longer disabled.
            if (!isUserLoggedIn) {
                showNotification('You must be logged in to add items.', 'error');
                return;
            }

            const productId = this.dataset.productId;
            let cart = JSON.parse(localStorage.getItem('cart')) || {};

            if (cart[productId]) {
                cart[productId]++;
            } else {
                cart[productId] = 1;
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            
            showNotification('Product added to cart!', 'success');
        });
    });
});
</script>