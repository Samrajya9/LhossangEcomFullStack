<?php 
// No need to require config.php again, header.php does it.
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$productId) {
    header('Location: products.php');
    exit();
}

// Config is included via header, so BASE_URL is available
require_once __DIR__ . '/config/config.php'; 
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
                <p class="category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="price">Rs. <?php echo number_format($product['price'], 2); ?></p>
                <div class="stock-status">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> left)</span>
                    <?php else: ?>
                        <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>
                <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="product-actions">
                    <input type="number" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" <?php if ($product['stock_quantity'] == 0) echo 'disabled'; ?>>
                    <a href="#" class="action-btn" <?php if ($product['stock_quantity'] == 0) echo 'style="background-color: grey; cursor: not-allowed;"'; ?>>
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </a>
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