<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/includes/header.php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'InfinityWaves'; ?></title>
    
    <!-- Consolidated Stylesheet -->
    <link rel="stylesheet" href="<?=BASE_URL?>/assets/css/user-style.css">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="wrapper">
    <!-- HEADER -->
    <header class="header">
        <div class="container">
            <a href="<?=BASE_URL?>/index.php" class="logo">
                <img src="<?=BASE_URL?>/assets/images/innfinity.png" alt="Infinity Logo" class="logo-img">
            </a>
            
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search for speakers, home theaters..." autocomplete="off">
                <button class="search-btn" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
                <!-- Container for search results -->
                <div class="search-results-container" id="search-results"></div>
            </div>

            <div class="auth-buttons">
                <a href="<?=BASE_URL?>/profile.php" aria-label="My Account"><i class="fas fa-user"></i></a>
                <a href="#" aria-label="Notifications"><i class="fas fa-bell"></i></a>
                <a href="<?=BASE_URL?>/carts.php" aria-label="Shopping Cart"><i class="fas fa-shopping-cart"></i></a>
            </div>
        </div>
    </header>

<!-- ==========================================================================
   Live Search JavaScript
   ========================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const searchResultsContainer = document.getElementById('search-results');
    const baseUrl = '<?= BASE_URL ?>'; // Get base URL from PHP

    // Debounce function to limit how often the API is called while typing
    const debounce = (func, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    };

    // Function to perform the search via API call
    const performSearch = async (query) => {
        // Don't search if query is too short
        if (query.length < 3) {
            searchResultsContainer.innerHTML = '';
            searchResultsContainer.style.display = 'none';
            return;
        }

        try {
            // Fetch results from the API
            const response = await fetch(`${baseUrl}/api/products.php?action=all&limit=5&q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json();
            
            searchResultsContainer.innerHTML = ''; // Clear previous results
            
            // If we have results, display them
            if (result.success && result.data.data.length > 0) {
                const products = result.data.data;
                products.forEach(product => {
                    const item = document.createElement('a');
                    item.href = `${baseUrl}/single-product.php?id=${product.id}`;
                    item.classList.add('search-result-item');
                    item.innerHTML = `
                        <img src="${baseUrl}${product.image_url || '/assets/images/placeholder.png'}" alt="${product.name}" class="search-result-img">
                        <div class="search-result-info">
                            <span class="search-result-name">${product.name}</span>
                            <span class="search-result-price">$${parseFloat(product.price).toFixed(2)}</span>
                        </div>
                    `;
                    searchResultsContainer.appendChild(item);
                });
            } else {
                // Otherwise, show the "not found" message with a redirect link
                searchResultsContainer.innerHTML = `
                    <div class="search-no-results">
                        <p>No products found for "<strong>${query}</strong>"</p>
                        <a href="${baseUrl}/products.php" class="action-btn-sm">View All Products</a>
                    </div>
                `;
            }
            searchResultsContainer.style.display = 'block';

        } catch (error) {
            console.error('Search failed:', error);
            searchResultsContainer.innerHTML = '<div class="search-no-results"><p>Error during search. Please try again.</p></div>';
            searchResultsContainer.style.display = 'block';
        }
    };

    // Add event listener to the input, using debounce
    searchInput.addEventListener('input', debounce(e => performSearch(e.target.value.trim()), 300));

    // Add a global click listener to hide results when clicking outside the search area
    document.addEventListener('click', function(event) {
        const isClickInside = searchInput.contains(event.target) || searchResultsContainer.contains(event.target);
        if (!isClickInside) {
            searchResultsContainer.style.display = 'none';
        }
    });
});
</script>