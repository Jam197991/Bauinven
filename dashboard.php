<?php
$conn = new mysqli("localhost", "root", "", "bauapp_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get categories
$categories_sql = "SELECT * FROM categories";
$categories_result = $conn->query($categories_sql);

// Get products for selected category
$selected_category = isset($_GET['category']) ? $_GET['category'] : null;
$products_sql = "SELECT p.*, c.category_name, c.category_type 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id";
if ($selected_category) {
    $products_sql .= " WHERE p.category_id = " . intval($selected_category);
}
$products_result = $conn->query($products_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BauApp - Ordering System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-btn {
            background: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all var(--transition-speed);
            font-weight: 500;
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                width: 100%;
            }

            .admin-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Update cart container styles */
        .cart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 2rem;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
            opacity: 0.95;
            backdrop-filter: blur(10px);
        }

        /* Mobile Cart Styles */
        @media (max-width: 1023px) {
            .cart-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                top: auto;
                max-height: 60vh; /* Reduced from 80vh */
                border-radius: 15px 15px 0 0;
                z-index: 1000;
                transform: translateY(100%);
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                padding-bottom: 60px; /* Reduced from 80px */
            }

            .cart-container.expanded {
                transform: translateY(0);
            }

            /* Update cart toggle button */
            .cart-toggle {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.95);
                padding: 0.8rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                z-index: 1001;
                backdrop-filter: blur(10px);
            }

            .view-cart-btn {
                background: var(--accent-color);
                color: white;
                border: none;
                padding: 0.6rem 1.2rem;
                border-radius: 25px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.9rem;
            }
        }

        /* Update main grid layout */
        @media (min-width: 1024px) {
            main {
                grid-template-columns: 1fr 2fr 1fr;
                gap: 1.5rem;
            }

            .products-section {
                grid-column: 1 / -2; /* Make products section wider */
            }

            .cart-container {
                grid-column: -2 / -1;
            }
        }

        /* Add semi-transparent overlay when cart is open on mobile */
        .cart-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .cart-overlay.active {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1><i class="fas fa-leaf"></i> BauApp Ordering System</h1>
            <div class="header-actions">
                <div class="cart-summary">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count">0</span> items
                    <span id="cart-total">₱0.00</span>
                </div>
                <a href="admin_orders.php" class="admin-btn">
                    <i class="fas fa-shopping-bag"></i> View Orders
                </a>
            </div>
        </header>

        <main>
            <div class="categories-section">
                <h2><i class="fas fa-tags"></i> Select Category</h2>
                <div class="categories-grid">
                    <?php
                    if ($categories_result->num_rows > 0) {
                        while($category = $categories_result->fetch_assoc()) {
                            $active_class = ($selected_category == $category['category_id']) ? 'active' : '';
                            echo '<a href="?category=' . $category['category_id'] . '" class="category-card ' . $active_class . '">';
                            echo '<img src="' . $category['image_url'] . '" alt="' . $category['category_name'] . '">';
                            echo '<h3>' . $category['category_name'] . '</h3>';
                            echo '<span class="category-type"><i class="fas fa-' . ($category['category_type'] == 'vegetable' ? 'carrot' : 'apple-alt') . '"></i> ' . ucfirst($category['category_type']) . '</span>';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="products-section">
                <h2>
                    <?php if ($selected_category): ?>
                        <i class="fas fa-box"></i> Select Items
                    <?php else: ?>
                        <i class="fas fa-hand-pointer"></i> Please Select a Category
                    <?php endif; ?>
                </h2>
                <div class="products-grid">
                    <?php
                    if ($selected_category && $products_result->num_rows > 0) {
                        while($product = $products_result->fetch_assoc()) {
                            echo '<div class="product-card">';
                            echo '<img src="' . $product['image_url'] . '" alt="' . $product['product_name'] . '">';
                            echo '<h3>' . $product['product_name'] . '</h3>';
                            echo '<p>' . $product['description'] . '</p>';
                            echo '<p class="price">₱' . number_format($product['price'], 2) . '</p>';
                            echo '<div class="quantity-controls">';
                            echo '<button onclick="decreaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-minus"></i></button>';
                            echo '<span id="quantity-' . $product['product_id'] . '">0</span>';
                            echo '<button onclick="increaseQuantity(' . $product['product_id'] . ')" class="quantity-btn"><i class="fas fa-plus"></i></button>';
                            echo '</div>';
                            echo '<button onclick="addToCart(' . $product['product_id'] . ', \'' . $product['product_name'] . '\', ' . $product['price'] . ')" class="add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>';
                            echo '</div>';
                        }
                    } elseif ($selected_category) {
                        echo '<p class="no-products"><i class="fas fa-box-open"></i> No products found in this category.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="cart-container">
                <h2><i class="fas fa-shopping-basket"></i> Your Order</h2>
                <div id="cart-items"></div>
                <div class="cart-total">
                    <h3>Total: <span id="total-amount">₱0.00</span></h3>
                </div>
                <button onclick="checkout()" class="checkout-btn"><i class="fas fa-credit-card"></i> Proceed to Checkout</button>
            </div>
        </main>

        <!-- Mobile Cart Toggle -->
        <div class="cart-toggle">
            <div class="cart-summary">
                <i class="fas fa-shopping-cart"></i>
                <span id="mobile-cart-count">0</span> items
                <span id="mobile-cart-total">₱0.00</span>
            </div>
            <button onclick="toggleCart()" class="view-cart-btn"><i class="fas fa-eye"></i> View Cart</button>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="modal">
        <div class="modal-content">
            <div class="receipt">
                <div class="receipt-header">
                    <h2><i class="fas fa-leaf"></i> BauApp</h2>
                    <p>Bauland's Ordering System</p>
                    <p>Saravia,Koronadal City National Highway</p>
                    <p><i class="fas fa-phone"></i> 09150712443</p>
                </div>
                <div class="receipt-body">
                    <div class="receipt-items"></div>
                    <div class="receipt-total"></div>
                </div>
                <div class="receipt-footer">
                    <p><i class="fas fa-heart"></i> Thank you for your purchase!</p>
                    <p><i class="fas fa-calendar"></i> Date: <span id="receipt-date"></span></p>
                    <p><i class="fas fa-clock"></i> Time: <span id="receipt-time"></span></p>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="printReceipt()" class="print-btn"><i class="fas fa-print"></i> Print Receipt</button>
                <button onclick="closeReceipt()" class="close-btn"><i class="fas fa-times"></i> Close</button>
            </div>
        </div>
    </div>

    <!-- Add overlay div -->
    <div class="cart-overlay" id="cart-overlay"></div>

    <script>
        let cart = [];
        let quantities = {};

        // Load cart from localStorage on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedCart = localStorage.getItem('cart');
            const savedQuantities = localStorage.getItem('quantities');
            
            if (savedCart) {
                cart = JSON.parse(savedCart);
            }
            if (savedQuantities) {
                quantities = JSON.parse(savedQuantities);
            }
            updateCart();
        });

        // Save cart to localStorage whenever it changes
        function saveCart() {
            localStorage.setItem('cart', JSON.stringify(cart));
            localStorage.setItem('quantities', JSON.stringify(quantities));
        }

        function increaseQuantity(productId) {
            quantities[productId] = (quantities[productId] || 0) + 1;
            updateQuantityDisplay(productId);
            saveCart();
        }

        function decreaseQuantity(productId) {
            if (quantities[productId] > 0) {
                quantities[productId]--;
                updateQuantityDisplay(productId);
                saveCart();
            }
        }

        function updateQuantityDisplay(productId) {
            document.getElementById('quantity-' + productId).textContent = quantities[productId] || 0;
        }
        
        function addToCart(id, name, price) {
            const quantity = quantities[id] || 1;
            if (quantity <= 0) return;

            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({ id, name, price, quantity });
            }
            quantities[id] = 0;
            updateQuantityDisplay(id);
            updateCart();
            saveCart();
            showNotification(`${name} added to cart`);
        }

        function removeFromCart(id) {
            const item = cart.find(item => item.id === id);
            cart = cart.filter(item => item.id !== id);
            updateCart();
            saveCart();
            if (item) {
                showNotification(`${item.name} removed from cart`);
            }
        }

        function updateCart() {
            const cartItems = document.getElementById('cart-items');
            const cartCount = document.getElementById('cart-count');
            const cartTotal = document.getElementById('cart-total');
            const totalAmount = document.getElementById('total-amount');
            const mobileCartCount = document.getElementById('mobile-cart-count');
            const mobileCartTotal = document.getElementById('mobile-cart-total');
            
            cartItems.innerHTML = '';
            let total = 0;
            let count = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                count += item.quantity;

                cartItems.innerHTML += `
                    <div class="cart-item">
                        <div class="cart-item-details">
                            <span class="item-name">${item.name}</span>
                            <span class="item-quantity">x${item.quantity}</span>
                        </div>
                        <div class="cart-item-actions">
                            <span class="item-price">₱${itemTotal.toFixed(2)}</span>
                            <button onclick="removeFromCart(${item.id})" class="remove-btn">×</button>
                        </div>
                    </div>
                `;
            });

            cartCount.textContent = count;
            cartTotal.textContent = `₱${total.toFixed(2)}`;
            totalAmount.textContent = `₱${total.toFixed(2)}`;
            mobileCartCount.textContent = count;
            mobileCartTotal.textContent = `₱${total.toFixed(2)}`;
        }

        function checkout() {
            if (cart.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            // Prepare order data
            const orderData = {
                items: cart,
                total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
            };

            // Save order to database
            fetch('save_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showReceipt();
                    // Clear cart after successful order
                    cart = [];
                    quantities = {};
                    updateCart();
                    showNotification('Order placed successfully!', 'success');
                } else {
                    showNotification('Error saving order: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error saving order. Please try again.', 'error');
            });
        }

        function showReceipt() {
            const modal = document.getElementById('receipt-modal');
            const receiptItems = document.querySelector('.receipt-items');
            const receiptTotal = document.querySelector('.receipt-total');
            const receiptDate = document.getElementById('receipt-date');
            const receiptTime = document.getElementById('receipt-time');
            
            // Set date and time
            const now = new Date();
            receiptDate.textContent = now.toLocaleDateString();
            receiptTime.textContent = now.toLocaleTimeString();

            // Generate receipt items
            let total = 0;
            receiptItems.innerHTML = '';
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                receiptItems.innerHTML += `
                    <div class="receipt-item">
                        <span class="item-name">${item.name}</span>
                        <span class="item-quantity">${item.quantity}</span>
                        <span class="item-price">₱${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            });

            // Add total
            receiptTotal.innerHTML = `
                <div class="receipt-total-row">
                    <span>Total Amount:</span>
                    <span>₱${total.toFixed(2)}</span>
                </div>
            `;

            // Show modal
            modal.style.display = 'flex';
        }

        function closeReceipt() {
            const modal = document.getElementById('receipt-modal');
            modal.style.display = 'none';
        }

        function clearCart() {
            cart = [];
            quantities = {};
            updateCart();
        }

        function printReceipt() {
            const receiptContent = document.querySelector('.receipt').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=800');
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>BauApp Receipt</title>
                        <style>
                            body { font-family: monospace; }
                            .receipt { width: 300px; margin: 0 auto; }
                            .receipt-header { text-align: center; margin-bottom: 20px; }
                            .receipt-item { display: flex; justify-content: space-between; margin: 5px 0; }
                            .receipt-total-row { display: flex; justify-content: space-between; margin-top: 20px; font-weight: bold; }
                            .receipt-footer { text-align: center; margin-top: 20px; }
                            @media print {
                                body { margin: 0; padding: 20px; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="receipt">
                            ${receiptContent}
                        </div>
                    </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }

        // Update toggle cart function
        function toggleCart() {
            const cartContainer = document.querySelector('.cart-container');
            const overlay = document.getElementById('cart-overlay');
            cartContainer.classList.toggle('expanded');
            overlay.classList.toggle('active');
        }

        // Close cart when clicking overlay
        document.getElementById('cart-overlay').addEventListener('click', function() {
            const cartContainer = document.querySelector('.cart-container');
            if (cartContainer.classList.contains('expanded')) {
                toggleCart();
            }
        });

        // Close cart when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const cartContainer = document.querySelector('.cart-container');
            const cartToggle = document.querySelector('.cart-toggle');
            
            if (window.innerWidth <= 1023 && 
                !cartContainer.contains(event.target) && 
                !cartToggle.contains(event.target) &&
                cartContainer.classList.contains('expanded')) {
                cartContainer.classList.remove('expanded');
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('receipt-modal');
            if (event.target == modal) {
                closeReceipt();
            }
        }

        // Add loading state to buttons
        function setLoading(element, isLoading) {
            if (isLoading) {
                element.disabled = true;
                element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            } else {
                element.disabled = false;
                element.innerHTML = element.getAttribute('data-original-text');
            }
        }

        // Add smooth scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Add notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Add touch feedback
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>
