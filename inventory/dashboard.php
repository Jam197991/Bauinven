<?php
session_start();
include '../includes/database.php';

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
    </div>
</body>
</html>
