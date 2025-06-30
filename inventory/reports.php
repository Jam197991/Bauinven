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

// Get current date for date filters
$current_date = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $current_date;

// Stock Level Report - Updated with correct logic
$stock_query = "SELECT p.product_name, i.quantity, 
                CASE 
                    WHEN i.quantity <= 30 THEN 'Low Stock'
                    WHEN i.quantity BETWEEN 31 AND 99 THEN 'Normal'
                    ELSE 'High Stock'
                END as stock_status
                FROM inventory i 
                JOIN products p ON i.product_id = p.product_id 
                ORDER BY i.quantity ASC";
$stock_result = mysqli_query($conn, $stock_query);

// Purchase Order Report - Changed to reflect purchases from stock_movements
$purchase_query = "SELECT s.supplier_name, 
                   COUNT(sm.movement_id) as total_transactions,
                   SUM(sm.total_amount) as total_spent,
                   sm.movement_type as purchase_type
                   FROM stock_movements sm
                   JOIN suppliers s ON sm.supplier_id = s.supplier_id
                   WHERE sm.movement_type = 'purchase' AND sm.movement_date BETWEEN '$start_date' AND '$end_date'
                   GROUP BY s.supplier_name, sm.movement_type
                   ORDER BY total_spent DESC";
$purchase_result = mysqli_query($conn, $purchase_query);

// Sales Order Report - Updated to sum item prices for total amount
$sales_query = "SELECT o.order_id, o.order_date, o.total_amount, o.status,
                SUM(CASE WHEN oi.is_pwd_discounted = 1 THEN oi.discounted_price ELSE oi.price END) as calculated_total,
                COUNT(oi.item_id) as total_items
                FROM orders o 
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC";
$sales_result = mysqli_query($conn, $sales_query);

// Sales Summary Report - Updated to use calculated total from order_items
$sales_summary_query = "SELECT 
                        DATE(o.order_date) as sale_date,
                        COUNT(DISTINCT o.order_id) as total_orders,
                        SUM(CASE WHEN oi.is_pwd_discounted = 1 THEN oi.discounted_price ELSE oi.price END) as total_sales,
                        SUM(CASE WHEN oi.is_pwd_discounted = 1 THEN oi.discounted_price ELSE oi.price END) / COUNT(DISTINCT o.order_id) as avg_order_value
                        FROM orders o
                        JOIN order_items oi ON o.order_id = oi.order_id
                        WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
                        GROUP BY DATE(o.order_date)
                        ORDER BY sale_date DESC";
$sales_summary_result = mysqli_query($conn, $sales_summary_query);

// Top Selling Products Report - Updated to use calculated price from order_items
$top_products_query = "SELECT p.product_name, 
                       COUNT(oi.product_id) as total_sold,
                       SUM(CASE WHEN oi.is_pwd_discounted = 1 THEN oi.discounted_price ELSE oi.price END) as total_revenue,
                       AVG(CASE WHEN oi.is_pwd_discounted = 1 THEN oi.discounted_price ELSE oi.price END) as avg_price
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.product_id
                       JOIN orders o ON oi.order_id = o.order_id
                       WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
                       GROUP BY p.product_id
                       ORDER BY total_sold DESC
                       LIMIT 10";
$top_products_result = mysqli_query($conn, $top_products_query);

// Purchase History Report
$purchase_history_query = "SELECT sm.movement_id, p.product_name, sm.movement_type,
                           sm.quantity, sm.unit_price, sm.total_amount,
                           s.supplier_name, sm.movement_date, sm.notes
                           FROM stock_movements sm
                           JOIN products p ON sm.product_id = p.product_id
                           LEFT JOIN suppliers s ON sm.supplier_id = s.supplier_id
                           WHERE sm.movement_date BETWEEN '$start_date' AND '$end_date'
                           ORDER BY sm.movement_date DESC";
$purchase_history_result = mysqli_query($conn, $purchase_history_query);

// Calculate summary statistics
$total_products = mysqli_num_rows($stock_result);
$low_stock_count = 0;
$normal_stock_count = 0;
$high_stock_count = 0;
$total_purchase_orders = mysqli_num_rows($purchase_result);
$total_sales_orders = mysqli_num_rows($sales_result);
$total_sales_amount = 0;
$total_purchase_amount = 0;

// Calculate stock level counts
mysqli_data_seek($stock_result, 0);
while($row = mysqli_fetch_assoc($stock_result)) {
    if($row['stock_status'] == 'Low Stock') {
        $low_stock_count++;
    } elseif($row['stock_status'] == 'Normal') {
        $normal_stock_count++;
    } else {
        $high_stock_count++;
    }
}
mysqli_data_seek($stock_result, 0);

// Calculate totals
while($row = mysqli_fetch_assoc($sales_result)) {
    $total_sales_amount += $row['calculated_total'] ?? $row['total_amount'];
}
mysqli_data_seek($sales_result, 0);

while($row = mysqli_fetch_assoc($purchase_result)) {
    $total_purchase_amount += $row['total_spent'];
}
mysqli_data_seek($purchase_result, 0);

// Get data for charts
// Sales trend data (last 7 days)
$sales_trend_query = "SELECT 
                        DATE(o.order_date) as sale_date,
                        COUNT(DISTINCT o.order_id) as total_orders,
                        SUM(o.total_amount) as total_sales
                        FROM orders o 
                        WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        GROUP BY DATE(o.order_date)
                        ORDER BY sale_date ASC";
$sales_trend_result = mysqli_query($conn, $sales_trend_query);

$sales_dates = [];
$sales_amounts = [];
$sales_orders = [];

while($row = mysqli_fetch_assoc($sales_trend_result)) {
    $sales_dates[] = date('M d', strtotime($row['sale_date']));
    $sales_amounts[] = $row['total_sales'];
    $sales_orders[] = $row['total_orders'];
}

// Stock level distribution data
$stock_distribution_query = "SELECT 
                              CASE 
                                  WHEN i.quantity <= 30 THEN 'Low Stock'
                                  WHEN i.quantity BETWEEN 31 AND 99 THEN 'Normal'
                                  ELSE 'High Stock'
                              END as stock_status,
                              COUNT(*) as count
                              FROM inventory i 
                              GROUP BY stock_status";
$stock_distribution_result = mysqli_query($conn, $stock_distribution_query);

$stock_labels = [];
$stock_counts = [];
$stock_colors = [];

while($row = mysqli_fetch_assoc($stock_distribution_result)) {
    $stock_labels[] = $row['stock_status'];
    $stock_counts[] = $row['count'];
    if($row['stock_status'] == 'Low Stock') {
        $stock_colors[] = '#e74c3c';
    } elseif($row['stock_status'] == 'Normal') {
        $stock_colors[] = '#2ecc71';
    } else {
        $stock_colors[] = '#3498db';
    }
}

// Top products by revenue
$top_products_chart_query = "SELECT p.product_name, 
                             SUM(oi.price) as total_revenue
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.product_id
                             JOIN orders o ON oi.order_id = o.order_id
                             WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
                             GROUP BY p.product_id
                             ORDER BY total_revenue DESC
                             LIMIT 8";
$top_products_chart_result = mysqli_query($conn, $top_products_chart_query);

$product_names = [];
$product_revenues = [];

while($row = mysqli_fetch_assoc($top_products_chart_result)) {
    $product_names[] = substr($row['product_name'], 0, 15) . '...';
    $product_revenues[] = $row['total_revenue'];
}

// Monthly sales comparison
$monthly_sales_query = "SELECT 
                        DATE_FORMAT(o.order_date, '%Y-%m') as month,
                        SUM(o.total_amount) as total_sales,
                        COUNT(DISTINCT o.order_id) as total_orders
                        FROM orders o 
                        WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
                        ORDER BY month ASC";
$monthly_sales_result = mysqli_query($conn, $monthly_sales_query);

$monthly_labels = [];
$monthly_sales = [];
$monthly_orders = [];

while($row = mysqli_fetch_assoc($monthly_sales_result)) {
    $monthly_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthly_sales[] = $row['total_sales'];
    $monthly_orders[] = $row['total_orders'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="../img/bau.jpg" rel="icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f6fa;
        }

        .main-content {
            margin-left: 250px;
            padding: 60px 15px 15px;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .sidebar.expanded ~ .main-content {
                margin-left: 250px;
            }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .date-filters {
            display: flex;
            gap: 10px;
            align-items: center;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .date-filters input {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }

        .date-filters button {
            padding: 6px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .date-filters button:hover {
            background: #2980b9;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .card-title {
            font-size: 0.9rem;
            color: #2c3e50;
        }

        .card-value {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .card-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .bg-primary { background: #3498db; color: white; }
        .bg-success { background: #2ecc71; color: white; }
        .bg-warning { background: #f1c40f; color: white; }
        .bg-danger { background: #e74c3c; color: white; }
        .bg-info { background: #17a2b8; color: white; }
        .bg-secondary { background: #6c757d; color: white; }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .report-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .report-header {
            background: #3498db;
            color: white;
            padding: 10px 15px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .report-content {
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-low-stock { background: #f8d7da; color: #721c24; }
        .status-normal { background: #d4edda; color: #155724; }
        .status-high-stock { background: #cce5ff; color: #004085; }
        .status-medium { background: #fff3cd; color: #856404; }
        .status-good { background: #d4edda; color: #155724; }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .chart-container {
            background: white;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            padding: 10px;
            margin-bottom: 0;
            height: 200px;
        }

        .chart-title {
            font-size: 0.8rem;
            color: #2c3e50;
            margin-bottom: 8px;
            text-align: center;
            font-weight: 600;
        }

        canvas {
            max-width: 100%;
            max-height: 150px !important;
            height: 150px !important;
        }

        .export-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 8px;
        }

        .export-btn:hover {
            background: #229954;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Reports & Analytics</h1>
            <form class="date-filters" method="GET">
                <label>From:</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                <label>To:</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                <button type="submit">Filter</button>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Products</div>
                        <div class="card-value"><?php echo $total_products; ?></div>
                    </div>
                    <div class="card-icon bg-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Low Stock Items</div>
                        <div class="card-value"><?php echo $low_stock_count; ?></div>
                    </div>
                    <div class="card-icon bg-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Normal Stock</div>
                        <div class="card-value"><?php echo $normal_stock_count; ?></div>
                    </div>
                    <div class="card-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">High Stock</div>
                        <div class="card-value"><?php echo $high_stock_count; ?></div>
                    </div>
                    <div class="card-icon bg-info">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Purchase Orders</div>
                        <div class="card-value"><?php echo $total_purchase_orders; ?></div>
                    </div>
                    <div class="card-icon bg-secondary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Sales Orders</div>
                        <div class="card-value"><?php echo $total_sales_orders; ?></div>
                    </div>
                    <div class="card-icon bg-warning">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
            <!-- Stock Level Report -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-boxes"></i> Stock Level Report
                    <button class="export-btn" onclick="exportTable('stock-table', 'Stock_Level_Report')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="report-content">
                    <div class="table-container">
                        <table id="stock-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($stock_result)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['stock_status'])); ?>">
                                            <?php echo $row['stock_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sales Order Report -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-chart-line"></i> Sales Order Report
                    <button class="export-btn" onclick="exportTable('sales-order-table', 'Sales_Order_Report')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="report-content">
                    <div class="table-container">
                        <table id="sales-order-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Discount Type</th>
                                    <th>Discount Name</th>
                                    <th>Discount ID</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sales_order_query = "SELECT o.order_id, o.order_date, o.discount_type, o.discount_name, o.discount_id, o.status,
                                    SUM(CASE WHEN oi.discounted_price IS NOT NULL THEN oi.discounted_price ELSE oi.price END) AS total_amount
                                    FROM orders o
                                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                                    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
                                    GROUP BY o.order_id
                                    ORDER BY o.order_date DESC";
                                $sales_order_result = mysqli_query($conn, $sales_order_query);
                                while($row = mysqli_fetch_assoc($sales_order_result)) { ?>
                                <tr>
                                    <td><?php echo $row['order_id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['discount_type'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['discount_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['discount_id'] ?? ''); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Export functionality
        function exportTable(tableId, filename) {
            const table = document.getElementById(tableId);
            const html = table.outerHTML;
            const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            const downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = url;
            downloadLink.download = filename + '.xls';
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to table rows
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    row.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = '#f8f9fa';
                    });
                    row.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = '';
                    });
                });
            });
        });
    </script>
</body>
</html>