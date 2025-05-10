<?php
session_start();
require_once 'config/database.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input data
if (!$data || !isset($data['items']) || !isset($data['total'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert into orders table
    $order_sql = "INSERT INTO orders (total_amount, status, order_date) VALUES (?, 'pending', NOW())";
    $stmt = $conn->prepare($order_sql);
    if (!$stmt) {
        throw new Exception("Error preparing order statement: " . $conn->error);
    }
    
    $stmt->bind_param("d", $data['total']);
    if (!$stmt->execute()) {
        throw new Exception("Error inserting order: " . $stmt->error);
    }
    
    $order_id = $conn->insert_id;

    // Insert order items
    $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($item_sql);
    if (!$stmt) {
        throw new Exception("Error preparing items statement: " . $conn->error);
    }

    foreach ($data['items'] as $item) {
        $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting order item: " . $stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();

    // Clear the cart session
    $_SESSION['cart'] = [];

    echo json_encode([
        'success' => true, 
        'message' => 'Order saved successfully', 
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Order Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error saving order: ' . $e->getMessage()
    ]);
} finally {
    // Close statements and connection
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 