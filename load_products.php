<?php
include 'includes/database.php';

// Set header to return HTML
header('Content-Type: text/html; charset=utf-8');

// Get category ID from POST request
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

if ($category_id <= 0) {
    echo '<p class="no-products"><i class="fas fa-exclamation-triangle"></i> Invalid category selected.</p>';
    exit;
}

// Get products for the selected category
$products_sql = "SELECT p.*, c.category_name, c.category_type, COALESCE(i.quantity, 0) as inventory_quantity, i.updated_at as inventory_updated_at
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN inventory i ON p.product_id = i.product_id
                 WHERE p.category_id = ?";
$stmt = $conn->prepare($products_sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$products_result = $stmt->get_result();

if ($products_result->num_rows > 0) {
    while($product = $products_result->fetch_assoc()) {
        $is_out_of_stock = $product['inventory_quantity'] <= 0;
        $card_class = $is_out_of_stock ? 'product-card out-of-stock' : 'product-card';
        
        echo '<div class="' . $card_class . '" data-product-id="' . $product['product_id'] . '">';
        
        // Display image if available, otherwise show icon
        if (!empty($product['image_url']) && file_exists($product['image_url'])) {
            echo '<div class="product-icon">';
            echo '<img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['product_name']) . '" class="product-image">';
            echo '</div>';
        } else {
            echo '<div class="product-icon"><i class="fas fa-' . ($product['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . ' fa-3x"></i></div>';
        }
        
        echo '<h3>' . htmlspecialchars($product['product_name']) . '</h3>';
        echo '<p>' . htmlspecialchars($product['description']) . '</p>';
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
            echo '<button onclick="addToCart(' . $product['product_id'] . ', \'' . addslashes($product['product_name']) . '\', ' . $product['price'] . ')" class="add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>';
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
} else {
    echo '<p class="no-products"><i class="fas fa-box-open"></i> No products found in this category.</p>';
}

$stmt->close();
$conn->close();
?> 