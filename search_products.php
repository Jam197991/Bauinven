<?php
include 'includes/database.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check database connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search)) {
    echo json_encode(['success' => false, 'message' => 'Search query is required']);
    exit;
}

try {
    // Search products with category and inventory information
    $search_sql = "SELECT p.*, c.category_name, c.category_type, COALESCE(i.quantity, 0) as inventory_quantity
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id
                   LEFT JOIN inventory i ON p.product_id = i.product_id
                   WHERE (p.product_name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)
                   ORDER BY 
                       CASE 
                           WHEN p.product_name LIKE ? THEN 1
                           WHEN p.product_name LIKE ? THEN 2
                           ELSE 3
                       END,
                       p.product_name ASC
                   LIMIT 10";
    
    $search_term = "%$search%";
    $exact_start = "$search%";
    
    $stmt = $conn->prepare($search_sql);
    $stmt->bind_param("sssss", $search_term, $search_term, $search_term, $exact_start, $exact_start);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'description' => $row['description'],
            'price' => $row['price'],
            'category_id' => $row['category_id'],
            'category_name' => $row['category_name'],
            'category_type' => $row['category_type'],
            'image_url' => $row['image_url'],
            'inventory_quantity' => $row['inventory_quantity'],
            'is_out_of_stock' => $row['inventory_quantity'] <= 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error searching products: ' . $e->getMessage()
    ]);
}

$stmt->close();
?> 