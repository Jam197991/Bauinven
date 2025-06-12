<?php
include 'includes/database.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get product ID
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    // Get product category information
    $sql = "SELECT p.product_id, p.product_name, c.category_id, c.category_name, c.category_type
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'product_id' => $product['product_id'],
            'product_name' => $product['product_name'],
            'category_id' => $product['category_id'],
            'category_name' => $product['category_name'],
            'category_type' => $product['category_type']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error getting product category: ' . $e->getMessage()
    ]);
}

$stmt->close();
?> 