<?php
// /opt/lampp/htdocs/infinityAdmin/index.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/authFunctions.php';

$pageTitle = "InfinityWaves - Premium Home Audio";

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';

// Fetch the 4 most recent products from the API for the "Most Popular" section
$api_url = BASE_URL . '/api/products.php?action=all&limit=4';
$response_json = @file_get_contents($api_url);
$response_data = $response_json ? json_decode($response_json, true) : null;

$popular_products = [];
if ($response_data && $response_data['success'] && isset($response_data['data']['data'])) {
    $popular_products = $response_data['data']['data'];
}
$isUserLoggedIn = isLoggedIn();

?>
<main>
    <section class="main-content-area">
        <div class="hero-section">
            <img src="<?= BASE_URL ?>/assets/images/fluance.png" alt="High-fidelity home audio setup" class="hero-image" />
            <div class="hero-text">
                <h2>Experience Sound, Redefined.</h2>
                <p>
                    Welcome to InfinityWaves, your ultimate destination for premium home audio. We provide sleek, powerful, and reliable systems designed to match your vibe.
                </p>
            </div>
        </div>
        <div class="action-section">
            <a href="<?= BASE_URL ?>/products.php" class="action-btn">Shop All Products</a>
        </div>
    </section>

    <section class="main-content-area" style="background-color: #f8f9fa;">
        <h2 class="section-title">Most Popular Gears</h2>
        
        <?php if (!empty($popular_products)) : ?>
            <div class="products-grid">
                <?php foreach ($popular_products as $product) : ?>
                    <div class="product-card">
                        <a href="single-product.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                            <div class="product-image">
                                <?php if (!empty($product['image_url'])) : ?>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-photo">
                                <?php else : ?>
                                    <i class="fas fa-box-open" style="font-size: 50px; color: #ccc;"></i>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-details"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                            <p class="product-price"><?= CURRENCY ?><?php echo number_format($product['price'], 2); ?></p>
                            <a href="single-product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="add-to-cart-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="centered-container" style="padding: 40px;">
                <i class="fas fa-exclamation-circle" style="font-size: 40px; color: #ccc; margin-bottom: 15px;"></i>
                <p>No popular products to display at the moment. Please check back later!</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>```

