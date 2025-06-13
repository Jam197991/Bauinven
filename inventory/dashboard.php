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
$low_stock_query = "SELECT p.*, c.category_name, i.quantity, i.updated_at 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   LEFT JOIN inventory i ON p.product_id = i.product_id 
                   WHERE i.quantity < 50 
                   ORDER BY i.quantity ASC 
                   LIMIT 5";
$low_stock_result = mysqli_query($conn, $low_stock_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        </div>

        <div class="low-stock-table">
            <h2>Low Stock Products</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = mysqli_fetch_assoc($low_stock_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td class="stock-warning"><?php echo $product['quantity']; ?> units</td>
                        <td><?php echo date('M d, Y', strtotime($product['updated_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
