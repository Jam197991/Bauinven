<?php
session_start();

// Check if user is logged in and is a chef
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chef') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$conn = new mysqli("localhost", "root", "", "bauapp_db");

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$order_id = intval($data['order_id']);
$status = $conn->real_escape_string($data['status']);

// Validate status
$valid_statuses = ['pending', 'processing', 'completed'];
if (!in_array($status, $valid_statuses)) {
    die(json_encode(['success' => false, 'message' => 'Invalid status']));
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current order status to check if we need to handle inventory
    $current_status_query = "SELECT status FROM orders WHERE order_id = ?";
    $current_stmt = $conn->prepare($current_status_query);
    $current_stmt->bind_param("i", $order_id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    $current_order = $current_result->fetch_assoc();
    $current_stmt->close();

    if (!$current_order) {
        throw new Exception('Order not found');
    }

    $previous_status = $current_order['status'];

    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update order status');
    }

    // If status is being changed to 'completed' and it wasn't completed before
    if ($status === 'completed' && $previous_status !== 'completed') {
        // Get order items
        $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        // Update inventory for each item
        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $order_quantity = $item['quantity'];

            // Check current inventory
            $inventory_query = "SELECT quantity FROM inventory WHERE product_id = ?";
            $inventory_stmt = $conn->prepare($inventory_query);
            $inventory_stmt->bind_param("i", $product_id);
            $inventory_stmt->execute();
            $inventory_result = $inventory_stmt->get_result();
            $inventory_row = $inventory_result->fetch_assoc();

            if ($inventory_row) {
                $current_quantity = $inventory_row['quantity'];
                $new_quantity = $current_quantity - $order_quantity;

                // Check if we have enough inventory
                if ($new_quantity < 0) {
                    throw new Exception("Insufficient inventory for product ID: $product_id");
                }

                // Update inventory
                $update_inventory_query = "UPDATE inventory SET quantity = ?, updated_at = NOW() WHERE product_id = ?";
                $update_inventory_stmt = $conn->prepare($update_inventory_query);
                $update_inventory_stmt->bind_param("ii", $new_quantity, $product_id);
                
                if (!$update_inventory_stmt->execute()) {
                    throw new Exception("Failed to update inventory for product ID: $product_id");
                }
                $update_inventory_stmt->close();
            } else {
                throw new Exception("Product not found in inventory: $product_id");
            }
            $inventory_stmt->close();
        }
        $items_stmt->close();
    }

    // If status is being changed from 'completed' to something else, restore inventory
    if ($previous_status === 'completed' && $status !== 'completed') {
        // Get order items
        $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        // Restore inventory for each item
        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $order_quantity = $item['quantity'];

            // Update inventory (add back the quantity)
            $update_inventory_query = "UPDATE inventory SET quantity = quantity + ?, updated_at = NOW() WHERE product_id = ?";
            $update_inventory_stmt = $conn->prepare($update_inventory_query);
            $update_inventory_stmt->bind_param("ii", $order_quantity, $product_id);
            
            if (!$update_inventory_stmt->execute()) {
                throw new Exception("Failed to restore inventory for product ID: $product_id");
            }
            $update_inventory_stmt->close();
        }
        $items_stmt->close();
    }

    // Commit transaction
    $conn->commit();
    
    $message = 'Order status updated successfully';
    if ($status === 'completed' && $previous_status !== 'completed') {
        $message .= ' and inventory updated';
    } elseif ($previous_status === 'completed' && $status !== 'completed') {
        $message .= ' and inventory restored';
    }
    
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 