<?php
// /opt/lampp/htdocs/infinityAdmin/index.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/authFunctions.php';

$pageTitle = "InfinityWaves - Premium Home Audio";

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';

// --- Fetch "Most Popular" products (first 4) ---
$popular_api_url = BASE_URL . '/api/products.php?action=all&limit=4&offset=0';
$popular_response_json = @file_get_contents($popular_api_url);
$popular_response_data = $popular_response_json ? json_decode($popular_response_json, true) : null;

$popular_products = [];
if ($popular_response_data && $popular_response_data['success'] && isset($popular_response_data['data']['data'])) {
    $popular_products = $popular_response_data['data']['data'];
}

// --- Fetch "Explore Our Collection" products (next 4) ---
$collection_api_url = BASE_URL . '/api/products.php?action=all&limit=4&offset=4';
$collection_response_json = @file_get_contents($collection_api_url);
$collection_response_data = $collection_response_json ? json_decode($collection_response_json, true) : null;

$collection_products = [];
if ($collection_response_data && $collection_response_data['success'] && isset($collection_response_data['data']['data'])) {
    $collection_products = $collection_response_data['data']['data'];
}

$isUserLoggedIn = isLoggedIn();

?>
<main>
    <!-- 1. Expanded Hero Section -->
    <section class="main-content-area">
        <div class="container">
            <div class="hero-section">
                <div class="hero-image">
                    <img src="<?= BASE_URL ?>/assets/images/fluance.png" alt="High-fidelity home audio setup" />
                </div>
                <div class="hero-text">
                    <p><b>Infinity Waves</b> - Hi, welcome to Infinity Waves your ultimate destination for premium home audio solutions. Whether you’re looking to upgrade your living room, set up a personal studio, or simply enjoy music the way it was meant to be heard, we’ve got you covered. Dive into our collection of sleek, powerful, and reliable audio systems designed to match your vibe and lifestyle. At InfinityWaves, it’s not just about sound—it’s about creating an experience you’ll feel in every beat. 🌊</p>
                    <div class="action-section">
                        <a href="<?= BASE_URL ?>/products.php" class="action-btn">Shop All Products</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- (The rest of your index.php content remains the same) -->

    <!-- 2. Most Popular Gears Section -->
    <section class="main-content-area">
        <div class="container">
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
                <div class="centered-container with-background" style="padding: 40px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 40px; color: #ccc; margin-bottom: 15px;"></i>
                    <p>No popular products to display at the moment. Please check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 3. Explore Our Collection Section (with Products) -->
    <section class="main-content-area">
        <div class="container">
            <h2 class="section-title">Explore Our Collection</h2>
            
            <?php if (!empty($collection_products)) : ?>
                <div class="products-grid">
                    <?php foreach ($collection_products as $product) : ?>
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
                <div class="action-section">
                    <a href="<?= BASE_URL ?>/products.php" class="action-btn">View All Products</a>
                </div>
            <?php else : ?>
                <div class="centered-container" style="padding: 40px;">
                    <p>More of our collection will be featured here soon. Stay tuned!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>