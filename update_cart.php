<?php
session_start();
include 'includes/database.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['itemId'] ?? null;
$quantity = $data['quantity'] ?? null;

if (!$itemId || !$quantity) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Update cart in session
if (isset($_SESSION['cart'][$itemId])) {
    $_SESSION['cart'][$itemId]['quantity'] = $quantity;
    
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
        'message' => 'Cart updated successfully',
        'cart' => $cart
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found in cart'
    ]);
} 