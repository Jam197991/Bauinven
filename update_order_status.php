<?php
session_start();

// Add error logging for debugging
error_log("update_order_status.php called");
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in and is a chef
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chef') {
    error_log("Unauthorized access attempt - user_id: " . ($_SESSION['user_id'] ?? 'not set') . ", role: " . ($_SESSION['role'] ?? 'not set'));
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Include database connection
include 'includes/database.php';

// Get JSON data
$input = file_get_contents('php://input');
error_log("Raw input: " . $input);
$data = json_decode($input, true);
error_log("Decoded data: " . print_r($data, true));

if (!isset($data['order_id']) || !isset($data['status'])) {
    error_log("Missing required fields - order_id: " . ($data['order_id'] ?? 'not set') . ", status: " . ($data['status'] ?? 'not set'));
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

// Check database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$order_id = intval($data['order_id']);
$status = $conn->real_escape_string($data['status']);

error_log("Processing order_id: $order_id, status: $status");

// Validate status
$valid_statuses = ['pending', 'processing', 'completed'];
if (!in_array($status, $valid_statuses)) {
    error_log("Invalid status: $status");
    die(json_encode(['success' => false, 'message' => 'Invalid status']));
}

try {
    // Check if order exists
    $check_query = "SELECT order_id, status FROM orders WHERE order_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $order = $check_result->fetch_assoc();
    $check_stmt->close();

    if (!$order) {
        error_log("Order not found: $order_id");
        throw new Exception('Order not found');
    }

    error_log("Current order status: " . $order['status'] . ", New status: $status");

    // Update order status
    $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $status, $order_id);

    if (!$update_stmt->execute()) {
        error_log("Failed to update order status: " . $update_stmt->error);
        throw new Exception('Failed to update order status');
    }

    error_log("Order status updated successfully");
    
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$update_stmt->close();
?> 