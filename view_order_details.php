<?php
session_start();
include 'includes/database.php';

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: admin_orders.php');
    exit;
}

// Get order details
$order_sql = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header('Location: admin_orders.php');
    exit;
}

// Get order items with product details
$items_sql = "SELECT oi.*, p.product_name, p.description
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              WHERE oi.order_id = ?
              ORDER BY oi.item_id";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order_id; ?> - BauApp</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-details-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .order-header h1 {
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn {
            background: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: var(--background-color);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
        }

        .info-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-color);
            font-size: 1rem;
        }

        .info-card p {
            margin: 0;
            color: var(--text-color);
            font-weight: 500;
        }

        .discount-info {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .discount-info h3 {
            color: white;
        }

        .discount-info p {
            color: white;
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .order-items-table th {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }

        .order-items-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .order-items-table tr:last-child td {
            border-bottom: none;
        }

        .order-items-table tr:hover {
            background: var(--background-color);
        }

        .discounted-item {
            background: rgba(40, 167, 69, 0.1);
        }

        .discounted-item td {
            border-left: 4px solid #28a745;
        }

        .price-info {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
        }

        .discounted-price {
            color: #28a745;
            font-weight: bold;
        }

        .regular-price {
            color: var(--primary-color);
            font-weight: bold;
        }

        .discount-badge {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
            margin-left: 0.5rem;
        }

        .cancel-item-btn {
            color: #dc3545;
            font-size: 1.2rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .cancel-item-btn:hover {
            color: #a71d2a;
        }

        .order-summary {
            background: var(--background-color);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .order-details-container {
                margin: 1rem;
                padding: 1rem;
            }

            .order-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .order-info {
                grid-template-columns: 1fr;
            }

            .order-items-table {
                font-size: 0.9rem;
            }

            .order-items-table th,
            .order-items-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <div class="order-header">
            <h1><i class="fas fa-receipt"></i> Order Details #<?php echo $order_id; ?></h1>
            <a href="admin_orders.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="order-info">
            <div class="info-card">
                <h3><i class="fas fa-calendar"></i> Order Date</h3>
                <p><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-tag"></i> Status</h3>
                <p><?php echo ucfirst($order['status']); ?></p>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-money-bill"></i> Total Amount</h3>
                <p>₱<?php echo number_format($order['total_amount'], 2); ?></p>
            </div>

            <?php if ($order['discount_type'] && $order['discount_name']): ?>
            <div class="info-card discount-info">
                <h3><i class="fas fa-percentage"></i> Discount Applied</h3>
                <p><strong><?php echo $order['discount_type']; ?></strong></p>
                <p><?php echo $order['discount_name']; ?></p>
                <?php if ($order['discount_id']): ?>
                <p>ID: <?php echo $order['discount_id']; ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <h2><i class="fas fa-list"></i> Order Items</h2>
        <table class="order-items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_items = 0;
                $total_amount = 0;
                
                while ($item = $items_result->fetch_assoc()) {
                    $is_discounted = $item['is_pwd_discounted'] == 1;
                    $display_price = $is_discounted ? $item['discounted_price'] : $item['price'];
                    $subtotal = $display_price;
                    $total_items++;
                    $total_amount += $subtotal;
                    
                    $row_class = $is_discounted ? 'discounted-item' : '';
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                            <?php if ($is_discounted): ?>
                                <span class="discount-badge">Discounted</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="price-info">
                                <?php if ($is_discounted): ?>
                                    <span class="original-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                    <span class="discounted-price">₱<?php echo number_format($item['discounted_price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="regular-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong>₱<?php echo number_format($subtotal, 2); ?></strong>
                        </td>
                        <td>
                            <a href="#" class="cancel-item-btn" title="Cancel Item" data-item-id="<?php echo $item['item_id']; ?>">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <div class="order-summary">
            <div class="summary-row">
                <span>Total Items:</span>
                <span><?php echo $total_items; ?> items</span>
            </div>
            <div class="summary-row">
                <span>Total Amount:</span>
                <span>₱<?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.cancel-item-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const itemId = this.dataset.itemId;
                const orderId = <?php echo $order_id; ?>;

                if (confirm('Are you sure you want to cancel this item? This action cannot be undone.')) {
                    fetch('cancel_order_item.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ item_id: itemId, order_id: orderId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Item cancelled successfully.');
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An unexpected error occurred. Please try again.');
                    });
                }
            });
        });
    });
    </script>
</body>
</html> 