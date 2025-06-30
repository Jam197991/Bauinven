<?php
date_default_timezone_set('Asia/Manila'); // Set to your local timezone
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include 'includes/database.php';
// Check if user is logged in and is a chef
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chef') {
    header('Location: index.php');
    exit();
}

// Debug session information
error_log("Chef.php - Session data: " . print_r($_SESSION, true));

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get orders with their items
$today = date('Y-m-d');
$orders_sql = "SELECT o.*, 
               GROUP_CONCAT(p.product_name SEPARATOR ', ') as items
               FROM orders o
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               LEFT JOIN products p ON oi.product_id = p.product_id
               WHERE DATE(o.order_date) = '$today'
               GROUP BY o.order_id
               ORDER BY o.order_date DESC";

error_log("Orders SQL Query: " . $orders_sql);
$orders_result = $conn->query($orders_sql);

if (!$orders_result) {
    error_log("Orders query error: " . $conn->error);
    die("Error fetching orders: " . $conn->error);
}

error_log("Number of orders found: " . $orders_result->num_rows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chef Dashboard - BauApp</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="img/bau.jpg" rel="icon">
    <style>
        :root {
            --primary-color: #2e7d32;
            --accent-color: #43a047;
            --text-color: #333;
            --background-color: #f5f5f5;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            --transition-speed: 0.3s;
        }

        body {
            background: var(--background-color);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .chef-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .chef-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        .chef-header h1 {
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            font-size: 1.8rem;
        }

        .chef-header .welcome-text {
            color: #666;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .logout-btn {
            background: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
            text-decoration: none;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            background: var(--primary-color);
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            animation: fadeIn 0.5s ease-out;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
            opacity: 0;
            transition: opacity var(--transition-speed);
        }

        .order-card:hover::before {
            opacity: 1;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .order-items {
            margin-bottom: 1rem;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }

        .order-items p {
            color: #666;
            margin: 0;
            line-height: 1.6;
        }

        .order-total {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            color: var(--text-color);
            background: white;
            cursor: pointer;
            transition: all var(--transition-speed);
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.1);
        }

        .status-pending {
            color: #ffc107;
        }

        .status-processing {
            color: #17a2b8;
        }

        .status-completed {
            color: #28a745;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            display: none;
            animation: slideIn 0.3s ease-out;
            z-index: 1000;
            box-shadow: var(--card-shadow);
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .notification i {
            margin-right: 0.5rem;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            color: #666;
        }

        .no-orders i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .logout-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .logout-overlay.active {
            opacity: 1;
        }

        .logout-container {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logout-leaf {
            position: absolute;
            font-size: 2.5rem;
            color: var(--primary-color);
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s ease;
        }

        .logout-leaf.main {
            font-size: 3.5rem;
            animation: mainLeafSpin 2s infinite ease-in-out;
            opacity: 1;
            transform: scale(1);
        }

        .logout-leaf.orbit {
            animation: orbitLeaf 3s infinite linear;
        }

        .logout-leaf.orbit:nth-child(2) {
            animation-delay: -1s;
        }

        .logout-leaf.orbit:nth-child(3) {
            animation-delay: -2s;
        }

        .logout-text {
            margin-top: 2rem;
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .logout-text.active {
            opacity: 1;
            transform: translateY(0);
        }

        .logout-dots {
            display: inline-block;
            animation: loadingDots 1.5s infinite;
        }

        @keyframes mainLeafSpin {
            0% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.1);
            }
            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        @keyframes orbitLeaf {
            0% {
                transform: rotate(0deg) translateX(40px) rotate(0deg) scale(0.8);
                opacity: 0.6;
            }
            100% {
                transform: rotate(360deg) translateX(40px) rotate(-360deg) scale(0.8);
                opacity: 0.6;
            }
        }

        @keyframes loadingDots {
            0%, 20% {
                content: '.';
            }
            40% {
                content: '..';
            }
            60%, 100% {
                content: '...';
            }
        }

        @media (max-width: 768px) {
            .chef-container {
                padding: 1rem;
            }

            .chef-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .orders-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="logout-overlay">
        <div class="logout-container">
            <i class="fas fa-leaf logout-leaf main"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
        </div>
        <div class="logout-text">Logging out<span class="logout-dots">...</span></div>
    </div>

    <div class="chef-container">
        <div class="chef-header">
            <div>
                <h1><i class="fas fa-utensils"></i> Chef Dashboard</h1>
                <div class="welcome-text">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-power-off"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <div class="orders-grid">
            <?php
            if ($orders_result->num_rows > 0) {
                while($order = $orders_result->fetch_assoc()) {
                    $statusClass = 'status-' . strtolower($order['status']);
                    echo '<div class="order-card">';
                    echo '<div class="order-header">';
                    echo '<span class="order-id">Order #' . $order['order_id'] . '</span>';
                    echo '<span class="order-date"><i class="far fa-clock"></i> ' . date('M d, Y H:i', strtotime($order['order_date'])) . '</span>';
                    echo '</div>';
                    echo '<div class="order-items">';
                    echo '<p>' . $order['items'] . '</p>';
                    echo '</div>';
                    echo '<div class="order-total"><i class="fas fa-receipt"></i> Total: ₱' . number_format($order['total_amount'], 2) . '</div>';
                    
                    // Add discount information if available
                    if ($order['discount_type'] && $order['discount_name']) {
                        echo '<div class="discount-info" style="background: rgba(40, 167, 69, 0.1); padding: 0.5rem; border-radius: 6px; margin: 0.5rem 0; border-left: 3px solid #28a745;">';
                        echo '<small style="color: #28a745; font-weight: 500;"><i class="fas fa-percentage"></i> ' . $order['discount_type'] . ' Discount</small><br>';
                        echo '<small style="color: #666;">Customer: ' . $order['discount_name'] . '</small>';
                        if ($order['discount_id']) {
                            echo '<br><small style="color: #666;">ID: ' . $order['discount_id'] . '</small>';
                        }
                        echo '</div>';
                    }
                    
                    if ($order['status'] == 'cancelled') {
                        echo '<span class="status-label status-cancelled" style="color: #dc3545; font-weight: bold; display: inline-block; margin-top: 0.5rem;">❌ Cancelled</span>';
                    } else {
                        echo '<select class="status-select ' . $statusClass . '" onchange="updateOrderStatus(' . $order['order_id'] . ', this.value)">';
                        echo '<option value="pending" ' . ($order['status'] == 'pending' ? 'selected' : '') . '>⏳ Pending</option>';
                        echo '<option value="processing" ' . ($order['status'] == 'processing' ? 'selected' : '') . '>👨‍🍳 Processing</option>';
                        echo '<option value="completed" ' . ($order['status'] == 'completed' ? 'selected' : '') . '>✅ Completed</option>';
                        echo '</select>';
                    }
                    echo '</div>';
                }
            } else {
                echo '<div class="no-orders">';
                echo '<i class="fas fa-clipboard-list"></i>';
                echo '<h2>No Orders Yet</h2>';
                echo '<p>When new orders come in, they will appear here.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Test function to verify JavaScript is working
        function testFunction() {
            console.log('Test function called - JavaScript is working');
            alert('JavaScript is working!');
        }

        // Test AJAX functionality
        function testAjax() {
            console.log('Testing AJAX functionality...');
            
            fetch('test_ajax.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                console.log('Test AJAX Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Test AJAX Response data:', data);
                alert('AJAX Test Result: ' + data.message + '\nSession: ' + JSON.stringify(data.session_data));
            })
            .catch(error => {
                console.error('Test AJAX Error:', error);
                alert('AJAX Test Failed: ' + error.message);
            });
        }

        function updateOrderStatus(orderId, status) {
            console.log('updateOrderStatus function called');
            const select = event.target;
            const originalValue = select.value;
            
            console.log('Updating order status:', { orderId, status });
            
            // Show loading state
            select.disabled = true;
            select.style.opacity = '0.7';
            
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: status
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                const notification = document.getElementById('notification');
                notification.innerHTML = `<i class="fas ${data.success ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${data.message}`;
                notification.className = 'notification ' + (data.success ? 'success' : 'error');
                notification.style.display = 'block';
                
                if (data.success) {
                    // Update select class
                    select.className = 'status-select status-' + status.toLowerCase();
                } else {
                    // Revert to original value on error
                    select.value = originalValue;
                }
                
                // Re-enable select
                select.disabled = false;
                select.style.opacity = '1';
                
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert to original value on error
                select.value = originalValue;
                select.disabled = false;
                select.style.opacity = '1';
                
                const notification = document.getElementById('notification');
                notification.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.';
                notification.className = 'notification error';
                notification.style.display = 'block';
                
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            });
        }

        // Add logout animation
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const logoutOverlay = document.querySelector('.logout-overlay');
            const logoutText = document.querySelector('.logout-text');
            
            // Show logout overlay with fade effect
            logoutOverlay.style.display = 'flex';
            setTimeout(() => {
                logoutOverlay.classList.add('active');
                logoutText.classList.add('active');
            }, 50);
            
            // Redirect after animation
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 2000);
        });

        // Test if JavaScript is loaded
        console.log('Chef dashboard JavaScript loaded');
    </script>
</body>
</html> 