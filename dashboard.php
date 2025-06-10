<?php
include 'includes/database.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get categories
$categories_sql = "SELECT * FROM categories";
$categories_result = $conn->query($categories_sql);

// Get best sellers
$best_sellers_sql = "SELECT p.*, c.category_name, c.category_type, 
                     COUNT(oi.product_id) as order_count
                     FROM products p 
                     JOIN categories c ON p.category_id = c.category_id
                     LEFT JOIN order_items oi ON p.product_id = oi.product_id
                     GROUP BY p.product_id
                     ORDER BY order_count DESC
                     LIMIT 4";
$best_sellers_result = $conn->query($best_sellers_sql);

// Get products for selected category
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;
$products_sql = "SELECT p.*, c.category_name, c.category_type, COALESCE(i.quantity, 0) as inventory_quantity, i.updated_at as inventory_updated_at
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN inventory i ON p.product_id = i.product_id";
if ($selected_category) {
    $products_sql .= " WHERE p.category_id = " . intval($selected_category);
}
$products_result = $conn->query($products_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BauApp - Ordering System</title>
    <link href="img/leaf.png" rel="icon">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .receipt-actions {
                display: none !important;
            }
            button, .btn, .add-to-cart-btn, .quantity-btn, .checkout-btn, .print-btn, .add-order-btn, .close-btn, .view-cart-btn, .admin-btn, .logout-btn {
                display: none !important;
            }
        }

        .best-sellers-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        .best-sellers-section h2 {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .best-sellers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .best-seller-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #eee;
            position: relative;
            overflow: hidden;
        }

        .best-seller-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .best-seller-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent-color);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .best-seller-icon {
            width: 100%;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background-color);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .best-seller-icon i {
            font-size: 3rem;
            color: var(--primary-color);
        }

        .best-seller-info h3 {
            color: var(--text-color);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .best-seller-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .best-seller-price {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .best-seller-stats {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .best-seller-stats i {
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .best-sellers-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        .best-sellers-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
        }

        .best-sellers-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .best-sellers-btn.active {
            background: var(--accent-color);
        }

        .best-sellers-section {
            display: none;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        .best-sellers-section.active {
            display: block;
        }

        @media (max-width: 768px) {
            .header-buttons {
                flex-direction: column;
                width: 100%;
            }

            .best-sellers-btn {
                width: 100%;
                justify-content: center;
            }
        }

        .header-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .view-cart-btn,
        .best-sellers-btn,
        .admin-btn {
            background: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
            text-decoration: none;
            font-size: 1rem;
            min-width: 140px;
            justify-content: center;
        }

        .view-cart-btn:hover,
        .best-sellers-btn:hover,
        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            color: white;
        }

        .view-cart-btn i,
        .best-sellers-btn i,
        .admin-btn i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .best-sellers-btn.active {
            background: var(--primary-color);
        }

        @media (max-width: 768px) {
            .header-buttons {
                flex-direction: column;
                width: 100%;
            }

            .view-cart-btn,
            .best-sellers-btn,
            .admin-btn {
                width: 100%;
            }
        }

        .logout-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .logout-overlay.active {
            opacity: 1;
        }

        .logout-container {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logout-leaf {
            position: absolute;
            font-size: 2.5rem;
            color: var(--primary-color);
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s ease;
        }

        .logout-leaf.main {
            font-size: 3.5rem;
            animation: mainLeafSpin 2s infinite ease-in-out;
            opacity: 1;
            transform: scale(1);
        }

        .logout-leaf.orbit {
            animation: orbitLeaf 3s infinite linear;
        }

        .logout-leaf.orbit:nth-child(2) {
            animation-delay: -1s;
        }

        .logout-leaf.orbit:nth-child(3) {
            animation-delay: -2s;
        }

        .logout-text {
            margin-top: 2rem;
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .logout-text.active {
            opacity: 1;
            transform: translateY(0);
        }

        .logout-dots {
            display: inline-block;
            animation: loadingDots 1.5s infinite;
        }

        @keyframes mainLeafSpin {
            0% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.1);
            }
            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        @keyframes orbitLeaf {
            0% {
                transform: rotate(0deg) translateX(40px) rotate(0deg) scale(0.8);
                opacity: 0.6;
            }
            100% {
                transform: rotate(360deg) translateX(40px) rotate(-360deg) scale(0.8);
                opacity: 0.6;
            }
        }

        @keyframes loadingDots {
            0%, 20% {
                content: '.';
            }
            40% {
                content: '..';
            }
            60%, 100% {
                content: '...';
            }
        }

        /* Smaller inventory info */
        .inventory-info {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 0.5rem;
            margin: 0.4rem 0;
            border-left: 3px solid #dee2e6;
        }

        .stock-label {
            display: block;
            font-size: 0.7rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0.2rem;
        }

        .stock-quantity {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: bold;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            margin-right: 0.2rem;
        }

        .stock-quantity.low-stock {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stock-quantity.medium-stock {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .stock-quantity.high-stock {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .stock-quantity.out-of-stock-badge {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }

        .out-of-stock-message {
            display: block;
            color: #dc3545;
            font-weight: 600;
            font-size: 0.7rem;
            margin-top: 0.2rem;
            text-align: center;
        }

        .out-of-stock-message i {
            margin-right: 0.3rem;
        }

        .stock-updated {
            display: block;
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.3rem;
            font-style: italic;
        }

        /* Update product card to accommodate inventory info */
        .product-card {
            background: white;
            border-radius: 8px;
            padding: 0.6rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
            min-height: 160px;
            max-width: 130px;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .product-card .product-icon {
            width: 35px;
            height: 35px;
            margin: 0 auto 0.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background-color);
            border-radius: 5px;
        }

        .product-card .product-icon i {
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .product-card h3 {
            font-size: 0.75rem;
            margin-bottom: 0.2rem;
            line-height: 1.1;
        }

        .product-card p {
            font-size: 0.65rem;
            margin-bottom: 0.3rem;
            line-height: 1.1;
            color: #666;
        }

        .product-card .price {
            font-size: 0.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.3rem;
        }

        /* Out of Stock Product Card Styles */
        .product-card.out-of-stock {
            opacity: 0.7;
            position: relative;
        }

        .product-card.out-of-stock::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 15px;
            pointer-events: none;
        }

        .quantity-controls.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .quantity-controls.disabled .quantity-btn {
            background: #6c757d;
            cursor: not-allowed;
        }

        .add-to-cart-btn.disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .add-to-cart-btn.disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* Smaller quantity controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin: 0.4rem 0;
        }

        .quantity-btn {
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            transition: all 0.2s ease;
        }

        .quantity-btn:hover:not(:disabled) {
            transform: scale(1.1);
            background: var(--primary-color);
        }

        .quantity-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .quantity-btn:disabled:hover {
            transform: none;
            background: #6c757d;
        }

        .quantity-controls span {
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            font-size: 0.8rem;
        }

        .quantity-controls.at-limit .quantity-btn[onclick*="increaseQuantity"] {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .quantity-controls.at-limit .quantity-btn[onclick*="increaseQuantity"]:hover {
            transform: none;
            background: #6c757d;
        }

        /* Smaller add to cart button */
        .add-to-cart-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 0.5rem 0.8rem;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            margin-top: auto;
        }

        .add-to-cart-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: var(--hover-shadow);
            background: var(--accent-color);
        }

        .add-to-cart-btn i {
            font-size: 0.7rem;
        }

        /* Update products grid for smaller cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.6rem;
            padding: 1rem 0;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 0.5rem;
            }
            
            .product-card {
                min-height: 140px;
                max-width: 110px;
                padding: 0.5rem;
            }
        }

        /* Compact Category Card Styles to match Product Cards */
        .category-card {
            display: block;
            text-decoration: none;
            color: var(--text-color);
            background: white;
            border-radius: 8px;
            padding: 0.6rem;
            transition: all 0.3s ease;
            border: 1px solid #eee;
            position: relative;
            overflow: hidden;
            min-height: 160px;
            max-width: 130px;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            border-color: var(--primary-color);
        }

        .category-card .category-icon {
            width: 35px;
            height: 35px;
            margin: 0 auto 0.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background-color);
            border-radius: 5px;
        }

        .category-card .category-icon i {
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .category-card h3 {
            font-size: 0.75rem;
            margin-bottom: 0.2rem;
            line-height: 1.1;
            text-align: center;
        }

        .category-card .category-type {
            display: block;
            padding: 0.2rem 0.5rem;
            background: var(--background-color);
            border-radius: 10px;
            font-size: 0.65rem;
            color: var(--primary-color);
            text-align: center;
            margin-top: 0.3rem;
        }

        .category-card.active {
            border-color: var(--primary-color);
            background: rgba(var(--primary-color-rgb), 0.05);
        }

        .category-card.active .category-icon {
            background: var(--primary-color);
        }

        .category-card.active .category-icon i {
            color: white;
        }

        /* Update categories grid to match products grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.6rem;
            padding: 1rem 0;
        }

        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 0.5rem;
            }
            
            .category-card {
                min-height: 140px;
                max-width: 110px;
                padding: 0.5rem;
            }
        }

        /* Smooth Transitions and Loading States */
        .products-grid {
            transition: all 0.3s ease-in-out;
        }

        .loading-products {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: var(--primary-color);
            font-size: 1rem;
            gap: 0.5rem;
        }

        .loading-products i {
            font-size: 1.2rem;
        }

        .category-card {
            transition: all 0.3s ease-in-out;
        }

        .category-card.active {
            transform: scale(1.05);
            border-color: var(--primary-color);
            background: rgba(var(--primary-color-rgb), 0.05);
            box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.2);
        }

        .products-section {
            transition: all 0.3s ease-in-out;
        }

        .products-section h2 {
            transition: all 0.3s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="logout-overlay">
        <div class="logout-container">
            <i class="fas fa-leaf logout-leaf main"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
        </div>
        <div class="logout-text">Going Back<span class="logout-dots">...</span></div>
    </div>
    <div class="dashboard-container">
        <header>
            <div class="header-top">
                <h1><i class="fas fa-leaf"></i> BauApp Ordering System</h1>
                <a href="index.php" class="logout-btn">
                    <i class="fas fa-power-off"></i>
                    <span>Back</span>
                </a>
            </div>
            <div class="header-actions">
                <div class="cart-summary">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count">0</span> items
                    <span id="cart-total">₱0.00</span>
                </div>
                <div class="header-buttons">
                    
                    <button onclick="toggleBestSellers()" class="best-sellers-btn">
                        <i class="fas fa-crown"></i> Best Sellers
                    </button>
                    <a href="admin_orders.php" class="admin-btn">
                        <i class="fas fa-shopping-bag"></i> View Orders
                    </a>
                </div>
            </div>
        </header>

        <main>
            <?php if (!$selected_category): ?>
            <div class="best-sellers-section">
                <h2><i class="fas fa-crown"></i> Best Sellers</h2>
                <div class="best-sellers-grid">
                    <?php
                    if ($best_sellers_result->num_rows > 0) {
                        $rank = 1;
                        while($product = $best_sellers_result->fetch_assoc()) {
                            echo '<div class="best-seller-card">';
                            echo '<div class="best-seller-badge"><i class="fas fa-fire"></i> #' . $rank . '</div>';
                            echo '<div class="best-seller-icon"><i class="fas fa-' . ($product['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . '"></i></div>';
                            echo '<div class="best-seller-info">';
                            echo '<h3>' . $product['product_name'] . '</h3>';
                            echo '<p>' . $product['description'] . '</p>';
                            echo '<div class="best-seller-price">₱' . number_format($product['price'], 2) . '</div>';
                            echo '<div class="best-seller-stats">';
                            echo '<i class="fas fa-shopping-cart"></i> ' . $product['order_count'] . ' orders';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            $rank++;
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="categories-section">
                <h2><i class="fas fa-tags"></i> Select Category</h2>
                <div class="categories-grid">
                    <?php
                    if ($categories_result->num_rows > 0) {
                        while($category = $categories_result->fetch_assoc()) {
                            $active_class = ($selected_category == $category['category_id']) ? 'active' : '';
                            echo '<a href="javascript:void(0)" onclick="selectCategory(' . $category['category_id'] . ', \'' . addslashes($category['category_name']) . '\')" class="category-card ' . $active_class . '" data-category-id="' . $category['category_id'] . '">';
                            echo '<div class="category-icon"><i class="fas fa-' . ($category['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . ' fa-3x"></i></div>';
                            echo '<h3>' . $category['category_name'] . '</h3>';
                            echo '<span class="category-type"><i class="fas fa-' . ($category['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . '"></i> ' . ucfirst($category['category_type']) . '</span>';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="products-section">
                <h2>
                    <?php if ($selected_category): ?>
                        <i class="fas fa-box"></i> Select Items
                    <?php else: ?>
                        <i class="fas fa-hand-pointer"></i> Please Select a Category
                    <?php endif; ?>
                </h2>
                <div class="products-grid">
                    <?php
                    if ($selected_category && $products_result->num_rows > 0) {
                        while($product = $products_result->fetch_assoc()) {
                            $is_out_of_stock = $product['inventory_quantity'] <= 0;
                            $card_class = $is_out_of_stock ? 'product-card out-of-stock' : 'product-card';
                            
                            echo '<div class="' . $card_class . '">';
                            echo '<div class="product-icon"><i class="fas fa-' . ($product['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . ' fa-3x"></i></div>';
                            echo '<h3>' . $product['product_name'] . '</h3>';
                            echo '<p>' . $product['description'] . '</p>';
                            echo '<p class="price">₱' . number_format($product['price'], 2) . '</p>';
                            
                            // Add inventory quantity display
                            $quantity_class = $product['inventory_quantity'] <= 10 ? 'low-stock' : ($product['inventory_quantity'] <= 30 ? 'medium-stock' : 'high-stock');
                            if ($is_out_of_stock) {
                                $quantity_class = 'out-of-stock-badge';
                            }
                            
                            echo '<div class="inventory-info">';
                            echo '<span class="stock-label">Available Stock:</span>';
                            echo '<span class="stock-quantity ' . $quantity_class . '">' . $product['inventory_quantity'] . ' Stocks</span>';
                            
                            if ($is_out_of_stock) {
                                echo '<div class="out-of-stock-message"><i class="fas fa-exclamation-triangle"></i> Out of Stock</div>';
                            }
                            
                            echo '</div>';
                            
                            if (!$is_out_of_stock) {
                                echo '<div class="quantity-controls">';
                                echo '<button onclick="decreaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-minus"></i></button>';
                                echo '<span id="quantity-' . $product['product_id'] . '">0</span>';
                                echo '<button onclick="increaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-plus"></i></button>';
                                echo '</div>';
                                echo '<button onclick="addToCart(' . $product['product_id'] . ', \'' . $product['product_name'] . '\', ' . $product['price'] . ')" class="add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>';
                            } else {
                                echo '<div class="quantity-controls disabled">';
                                echo '<button class="quantity-btn" disabled><i class="fas fa-minus"></i></button>';
                                echo '<span id="quantity-' . $product['product_id'] . '">0</span>';
                                echo '<button class="quantity-btn" disabled><i class="fas fa-plus"></i></button>';
                                echo '</div>';
                                echo '<button class="add-to-cart-btn disabled" disabled><i class="fas fa-ban"></i> Out of Stock</button>';
                            }
                            echo '</div>';
                        }
                    } elseif ($selected_category) {
                        echo '<p class="no-products"><i class="fas fa-box-open"></i> No products found in this category.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="cart-container">
                <h2><i class="fas fa-shopping-basket"></i> Your Order</h2>
                <div id="cart-items"></div>
                <div class="cart-total">
                    <h3>Total: <span id="total-amount">₱0.00</span></h3>
                </div>
                <button onclick="checkout()" class="checkout-btn"><i class="fas fa-credit-card"></i> Proceed to Checkout</button>
            </div>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="modal">
        <div class="receipt">
            <div class="receipt-header">
                <h2>BauApp</h2>
                <p>Your Receipt</p>
                <p>Date: <span id="receipt-date"></span></p>
                <p>Time: <span id="receipt-time"></span></p>
            </div>
            <div class="receipt-items"></div>
            <div class="receipt-total"></div>
            <div class="receipt-footer">
                <p>Thank you for your purchase!</p>
            </div>
            <div class="receipt-actions">
                <button onclick="printReceipt()" class="print-btn">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button onclick="addOrder()" class="add-order-btn">
                    <i class="fas fa-plus"></i> Add Order
                </button>
                <button onclick="closeReceipt()" class="close-btn">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function toggleBestSellers() {
            const bestSellersSection = document.querySelector('.best-sellers-section');
            const bestSellersBtn = document.querySelector('.best-sellers-btn');
            const categoriesSection = document.querySelector('.categories-section');
            const productsSection = document.querySelector('.products-section');
            const cartContainer = document.querySelector('.cart-container');
            
            // Close cart if it's open
            if (cartContainer.classList.contains('expanded')) {
                toggleCart();
            }
            
            bestSellersSection.classList.toggle('active');
            bestSellersBtn.classList.toggle('active');
            
            if (bestSellersSection.classList.contains('active')) {
                categoriesSection.style.display = 'none';
                productsSection.style.display = 'none';
                bestSellersBtn.innerHTML = '<i class="fas fa-times"></i> Close Best Sellers';
                // Clear any selected category
                const url = new URL(window.location.href);
                url.searchParams.delete('category');
                window.history.pushState({}, '', url);
            } else {
                categoriesSection.style.display = 'block';
                productsSection.style.display = 'block';
                bestSellersBtn.innerHTML = '<i class="fas fa-crown"></i> Best Sellers';
            }
        }

        // Modify the existing toggleCart function
        function toggleCart() {
            const cartContainer = document.querySelector('.cart-container');
            const mainContent = document.querySelector('main');
            const bestSellersSection = document.querySelector('.best-sellers-section');
            
            if (cart.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            if (cartContainer) {
                cartContainer.classList.toggle('expanded');
                
                // Update button text
                const viewCartBtn = document.querySelector('.view-cart-btn');
                if (viewCartBtn) {
                    viewCartBtn.innerHTML = cartContainer.classList.contains('expanded') 
                        ? '<i class="fas fa-times"></i> Close Cart' 
                        : '<i class="fas fa-shopping-cart"></i> View Cart';
                }

                // Hide best sellers when cart is expanded
                if (cartContainer.classList.contains('expanded')) {
                    bestSellersSection.classList.remove('active');
                    const bestSellersBtn = document.querySelector('.best-sellers-btn');
                    if (bestSellersBtn) {
                        bestSellersBtn.classList.remove('active');
                        bestSellersBtn.innerHTML = '<i class="fas fa-crown"></i> Best Sellers';
                    }
                }

                // Toggle main content layout
                if (mainContent) {
                    if (cartContainer.classList.contains('expanded')) {
                        mainContent.style.display = 'grid';
                        mainContent.style.gridTemplateColumns = '1fr 2fr 1fr';
                    } else {
                        mainContent.style.display = 'grid';
                        mainContent.style.gridTemplateColumns = '1fr 2fr';
                    }
                }
            }
        }

        // Add logout animation
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const logoutOverlay = document.querySelector('.logout-overlay');
            const logoutText = document.querySelector('.logout-text');
            
            // Show logout overlay with fade effect
            logoutOverlay.style.display = 'flex';
            setTimeout(() => {
                logoutOverlay.classList.add('active');
                logoutText.classList.add('active');
            }, 50);
            
            // Redirect after animation
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        });

        // Category selection with smooth transitions
        function selectCategory(categoryId, categoryName) {
            // Update URL without page refresh
            const url = new URL(window.location.href);
            url.searchParams.set('category', categoryId);
            window.history.pushState({}, '', url);

            // Update active category card
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('active');
            });
            document.querySelector(`[data-category-id="${categoryId}"]`).classList.add('active');

            // Show loading state
            const productsSection = document.querySelector('.products-section');
            const productsGrid = document.querySelector('.products-grid');
            
            // Add fade out effect
            productsGrid.style.opacity = '0';
            productsGrid.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                // Show loading indicator
                productsGrid.innerHTML = '<div class="loading-products"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
                
                // Load products via AJAX
                loadProducts(categoryId, categoryName);
            }, 300);
        }

        // AJAX function to load products
        function loadProducts(categoryId, categoryName) {
            const formData = new FormData();
            formData.append('category_id', categoryId);
            formData.append('action', 'load_products');

            fetch('load_products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const productsGrid = document.querySelector('.products-grid');
                
                // Fade out loading
                productsGrid.style.opacity = '0';
                productsGrid.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    // Update products grid
                    productsGrid.innerHTML = html;
                    
                    // Update section title
                    const productsSectionTitle = document.querySelector('.products-section h2');
                    productsSectionTitle.innerHTML = `<i class="fas fa-box"></i> ${categoryName}`;
                    
                    // Fade in new content
                    productsGrid.style.opacity = '1';
                    productsGrid.style.transform = 'translateY(0)';
                    
                    // Reinitialize quantity displays
                    Object.keys(quantities).forEach(productId => {
                        updateQuantityDisplay(productId);
                    });
                }, 200);
            })
            .catch(error => {
                console.error('Error loading products:', error);
                const productsGrid = document.querySelector('.products-grid');
                productsGrid.innerHTML = '<p class="no-products"><i class="fas fa-exclamation-triangle"></i> Error loading products. Please try again.</p>';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
            });
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const categoryId = urlParams.get('category');
            
            if (categoryId) {
                // Find category name and reload
                const categoryCard = document.querySelector(`[data-category-id="${categoryId}"]`);
                if (categoryCard) {
                    const categoryName = categoryCard.querySelector('h3').textContent;
                    selectCategory(categoryId, categoryName);
                }
            } else {
                // No category selected, show default state
                document.querySelectorAll('.category-card').forEach(card => {
                    card.classList.remove('active');
                });
                const productsSectionTitle = document.querySelector('.products-section h2');
                productsSectionTitle.innerHTML = '<i class="fas fa-hand-pointer"></i> Please Select a Category';
                const productsGrid = document.querySelector('.products-grid');
                productsGrid.innerHTML = '';
                productsGrid.style.opacity = '1';
                productsGrid.style.transform = 'translateY(0)';
            }
        });
    </script>
</body>
</html>
