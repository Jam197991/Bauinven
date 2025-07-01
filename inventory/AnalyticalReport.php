<?php
session_start();
include '../includes/database.php';

if (!isset($_SESSION['staff_id'])) {
    echo "<script>
            alert('Please log in first');
            window.location.href = '../index.php';
        </script>";
    exit();
}

// --- SALES REPORT SUMMARY ---
// Total completed orders and revenue
$sales_sql = "SELECT COUNT(DISTINCT o.order_id) as total_orders, 
                     SUM(oi.price) as total_sales, 
                     SUM(oi.discounted_price) as total_discounted_sales
              FROM orders o
              JOIN order_items oi ON o.order_id = oi.order_id
              WHERE o.status = 'completed'";
$sales_result = $conn->query($sales_sql);
$sales = $sales_result->fetch_assoc();

// Total cancelled orders
$cancel_sql = "SELECT COUNT(DISTINCT o.order_id) as cancelled_orders
               FROM orders o WHERE o.status = 'cancelled'";
$cancel_result = $conn->query($cancel_sql);
$cancelled = $cancel_result->fetch_assoc();

// --- INVENTORY REPORT SUMMARY ---
$low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity < 50 AND quantity > 0")->fetch_assoc()['count'];
$normal_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity >= 50 AND quantity <= 200")->fetch_assoc()['count'];
$high_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity > 200")->fetch_assoc()['count'];
$out_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity = 0")->fetch_assoc()['count'];

// --- ORDER STATUS SUMMARY ---
$status_counts = [];
$status_query = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $status_query->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytical Report</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="../img/bau.jpg" rel="icon">
    <style>
        .main-content {
            margin-left: 250px; /* or padding-left: 250px; */
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px; /* if sidebar collapses to 70px on mobile */
            }
        }
        .section-header {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 24px;
            letter-spacing: 1px;
            border-left: 6px solid #3498db;
            padding-left: 16px;
            background: #f4f8fb;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(52,152,219,0.07);
        }
        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #eaf6fb 100%);
            border-radius: 18px;
            box-shadow: 0 6px 24px rgba(44,62,80,0.12);
            padding: 32px 28px 24px 28px;
            margin-bottom: 28px;
            border-left: 8px solid #3498db;
            position: relative;
            transition: box-shadow 0.25s, transform 0.18s;
        }
        .summary-card:hover {
            box-shadow: 0 12px 36px rgba(52,152,219,0.22);
            transform: translateY(-4px) scale(1.02);
        }
        .summary-card h4 {
            color: #3498db;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .summary-card p {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        .summary-label {
            color: #7f8c8d;
            font-weight: 500;
        }
        .summary-value {
            color: #222;
            font-weight: 700;
        }
        .card-graph {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.08);
            padding: 24px 16px 16px 16px;
            margin-bottom: 24px;
            border: none;
            transition: box-shadow 0.2s;
        }
        .card-graph:hover {
            box-shadow: 0 8px 32px rgba(52,152,219,0.18);
        }
        .card-graph h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .chartjs-render-monitor {
            border-radius: 12px;
            background: #f8fafc;
            box-shadow: 0 2px 8px rgba(52,152,219,0.04);
        }
        .table-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.08);
            padding: 24px 16px 16px 16px;
            margin-bottom: 32px;
        }
        .table-section h4 {
            color: #388e3c;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .table {
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
        }
        .table th {
            background: #3498db;
            color: #fff;
            font-weight: 600;
        }
        .table td {
            color: #2c3e50;
        }
    </style>

</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>
    <div style="height: 80px;"></div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <div class="section-header">Analytical Report</div>
                </div>
            </div>
            <div class="row" style="margin-bottom: 32px;">
                <!-- Sales Report Summary -->
                <div class="col-md-4">
                    <div class="summary-card">
                        <h4>Sales Report</h4>
                        <p><span class="summary-label">Total Completed Orders:</span> <span class="summary-value"><?php echo $sales['total_orders'] ?? 0; ?></span></p>
                        <p><span class="summary-label">Total Sales:</span> <span class="summary-value">₱<?php echo number_format($sales['total_sales'] ?? 0, 2); ?></span></p>
                        <p><span class="summary-label">Total Discounted Sales:</span> <span class="summary-value">₱<?php echo number_format($sales['total_discounted_sales'] ?? 0, 2); ?></span></p>
                        <p><span class="summary-label">Cancelled Orders:</span> <span class="summary-value"><?php echo $cancelled['cancelled_orders'] ?? 0; ?></span></p>
                    </div>
                </div>
                <!-- Inventory Report Summary -->
                <div class="col-md-4">
                    <div class="summary-card">
                        <h4>Inventory Report</h4>
                        <p><span class="summary-label">Low Stock:</span> <span class="summary-value"><?php echo $low_stock; ?></span></p>
                        <p><span class="summary-label">Normal Stock:</span> <span class="summary-value"><?php echo $normal_stock; ?></span></p>
                        <p><span class="summary-label">High Stock:</span> <span class="summary-value"><?php echo $high_stock; ?></span></p>
                        <p><span class="summary-label">Out of Stock:</span> <span class="summary-value"><?php echo $out_stock; ?></span></p>
                    </div>
                </div>
                <!-- Order Status Summary -->
                <div class="col-md-4">
                    <div class="summary-card">
                        <h4>Order Status</h4>
                        <p><span class="summary-label">Pending:</span> <span class="summary-value"><?php echo $status_counts['pending'] ?? 0; ?></span></p>
                        <p><span class="summary-label">Processing:</span> <span class="summary-value"><?php echo $status_counts['processing'] ?? 0; ?></span></p>
                        <p><span class="summary-label">Completed:</span> <span class="summary-value"><?php echo $status_counts['completed'] ?? 0; ?></span></p>
                        <p><span class="summary-label">Cancelled:</span> <span class="summary-value"><?php echo $status_counts['cancelled'] ?? 0; ?></span></p>
                    </div>
                </div>
            </div>
            <!-- Charts Section -->
            <div class="row">
                <!-- Sales Pie Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card-graph">
                        <h5 class="text-center">Orders: Completed vs Cancelled</h5>
                        <canvas id="ordersPieChart"></canvas>
                    </div>
                </div>
                <!-- Sales Bar Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card-graph">
                        <h5 class="text-center">Total Sales vs Discounted Sales</h5>
                        <canvas id="salesBarChart"></canvas>
                    </div>
                </div>
                <!-- Inventory Pie Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card-graph">
                        <h5 class="text-center">Inventory Stock Levels</h5>
                        <canvas id="inventoryPieChart"></canvas>
                    </div>
                </div>
                <!-- Order Status Pie Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card-graph">
                        <h5 class="text-center">Order Status Distribution</h5>
                        <canvas id="orderStatusPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
<script>
// Pass PHP data to JS
const completedOrders = <?php echo (int)($sales['total_orders'] ?? 0); ?>;
const cancelledOrders = <?php echo (int)($cancelled['cancelled_orders'] ?? 0); ?>;
const totalSales = <?php echo (float)($sales['total_sales'] ?? 0); ?>;
const totalDiscountedSales = <?php echo (float)($sales['total_discounted_sales'] ?? 0); ?>;
const lowStock = <?php echo (int)$low_stock; ?>;
const normalStock = <?php echo (int)$normal_stock; ?>;
const highStock = <?php echo (int)$high_stock; ?>;
const outStock = <?php echo (int)$out_stock; ?>;
const orderStatus = {
    pending: <?php echo (int)($status_counts['pending'] ?? 0); ?>,
    processing: <?php echo (int)($status_counts['processing'] ?? 0); ?>,
    completed: <?php echo (int)($status_counts['completed'] ?? 0); ?>,
    cancelled: <?php echo (int)($status_counts['cancelled'] ?? 0); ?>
};

// Orders Pie Chart
new Chart(document.getElementById('ordersPieChart'), {
    type: 'pie',
    data: {
        labels: ['Completed', 'Cancelled'],
        datasets: [{
            data: [completedOrders, cancelledOrders],
            backgroundColor: ['#2ecc71', '#e74c3c']
        }]
    },
    options: {responsive: true}
});

// Sales Bar Chart
new Chart(document.getElementById('salesBarChart'), {
    type: 'bar',
    data: {
        labels: ['Total Sales', 'Total Discounted Sales'],
        datasets: [{
            label: '₱ Amount',
            data: [totalSales, totalDiscountedSales],
            backgroundColor: ['#3498db', '#f1c40f']
        }]
    },
    options: {responsive: true}
});

// Inventory Pie Chart
new Chart(document.getElementById('inventoryPieChart'), {
    type: 'pie',
    data: {
        labels: ['Low Stock', 'Normal Stock', 'High Stock', 'Out of Stock'],
        datasets: [{
            data: [lowStock, normalStock, highStock, outStock],
            backgroundColor: ['#e67e22', '#2ecc71', '#3498db', '#e74c3c']
        }]
    },
    options: {responsive: true}
});

// Order Status Pie Chart
new Chart(document.getElementById('orderStatusPieChart'), {
    type: 'pie',
    data: {
        labels: ['Pending', 'Processing', 'Completed', 'Cancelled'],
        datasets: [{
            data: [orderStatus.pending, orderStatus.processing, orderStatus.completed, orderStatus.cancelled],
            backgroundColor: ['#f1c40f', '#e67e22', '#2ecc71', '#e74c3c']
        }]
    },
    options: {responsive: true}
});
</script>
</html>