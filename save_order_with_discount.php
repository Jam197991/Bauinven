<?php
session_start();
include 'includes/database.php';

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
    // Aggregate requested quantities from the flattened items list
    $requested_quantities = [];
    foreach ($data['items'] as $item) {
        $product_id = $item['id'];
        if (!isset($requested_quantities[$product_id])) {
            $requested_quantities[$product_id] = 0;
        }
        $requested_quantities[$product_id]++;
    }

    // Check inventory availability before processing order
    $out_of_stock_items = [];

    foreach ($requested_quantities as $product_id => $quantity) {
        // Check current inventory for this product
        $inventory_sql = "SELECT p.product_name, COALESCE(i.quantity, 0) as available_stock 
                         FROM products p 
                         LEFT JOIN inventory i ON p.product_id = i.product_id 
                         WHERE p.product_id = ?";
        $stmt = $conn->prepare($inventory_sql);
        if (!$stmt) {
            throw new Exception("Error preparing inventory check: " . $conn->error);
        }
        
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception("Error checking inventory: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $inventory_data = $result->fetch_assoc();
        
        if (!$inventory_data) {
            // This case should ideally not happen if data comes from a valid source
            continue;
        }

        $product_name = $inventory_data['product_name'];
        $available_stock = $inventory_data['available_stock'];
        
        // Check if requested quantity exceeds available stock
        if ($quantity > $available_stock) {
            $out_of_stock_items[] = [
                'name' => $product_name,
                'requested' => $quantity,
                'available' => $available_stock
            ];
        }
        
        $stmt->close();
    }
    
    // If there are out-of-stock items, return error
    if (!empty($out_of_stock_items)) {
        $error_message = "Some items are out of stock or have insufficient quantity:\n";
        foreach ($out_of_stock_items as $item) {
            $error_message .= "- {$item['name']}: Requested {$item['requested']}, Available {$item['available']}\n";
        }
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    // Prepare discount information
    $discount_type = isset($data['discount_info']['customerType']) ? $data['discount_info']['customerType'] : null;
    $discount_name = isset($data['discount_info']['customerName']) ? $data['discount_info']['customerName'] : null;
    $discount_id = isset($data['discount_info']['customerId']) ? $data['discount_info']['customerId'] : null;
    $discounted_products = isset($data['discount_info']['selectedProducts']) ? $data['discount_info']['selectedProducts'] : [];

    // Insert into orders table with discount information
    $order_sql = "INSERT INTO orders (total_amount, status, order_date, discount_type, discount_name, discount_id) 
                  VALUES (?, 'pending', NOW(), ?, ?, ?)";
    $stmt = $conn->prepare($order_sql);
    if (!$stmt) {
        throw new Exception("Error preparing order statement: " . $conn->error);
    }
    
    $stmt->bind_param("dsss", $data['total'], $discount_type, $discount_name, $discount_id);
    if (!$stmt->execute()) {
        throw new Exception("Error inserting order: " . $stmt->error);
    }
    
    $order_id = $conn->insert_id;

    // Insert order items and update inventory
    $item_sql = "INSERT INTO order_items (order_id, product_id, price, is_pwd_discounted, discounted_price) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($item_sql);
    if (!$stmt) {
        throw new Exception("Error preparing items statement: " . $conn->error);
    }

    foreach ($data['items'] as $item) {
        $is_discounted = isset($item['is_pwd_discounted']) ? (int)$item['is_pwd_discounted'] : 0;
        $discounted_price = isset($item['discounted_price']) && $item['discounted_price'] !== null ? $item['discounted_price'] : $item['price'];
        // Insert order item (one per row)
        $stmt->bind_param("iidid", $order_id, $item['id'], $item['price'], $is_discounted, $discounted_price);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting order item: " . $stmt->error);
        }
        // Update inventory (reduce stock by 1 for each item)
        $update_inventory_sql = "UPDATE inventory SET quantity = quantity - 1, updated_at = NOW() WHERE product_id = ?";
        $update_stmt = $conn->prepare($update_inventory_sql);
        if (!$update_stmt) {
            throw new Exception("Error preparing inventory update: " . $conn->error);
        }
        $update_stmt->bind_param("i", $item['id']);
        if (!$update_stmt->execute()) {
            throw new Exception("Error updating inventory: " . $update_stmt->error);
        }
        $update_stmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Clear the cart session
    $_SESSION['cart'] = [];

    echo json_encode([
        'success' => true, 
        'message' => 'Order saved successfully', 
        'order_id' => $order_id,
        'discount_applied' => !empty($discounted_products)
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