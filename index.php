<?php 
require_once __DIR__ . '/config/config.php';


include __DIR__ . '/includes/header.php'; 
include __DIR__ . '/includes/navigation.php';
$pageTitle = "InfinityWaves - Premium Home Audio";

?>
<main>
    <section class="main-content-area">
        <div class="hero-section">
            <img src="<?=BASE_URL?>/assets/images/fluance.png" alt="High-fidelity home audio setup" class="hero-image" />
            <div class="hero-text">
                <h2>Experience Sound, Redefined.</h2>
                <p>
                    Welcome to InfinityWaves, your ultimate destination for premium home audio. We provide sleek, powerful, and reliable systems designed to match your vibe.
                </p>
            </div>
        </div>
        <div class="action-section">
            <a href="<?=BASE_URL?>/products.php" class="action-btn">Shop All Products</a>
        </div>
    </section>

    <section class="main-content-area" style="background-color: #f8f9fa;">
        <h2 class="section-title">Most Popular Gears</h2>
        <div class="products-grid">
            <!-- This section should ideally be populated dynamically from your API -->
            <div class="product-card">
                <div class="product-image"><img src="<?=BASE_URL?>/assets/images/picture2.png" alt="Speaker" class="product-photo"></div>
                <div class="product-info">
                    <h3>Sonos Era 100 Speaker</h3>
                    <p class="product-details">Wireless, Alexa Enabled Smart Speaker</p>
                    <p class="product-price">Rs. 24,999.00</p>
                    <a href="#" class="add-to-cart-btn">View Details</a>
                </div>
            </div>
             <div class="product-card">
                <div class="product-image"><img src="<?=BASE_URL?>/assets/images/picture1.png" alt="Home Theater" class="product-photo"></div>
                <div class="product-info">
                    <h3>Yamaha YHT-5960U</h3>
                    <p class="product-details">Home Theater System with 8K HDMI</p>
                    <p class="product-price">Rs. 89,900.00</p>
                    <a href="#" class="add-to-cart-btn">View Details</a>
                </div>
            </div>
             <div class="product-card">
                <div class="product-image"><img src="<?=BASE_URL?>/assets/images/product3.png" alt="BT Speaker" class="product-photo"></div>
                <div class="product-info">
                    <h3>Soundcore Boom 2</h3>
                    <p class="product-details">Powerful Outdoor Bluetooth Speaker</p>
                    <p class="product-price">Rs. 12,500.00</p>
                    <a href="#" class="add-to-cart-btn">View Details</a>
                </div>
            </div>
             <div class="product-card">
                <div class="product-image"><img src="<?=BASE_URL?>/assets/images/product4.jpg" alt="Projector Bundle" class="product-photo"></div>
                <div class="product-info">
                    <h3>Projector Bundle</h3>
                    <p class="product-details">120" Screen & Mini Bluetooth Speakers</p>
                    <p class="product-price">Rs. 45,000.00</p>
                    <a href="#" class="add-to-cart-btn">View Details</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>