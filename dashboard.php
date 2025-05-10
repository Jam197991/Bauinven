<?php
$conn = new mysqli("localhost", "root", "", "bauapp_db");

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
$products_sql = "SELECT p.*, c.category_name, c.category_type 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id";
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="header-top">
                <h1><i class="fas fa-leaf"></i> BauApp Ordering System</h1>
                <a href="index.php" class="logout-btn">
                    <i class="fas fa-power-off"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="header-actions">
                <div class="cart-summary">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count">0</span> items
                    <span id="cart-total">₱0.00</span>
                </div>
                <div class="header-buttons">
                    <button onclick="toggleCart()" class="view-cart-btn">
                        <i class="fas fa-shopping-cart"></i> View Cart
                    </button>
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
                            echo '<a href="?category=' . $category['category_id'] . '" class="category-card ' . $active_class . '">';
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
                            echo '<div class="product-card">';
                            echo '<div class="product-icon"><i class="fas fa-' . ($product['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . ' fa-3x"></i></div>';
                            echo '<h3>' . $product['product_name'] . '</h3>';
                            echo '<p>' . $product['description'] . '</p>';
                            echo '<p class="price">₱' . number_format($product['price'], 2) . '</p>';
                            echo '<div class="quantity-controls">';
                            echo '<button onclick="decreaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-minus"></i></button>';
                            echo '<span id="quantity-' . $product['product_id'] . '">0</span>';
                            echo '<button onclick="increaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-plus"></i></button>';
                            echo '</div>';
                            echo '<button onclick="addToCart(' . $product['product_id'] . ', \'' . $product['product_name'] . '\', ' . $product['price'] . ')" class="add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>';
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
    </script>
</body>
</html>
