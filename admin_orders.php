<?php
session_start();

// Database connection configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "bauapp_db";

// Function to establish database connection with retry
function connectDB($host, $user, $pass, $dbname, $max_retries = 3) {
    $retries = 0;
    while ($retries < $max_retries) {
        try {
            $conn = new mysqli($host, $user, $pass, $dbname);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            return $conn;
        } catch (Exception $e) {
            $retries++;
            if ($retries == $max_retries) {
                die("Database connection failed after $max_retries attempts. Please check if MySQL is running and try again.");
            }
            sleep(1); // Wait 1 second before retrying
        }
    }
}

// Try to establish database connection
try {
    $conn = connectDB($db_host, $db_user, $db_pass, $db_name);
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - BauApp</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="img/bau.jpg" rel="icon">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background: var(--primary-color);
            color: white;
        }

        .orders-table tr:hover {
            background: var(--background-color);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .admin-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .back-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: var(--secondary-color);
            transform: translateX(-5px);
        }

        .chef-only-notice {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .back-btn {
                width: 100%;
                justify-content: center;
            }
        }

        .status-badge.completed {
            background: #28a745;
        }

        /* Discount Badge Styles */
        .discount-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            font-size: 0.8rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .discount-badge i {
            margin-right: 0.3rem;
            font-size: 0.9rem;
        }

        .discount-badge small {
            display: block;
            margin-top: 0.2rem;
            opacity: 0.9;
            font-size: 0.7rem;
        }

        .view-details-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .view-details-btn:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1><i class="fas fa-shopping-bag"></i> Order Management</h1>
        </div>

        <div class="chef-only-notice">
            <i class="fas fa-info-circle"></i>
            <span>Order status updates are managed by the chef. Please contact the chef for any status changes.</span>
        </div>

        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Original Total Amount</th>
                    <th>Discounted Amount</th>
                    <th>Discount Info</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all orders
                $orders_sql = "SELECT * FROM orders ORDER BY order_date DESC";
                $orders_result = $conn->query($orders_sql);
                if ($orders_result->num_rows > 0) {
                    while($order = $orders_result->fetch_assoc()) {
                        $order_id = $order['order_id'];
                        $status_class = 'status-' . strtolower($order['status']);

                        // Fetch order items for this order
                        $items_sql = "SELECT oi.*, p.product_name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ? ORDER BY oi.item_id";
                        $stmt = $conn->prepare($items_sql);
                        $stmt->bind_param("i", $order_id);
                        $stmt->execute();
                        $items_result = $stmt->get_result();

                        // Group items by product and discount status
                        $item_groups = [];
                        while ($item = $items_result->fetch_assoc()) {
                            $name = $item['product_name'];
                            $is_discounted = $item['is_pwd_discounted'] == 1;
                            $price = $item['price'];
                            $discounted_price = $item['discounted_price'];
                            $key = $name . '|' . $is_discounted . '|' . ($is_discounted ? $discounted_price : $price);
                            if (!isset($item_groups[$key])) {
                                $item_groups[$key] = [
                                    'name' => $name,
                                    'is_discounted' => $is_discounted,
                                    'price' => $price,
                                    'discounted_price' => $discounted_price,
                                    'count' => 0
                                ];
                            }
                            $item_groups[$key]['count']++;
                        }
                        $items_display = [];
                        $discounted_amount = 0;
                        $fixed_price_amount = 0;
                        foreach ($item_groups as $group) {
                            $qty = $group['count'];
                            $name = $group['name'];
                            $is_discounted = $group['is_discounted'];
                            $price = $group['price'];
                            $discounted_price = $group['discounted_price'];
                            if ($is_discounted) {
                                $items_display[] = $qty . 'x ' . $name . ' <span style="color:#28a745;">(Discounted)</span> - ₱' . number_format($discounted_price, 2);
                                $discounted_amount += $discounted_price * $qty;
                            } else {
                                $items_display[] = $qty . 'x ' . $name . ' - ₱' . number_format($price, 2);
                                $fixed_price_amount += $price * $qty;
                            }
                        }
                        $stmt->close();
                        $items_html = implode('<br>', $items_display);

                        // Prepare discount info display
                        $discount_info = '';
                        if ($order['discount_type'] && $order['discount_name']) {
                            $discount_info = '<div class="discount-badge">';
                            $discount_info .= '<i class="fas fa-percentage"></i> ' . $order['discount_type'];
                            $discount_info .= '<br><small>' . $order['discount_name'] . '</small>';
                            if ($order['discount_id']) {
                                $discount_info .= '<br><small>ID: ' . $order['discount_id'] . '</small>';
                            }
                            $discount_info .= '</div>';
                        }

                        echo '<tr>';
                        echo '<td>#' . $order['order_id'] . '</td>';
                        echo '<td>' . date('M d, Y h:i A', strtotime($order['order_date'])) . '</td>';
                        echo '<td>' . $items_html . '</td>';
                        echo '<td>₱' . number_format($order['total_amount'], 2) . '</td>';
                        $final_amount = $discounted_amount + $fixed_price_amount;
                        echo '<td style="color:#28a745;">₱' . number_format($final_amount, 2) . '</td>';
                        echo '<td>' . $discount_info . '</td>';
                        echo '<td><span class="status-badge ' . $status_class . '">' . ucfirst($order['status']) . '</span></td>';
                        echo '<td><a href="view_order_details.php?order_id=' . $order['order_id'] . '" class="view-details-btn">View Details</a></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="9" style="text-align: center;">No orders found</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html> 