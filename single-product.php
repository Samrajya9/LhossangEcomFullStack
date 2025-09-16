<?php 
// /opt/lampp/htdocs/infinityAdmin/single-product.php
require_once __DIR__ . '/utils/authFunctions.php';
require_once __DIR__ . '/config/config.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$productId) {
    header('Location: products.php');
    exit();
}

$api_url = BASE_URL . '/api/products.php?action=get&id=' . $productId;
$response_json = @file_get_contents($api_url);
$response_data = $response_json ? json_decode($response_json, true) : null;

$product = null;
if ($response_data && $response_data['success']) {
    $product = $response_data['data'];
}

$pageTitle = $product ? $product['name'] : "Product Not Found";
include __DIR__ . '/includes/header.php'; 
include __DIR__ . '/includes/navigation.php';

$isUserLoggedIn = isLoggedIn();
?>

<main class="main-content-area">
   <?php if ($product): ?>
        <div class="single-product-container">
            <div class="product-gallery">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <div style="display:flex; align-items:center; justify-content:center; height: 100%; min-height: 400px; background: #f1f3f4; border-radius: 10px;">
                        <i class="fas fa-image" style="font-size: 80px; color: #ccc;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-details-content">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                <p class="price"><?=CURRENCY?><?php echo number_format($product['price'], 2); ?></p>
                
                <div class="stock-status">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> left)</span>
                    <?php else: ?>
                        <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>

                <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="product-actions">
                    <input type="number" id="quantity-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" <?php if ($product['stock_quantity'] == 0) echo 'disabled'; ?>>
                    <!-- MODIFICATION: Removed the 'disabled' attribute check for login status. -->
                    <button 
                        class="action-btn add-to-cart-btn" 
                        data-product-id="<?php echo htmlspecialchars($product['id']); ?>"
                        <?php 
                            if ($product['stock_quantity'] == 0) {
                                echo 'disabled title="This product is out of stock" style="background-color: #999; cursor: not-allowed;"';
                            }
                        ?>
                    >
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="centered-container">
            <i class="fas fa-exclamation-triangle" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
            <h2>Product Not Found</h2>
            <p>The product you are looking for does not exist or has been removed.</p>
            <a href="products.php" class="action-btn" style="margin-top: 20px;">Back to Products</a>
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
    const addToCartButton = document.querySelector('.add-to-cart-btn');
    if (!addToCartButton) return;

    const quantityInput = document.getElementById('quantity-input');
    const isUserLoggedIn = <?php echo json_encode($isUserLoggedIn); ?>;
    
    addToCartButton.addEventListener('click', function() {
        // This logic now works correctly because the button is no longer disabled for logged-out users.
        if (!isUserLoggedIn) {
            showNotification('You must be logged in to add items.', 'error');
            return;
        }

        const productId = this.dataset.productId;
        const quantityToAdd = parseInt(quantityInput.value, 10);
        
        if (isNaN(quantityToAdd) || quantityToAdd < 1) {
            showNotification('Please enter a valid quantity.', 'error');
            return;
        }

        let cart = JSON.parse(localStorage.getItem('cart')) || {};

        if (cart[productId]) {
            cart[productId] += quantityToAdd;
        } else {
            cart[productId] = quantityToAdd;
        }

        localStorage.setItem('cart', JSON.stringify(cart));

        const productName = document.querySelector('.product-title').textContent;
        showNotification(`${quantityToAdd} x "${productName}" added to cart!`, 'success');
    });
});
</script>