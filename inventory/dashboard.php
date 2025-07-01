<?php
session_start();
include '../includes/database.php';
include '../includes/audit.php';

if (!isset($_SESSION['staff_id'])) {
    echo "<script>
            alert('Please log in first');
            window.location.href = '../index.php';
        </script>";
    exit();
}

// Get total products count
$products_query = "SELECT COUNT(*) as total_products FROM products";
$products_result = mysqli_query($conn, $products_query);
$products_count = mysqli_fetch_assoc($products_result)['total_products'];

// Get total staff count
$staff_query = "SELECT COUNT(*) as total_staff FROM inventory_staff";
$staff_result = mysqli_query($conn, $staff_query);
$staff_count = mysqli_fetch_assoc($staff_result)['total_staff'];

// Get total suppliers count
$suppliers_query = "SELECT COUNT(*) as total_suppliers FROM suppliers";
$suppliers_result = mysqli_query($conn, $suppliers_query);
$suppliers_count = mysqli_fetch_assoc($suppliers_result)['total_suppliers'];

// Get low stock products (less than 50 items)
$low_stock_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.quantity < 50 
                   ORDER BY p.quantity ASC 
                   LIMIT 100";
$low_stock_result = mysqli_query($conn, $low_stock_query);

// Stock category counts
$low_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity < 50 AND quantity > 1";
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $low_stock_count_query))['count'];

$normal_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity >= 50 AND quantity <= 200";
$normal_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $normal_stock_count_query))['count'];

$high_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity > 200";
$high_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $high_stock_count_query))['count'];

$no_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity = 0";
$no_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $no_stock_count_query))['count'];

// New queries for normal and high stock products
$normal_stock_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.quantity >= 50 AND p.quantity <= 200 
                   ORDER BY p.quantity ASC 
                   LIMIT 100";
$normal_stock_result = mysqli_query($conn, $normal_stock_query);

$high_stock_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.quantity > 200 
                   ORDER BY p.quantity ASC 
                   LIMIT 100";
$high_stock_result = mysqli_query($conn, $high_stock_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
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
            padding: 80px 20px 20px;
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

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .card-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .bg-primary { background: #3498db; color: white; }
        .bg-success { background: #2ecc71; color: white; }
        .bg-warning { background: #f1c40f; color: white; }
        .bg-danger { background: #e74c3c; color: white; }

        /* Table Styles */
        .low-stock-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }

        .low-stock-table h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .stock-warning {
            color: #e74c3c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main-content">
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Total Products</h3>
                    <div class="card-icon bg-primary">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo number_format($products_count); ?></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Total Staff</h3>
                    <div class="card-icon bg-success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo number_format($staff_count); ?></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Total Suppliers</h3>
                    <div class="card-icon bg-warning">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo number_format($suppliers_count); ?></div>
            </div>  

            <!-- New Stock Category Cards -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Low Stock Items</h3>
                    <div class="card-icon bg-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo number_format($low_stock_count); ?></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Normal Stock</h3>
                    <div class="card-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo number_format($normal_stock_count); ?></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">High Stock</h3>
                    <div class="card-icon bg-primary">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo number_format($high_stock_count); ?></div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">No Stock</h3>
                    <div class="card-icon bg-warning">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo number_format($no_stock_count); ?></div>
            </div>
            <!-- End New Stock Category Cards -->
        </div>

    </div>
</body>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#lowStockTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "search": "Search:"
        }
    });
    $('#normalStockTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "search": "Search:"
        }
    });
    $('#highStockTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "search": "Search:"
        }
    });
});
</script>
</html>
