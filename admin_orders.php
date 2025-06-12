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

// Get all orders with their items
$orders_sql = "SELECT o.*, 
                      GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.product_name) SEPARATOR ', ') as items
               FROM orders o
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               LEFT JOIN products p ON oi.product_id = p.product_id
               GROUP BY o.order_id
               ORDER BY o.order_date DESC";
$orders_result = $conn->query($orders_sql);
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
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($orders_result->num_rows > 0) {
                    while($order = $orders_result->fetch_assoc()) {
                        $status_class = 'status-' . strtolower($order['status']);
                        echo '<tr>';
                        echo '<td>#' . $order['order_id'] . '</td>';
                        echo '<td>' . date('M d, Y h:i A', strtotime($order['order_date'])) . '</td>';
                        echo '<td>' . $order['items'] . '</td>';
                        echo '<td>₱' . number_format($order['total_amount'], 2) . '</td>';
                        echo '<td><span class="status-badge ' . $status_class . '">' . ucfirst($order['status']) . '</span></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" style="text-align: center;">No orders found</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html> 