<?php
session_start();
require_once 'config/database.php';

// Get cart items from session or database
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Cart - BauApp</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="cart-page-container">
        <header class="cart-header">
            <div class="header-content">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Menu
                </a>
                <h1><i class="fas fa-shopping-cart"></i> Your Cart</h1>
            </div>
        </header>

        <main class="cart-content">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket"></i>
                    <h2>Your cart is empty</h2>
                    <p>Add some items to your cart to see them here</p>
                    <a href="dashboard.php" class="continue-shopping-btn">
                        <i class="fas fa-utensils"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-items-container">
                    <?php foreach ($cart_items as $item): 
                        $item_total = $item['price'] * $item['quantity'];
                        $total += $item_total;
                    ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                            </div>
                            <div class="item-details">
                                <h3><?php echo $item['name']; ?></h3>
                                <p class="item-price">₱<?php echo number_format($item['price'], 2); ?></p>
                                <div class="quantity-controls">
                                    <button onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')" class="quantity-btn">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="quantity"><?php echo $item['quantity']; ?></span>
                                    <button onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')" class="quantity-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="item-actions">
                                <p class="item-total">₱<?php echo number_format($item_total, 2); ?></p>
                                <button onclick="removeItem(<?php echo $item['id']; ?>)" class="remove-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Service Fee:</span>
                        <span>₱0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="cart-actions">
                    <button onclick="window.location.href='dashboard.php'" class="continue-shopping-btn">
                        <i class="fas fa-utensils"></i> Continue Shopping
                    </button>
                    <button onclick="proceedToCheckout()" class="checkout-btn">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </button>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="js/cart.js"></script>
</body>
</html> 