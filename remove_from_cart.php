<?php
session_start();
include 'includes/database.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['itemId'] ?? null;

if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Remove item from cart
if (isset($_SESSION['cart'][$itemId])) {
    unset($_SESSION['cart'][$itemId]);
    
    // Get updated cart data
    $cart = [];
    foreach ($_SESSION['cart'] as $id => $item) {
        $cart[] = [
            'id' => $id,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'image' => $item['image']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Item removed successfully',
        'cart' => $cart
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found in cart'
    ]);
} 