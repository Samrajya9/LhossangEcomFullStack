<?php 
// /opt/lampp/htdocs/infinityAdmin/products.php
require_once __DIR__ . '/utils/authFunctions.php';

$pageTitle = "All Products";

include __DIR__ . '/includes/header.php'; 
include __DIR__ . '/includes/navigation.php';
// ADDED: Include authentication functions to check login status.


// Fetch products from the API using the constant from config.php
$api_url = BASE_URL . '/api/products.php?action=all';
$response_json = @file_get_contents($api_url);
$response_data = $response_json ? json_decode($response_json, true) : null;

$products = [];
if ($response_data && $response_data['success'] && isset($response_data['data']['data'])) {
    $products = $response_data['data']['data'];
}
// Check if the user is logged in to enable/disable the cart buttons.
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
                                <!-- Prepend BASE_URL for correct image path from uploads -->
                                <img src="<?php echo BASE_URL . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-photo">
                            <?php else: ?>
                                <i class="fas fa-box-open" style="font-size: 50px; color: #ccc;"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-details"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                        <p class="product-price">Rs. <?php echo number_format($product['price'], 2); ?></p>
                        
                        <!-- MODIFICATION: Button is now disabled and has a title if the user is not logged in. -->
                        <button 
                            class="add-to-cart-btn" 
                            data-product-id="<?php echo htmlspecialchars($product['id']); ?>"
                            <?php if (!$isUserLoggedIn): ?>
                                disabled
                                title="Please log in to add items to your cart"
                            <?php endif; ?>
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

<!-- MODIFIED: JavaScript for Add to Cart functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    // Pass the PHP login status to JavaScript.
    const isUserLoggedIn = <?php echo json_encode($isUserLoggedIn); ?>;
    console.log(isUserLoggedIn)

    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            // A secondary check in JS to ensure non-logged-in users cannot add to cart.
            if (!isUserLoggedIn) {
                alert('You must be logged in to add items to your cart.');
                return; // Stop the function if not logged in.
            }

            const productId = this.dataset.productId;

            // Retrieve the current cart from localStorage, or initialize an empty array.
            let cart = JSON.parse(localStorage.getItem('cart')) || [];

            // MODIFICATION: Add the product ID to the cart on every click to track quantity.
            // The check to see if the item was already in the cart has been removed.
            cart.push(productId);

            // Store the updated cart back into localStorage.
            localStorage.setItem('cart', JSON.stringify(cart));

            alert('Product added to cart!');
        });
    });
});
</script>