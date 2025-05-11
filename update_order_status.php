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

// Update order status
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
}

$stmt->close();
$conn->close();
?> 