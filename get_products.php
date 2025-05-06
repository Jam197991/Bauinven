<?php
$conn = new mysqli("localhost", "root", "", "bauapp_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

$products_sql = "SELECT p.*, c.category_name, c.category_type 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id
                 WHERE p.category_id = ?";

$stmt = $conn->prepare($products_sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($product = $result->fetch_assoc()) {
        echo '<div class="product-card">';
        echo '<img src="' . $product['image_url'] . '" alt="' . $product['product_name'] . '">';
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
} else {
    echo '<p class="no-products"><i class="fas fa-box-open"></i> No products found in this category.</p>';
}

$stmt->close();
$conn->close();
?> 