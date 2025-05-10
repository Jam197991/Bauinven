<?php
$conn = new mysqli("localhost", "root", "", "bauapp_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get categories
$categories_sql = "SELECT * FROM categories";
$categories_result = $conn->query($categories_sql);

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
                    <a href="admin_orders.php" class="admin-btn">
                        <i class="fas fa-shopping-bag"></i> View Orders
                    </a>
                </div>
            </div>
        </header>

        <main>
            <div class="categories-section">
                <h2><i class="fas fa-tags"></i> Select Category</h2>
                <div class="categories-grid">
                    <?php
                    if ($categories_result->num_rows > 0) {
                        while($category = $categories_result->fetch_assoc()) {
                            $active_class = ($selected_category == $category['category_id']) ? 'active' : '';
                            echo '<a href="?category=' . $category['category_id'] . '" class="category-card ' . $active_class . '">';
                            echo '<img src="' . $category['image_url'] . '" alt="' . $category['category_name'] . '">';
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
                            echo '<img src="' . $product['image_url'] . '" alt="' . $product['product_name'] . '">';
                            echo '<h3>' . $product['product_name'] . '</h3>';
                            echo '<p>' . $product['description'] . '</p>';
                            echo '<p class="price">₱' . number_format($product['price'], 2) . '</p>';
                            echo '<div class="quantity-controls">';
                            echo '<button onclick="decreaseQuantity(' . $product['product_id'] . ')" class="decrease">−</button>';
                            echo '<span id="quantity-' . $product['product_id'] . '" class="quantity">0</span>';
                            echo '<button onclick="increaseQuantity(' . $product['product_id'] . ')" class="increase">+</button>';
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

        <!-- Mobile Cart Toggle -->
        <div class="cart-toggle">
            <div class="cart-summary">
                <i class="fas fa-shopping-cart"></i>
                <span id="mobile-cart-count">0</span> items
                <span id="mobile-cart-total">₱0.00</span>
            </div>
            <button onclick="toggleCart()" class="view-cart-btn"><i class="fas fa-eye"></i> View Cart</button>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="modal">
        <div class="modal-content">
            <div class="receipt">
                <div class="receipt-header">
                    <h2><i class="fas fa-leaf"></i> BauApp</h2>
                    <p>Bauland's Ordering System</p>
                    <p>Saravia,Koronadal City National Highway</p>
                    <p><i class="fas fa-phone"></i> 09150712443</p>
                </div>
                <div class="receipt-body">
                    <div class="receipt-items"></div>
                    <div class="receipt-total"></div>
                </div>
                <div class="receipt-footer">
                    <p><i class="fas fa-heart"></i> Thank you for your purchase!</p>
                    <p><i class="fas fa-calendar"></i> Date: <span id="receipt-date"></span></p>
                    <p><i class="fas fa-clock"></i> Time: <span id="receipt-time"></span></p>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="printReceipt()" class="print-btn"><i class="fas fa-print"></i> Print Receipt</button>
                <button onclick="closeReceipt()" class="close-btn"><i class="fas fa-times"></i> Close</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
