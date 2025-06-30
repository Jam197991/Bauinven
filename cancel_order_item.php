<?php
session_start();



header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

$conn->begin_transaction();

try {
    // Get item details before deleting
    $item_sql = "SELECT order_id, price, discounted_price, is_pwd_discounted, product_id FROM order_items WHERE item_id = ?";
    $stmt = $conn->prepare($item_sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item_result = $stmt->get_result();
    $item = $item_result->fetch_assoc();

    if (!$item) {
        throw new Exception("Item not found.");
    }

    $order_id = $item['order_id'];
    $amount_to_subtract = $item['is_pwd_discounted'] ? $item['discounted_price'] : $item['price'];
    $product_id = $item['product_id'];

    // Delete the item from order_items
    $delete_sql = "DELETE FROM order_items WHERE item_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to delete item.");
    }

    // Update the total amount in the orders table
    $update_order_sql = "UPDATE orders SET total_amount = total_amount - ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_order_sql);
    $stmt->bind_param("di", $amount_to_subtract, $order_id);
    $stmt->execute();

    // Restore product stock
    $update_stock_sql = "UPDATE inventory SET quantity = quantity + 1 WHERE product_id = ?";
    $stmt = $conn->prepare($update_stock_sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Item cancelled successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 